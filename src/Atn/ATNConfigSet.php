<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn;

use Antlr4\Atn\SemanticContexts\SemanticContext;
use Antlr4\PredictionContexts\PredictionContext;
use Antlr4\Utils\BitSet;
use Antlr4\Utils\DoubleKeyMap;
use Antlr4\Utils\Utils;
use Antlr4\Utils\Set;

// Specialized {@link Set}{@code <}{@link ATNConfig}{@code >} that can track
// info about the set, with support for combining similar configurations using a
// graph-structured stack.
class ATNConfigSet
{
    /**
     * @var Set
     */
    public $configLookup;

    public $fullCtx;

    /**
     * @var bool
     */
    public $readOnly;

    /**
     * @var ATNConfig[]
     */
    public $configs;

    /**
     * @var int
     */
    public $uniqueAlt;

    /**
     * @var BitSet
     */
    public $conflictingAlts;

    /**
     * @var bool
     */
    public $hasSemanticContext;

    public $dipsIntoOuterContext;

    public $cachedHashCode;

    static function equalATNConfigs(?ATNConfig $a, ?ATNConfig $b) : bool
    {
        if ($a === $b) return true;
        if ($a === null || $b === null) return false;
        return $a->equals($b);
    }

    function __construct(bool $fullCtx=true)
    {
        // The reason that we need this is because we don't want the hash map to use
        // the standard hash code and equals. We need all configurations with the
        // same {@code (s,i,_,semctx)} to be equal. Unfortunately, this key effectively doubles
        // the number of objects associated with ATNConfigs. The other solution is
        // to use a hash table that lets us specify the equals/hashcode operation.
        // All configs but hashed by (s, i, _, pi) not including context. Wiped out
        // when we go readonly as this set becomes a DFA state.
        $this->configLookup = new Set();
        $this->configLookup->equalsFunction = function($a, $b) { return self::equalATNConfigs($a, $b); };

        // Indicates that this configuration set is part of a full context
        // LL prediction. It will be used to determine how to merge $. With SLL
        // it's a wildcard whereas it is not for LL context merge.
        $this->fullCtx = $fullCtx;

        // Indicates that the set of configurations is read-only. Do not
        // allow any code to manipulate the set; DFA states will point at
        // the sets and they must not change. This does not protect the other
        // fields; in particular, conflictingAlts is set after
        // we've made this readonly.
        $this->readOnly = false;

        // Track the elements as they are added to the set; supports get(i)
        $this->configs = [];

        // TODO: these fields make me pretty uncomfortable but nice to pack up info together, saves recomputation
        // TODO: can we track conflicts as they are added to save scanning configs later?
        $this->uniqueAlt = 0;

        $this->conflictingAlts = null;

        // Used in parser and lexer. In lexer, it indicates we hit a pred
        // while computing a closure operation. Don't make a DFA state from this.
        $this->hasSemanticContext = false;
        $this->dipsIntoOuterContext = false;

        $this->cachedHashCode = -1;
    }

    // Adding a new config means merging contexts with existing configs for
    // {@code (s, i, pi, _)}, where {@code s} is the
    // {@link ATNConfig//state}, {@code i} is the {@link ATNConfig//alt}, and
    // {@code pi} is the {@link ATNConfig//semanticContext}. We use {@code (s,i,pi)} as key.
    //
    // <p>This method updates {@link //dipsIntoOuterContext} and {@link //hasSemanticContext} when necessary.</p>
    function add(ATNConfig $config, DoubleKeyMap $mergeCache=null) : bool
    {
        if ($this->readOnly) throw new \RuntimeException("This set is readonly");

        if ($config->semanticContext !== SemanticContext::NONE())
        {
            $this->hasSemanticContext = true;
        }

        if ($config->reachesIntoOuterContext > 0)
        {
            $this->dipsIntoOuterContext = true;
        }

        /** @var ATNConfig $existing */
        $existing = $this->configLookup->add($config);
        if ($existing->equals($config))
        {
            $this->cachedHashCode = -1;
            $this->configs[] = $config;// track order here
            return true;
        }

        // a previous (s,i,pi,_), merge with it and save result
        $rootIsWildcard = !$this->fullCtx;
        $merged = PredictionContext::merge($existing->context, $config->context, $rootIsWildcard, $mergeCache);

        // no need to check for existing.context, config.context in cache
        // since only way to create new graphs is "call rule" and here. We
        // cache at both places.
        $existing->reachesIntoOuterContext = max( $existing->reachesIntoOuterContext, $config->reachesIntoOuterContext);

        // make sure to preserve the precedence filter suppression during the merge
        if ($config->isPrecedenceFilterSuppressed())
        {
            $existing->setPrecedenceFilterSuppressed(true);
        }

        $existing->context = $merged;// replace context; no need to alt mapping

        return true;
    }

    function getStates() : Set
    {
        $states = new Set();
        foreach ($this->configs as $c) {
            $states->add($c->state);
        }
        return $states;
    }

    /**
     * @return SemanticContext[]
     */
    function getPredicates() : array
    {
        $preds = [];
        foreach ($this->configs as $c)
        {
            if ($c->semanticContext !== SemanticContext::NONE())
            {
                $preds[] = $c->semanticContext;
            }
        }
        return $preds;
    }

    /**
     * @return ATNConfig[]
     */
    function items() : array { return $this->configs; }

    function optimizeConfigs(ATNSimulator $interpreter) : void
    {
        if ($this->readOnly) throw new \RuntimeException("This set is readonly");
        if ($this->configLookup->isEmpty()) return;

        foreach ($this->configs as $config)
        {
            $config->context = $interpreter->getCachedContext($config->context);
        }
    }

    function addAll(array $coll) : void
    {
        foreach ($coll as $c)
        {
            $this->add($c);
        }
    }

    function equals($other) : bool
    {
        return $this === $other ||
            ($other instanceof self &&
            Utils::equalArrays($this->configs, $other->configs) &&
            $this->fullCtx === $other->fullCtx &&
            $this->uniqueAlt === $other->uniqueAlt &&
            $this->conflictingAlts === $other->conflictingAlts &&
            $this->hasSemanticContext === $other->hasSemanticContext &&
            $this->dipsIntoOuterContext === $other->dipsIntoOuterContext);
    }

    function getLength() : int { return count($this->configs); }

    function isEmpty() : bool
    {
        return count($this->configs) === 0;
    }

    function contains(object $item) : bool
    {
        if (!$this->configLookup) throw new \RuntimeException("This method is not implemented for readonly sets.");

        return $this->configLookup->contains($item);
    }

    function containsFast(ATNConfig $item) : bool
    {
        return $this->contains($item);
    }

    function clear() : void
    {
        if ($this->readOnly) throw new \RuntimeException("This set is readonly");

        $this->configs = [];
        $this->cachedHashCode = -1;
        $this->configLookup = new Set();
    }

    function setReadonly(bool $readOnly) : void
    {
        $this->readOnly = $readOnly;
        if ($readOnly)
        {
            $this->configLookup = null;// can't mod, no need for lookup cache
        }
    }

    function __toString()
    {
        return Utils::arrayToString($this->configs) .
            ($this->hasSemanticContext ? ",hasSemanticContext=" . $this->hasSemanticContext : "") .
            ($this->uniqueAlt !== ATN::INVALID_ALT_NUMBER ? ",uniqueAlt=" . $this->uniqueAlt : "") .
            ($this->conflictingAlts !== null ? ",conflictingAlts=" . $this->conflictingAlts : "") .
            ($this->dipsIntoOuterContext ? ",dipsIntoOuterContext" : "");
    }
}
