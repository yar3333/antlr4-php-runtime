<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection PhpUnusedParameterInspection */

namespace Antlr4\Atn;

use Antlr4\Atn\SemanticContexts\SemanticContext;
use Antlr4\Atn\States\ATNState;
use Antlr4\PredictionContexts\PredictionContext;
use Antlr4\Recognizer;

// A tuple: (ATN state, predicted alt, syntactic, semantic context).
// The syntactic context is a graph-structured stack node whose
// path(s) to the root is the rule invocation(s)
// chain used to arrive at the state.  The semantic context is
// the tree of semantic predicates encountered before reaching an ATN state.
class ATNConfig
{
 	const SUPPRESS_PRECEDENCE_FILTER = 0x40000000;

    /**
     * @var ATNState
     */
    public $state;

    /**
     * @var int
     */
    public $alt;

    /**
     * @var PredictionContext
     */
    public $context;

    /**
     * @var SemanticContext
     */
    public $semanticContext;

    /**
     * @var int
     */
    public $reachesIntoOuterContext;

    private static function checkParams(?object $params, $isCfg=false) : object
    {
        if (!$params)
        {
            $result = (object)[ 'state'=>null, 'alt'=>null, 'context'=>null, 'semanticContext'=>null ];
            if ($isCfg) $result->reachesIntoOuterContext = 0;
            return $result;
        }

        $props = (object)[];
        $props->state = $params->state ?? null;
        $props->alt = $params->alt ?? null;
        $props->context = $params->context ?? null;
        $props->semanticContext = $params->semanticContext ?? null;
        if ($isCfg)
        {
            $props->reachesIntoOuterContext = $params->reachesIntoOuterContext ?? 0;
        }
        return $props;
    }

    function getState() : ATNState
    {
        return $this->state;
    }

	function isPrecedenceFilterSuppressed() : bool { return ($this->reachesIntoOuterContext & self::SUPPRESS_PRECEDENCE_FILTER) !== 0; }

	function setPrecedenceFilterSuppressed(bool $value) : void
    {
		if ($value) {
			$this->reachesIntoOuterContext |= self::SUPPRESS_PRECEDENCE_FILTER;
		}
		else {
			$this->reachesIntoOuterContext &= ~self::SUPPRESS_PRECEDENCE_FILTER;
		}
	}


    function __construct(?object $params, ?ATNConfig $_config)
    {
        $params = self::checkParams($params);
        $config = self::checkParams($_config, true);

        $this->state = $params->state ?? $config->state;
        $this->alt = $params->alt ?? $config->alt;
        $this->context = $params->context ?? $config->context;
        $this->semanticContext = $params->semanticContext ?? ($config->semanticContext ?? SemanticContext::NONE());
        $this->reachesIntoOuterContext = $config->reachesIntoOuterContext;
    }

    // An ATN configuration is equal to another if both have
    // the same state, they predict the same alternative, and
    // syntactic/semantic contexts are the same.
    function equals($other) : bool
    {
        if ($this === $other) return true;

        if (!($other instanceof self)) return false;

        return $this->state->stateNumber === $other->state->stateNumber &&
            $this->alt === $other->alt &&
            ($this->context === null ? $other->context === null : $this->context->equals($other->context)) &&
            $this->semanticContext->equals($other->semanticContext) &&
            $this->isPrecedenceFilterSuppressed() === $other->isPrecedenceFilterSuppressed();
    }

	/**
	 * This method gets the value of the {@link #reachesIntoOuterContext} field
	 * as it existed prior to the introduction of the
	 * {@link #isPrecedenceFilterSuppressed} method.
	 */
	function getOuterContextDepth() : int
    {
		return $this->reachesIntoOuterContext & ~self::SUPPRESS_PRECEDENCE_FILTER;
	}

	function toString(Recognizer $recog, bool $showAlt) : string
	{
		$buf = '(';
		$buf .= $this->state;
		if ($showAlt) $buf .= "," . $this->alt;
        if ($this->context) $buf .= ",[" . $this->context . "]";
        if ($this->semanticContext && $this->semanticContext !== SemanticContext::NONE())
        {
            $buf .= "," . $this->semanticContext;
        }
        if ($this->getOuterContextDepth() > 0)
        {
            $buf .= ",up=" . $this->getOuterContextDepth();
        }
		$buf .= ')';
		return $buf;
    }

    function __toString()
    {
        return
            "(" . $this->state . "," . $this->alt .
                ($this->context ? ",[" . $this->context . "]" : "") .
                ($this->semanticContext && $this->semanticContext !== SemanticContext::NONE() ? "," . $this->semanticContext : "") .
                ($this->reachesIntoOuterContext > 0 ? ",up=" . $this->reachesIntoOuterContext : "") .
            ")";
    }
}
