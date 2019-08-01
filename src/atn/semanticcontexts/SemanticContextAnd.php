<?php

namespace Antlr4\Atn\SemanticContexts;

use Antlr4\Recognizer;
use Antlr4\RuleContext;
use Antlr4\Utils\Set;
use Antlr4\Utils\Utils;

// A semantic context which is true whenever none of the contained contexts is false.
class SemanticContextAnd extends SemanticContext
{
    /**
     * @var SemanticContext[]
     */
    public $opnds;

    function __construct(SemanticContext $a, SemanticContext $b)
    {
        parent::__construct();

        /** @var Set<SemanticContext> $operands */
        $operands = new Set();

        if ($a instanceof self) $operands->addAll($a->opnds);
        else $operands->add($a);

        if ($b instanceof self) $operands->addAll($b->opnds);
        else $operands->add($b);

        /** @var PrecedencePredicate[] $precedencePredicates */
        $precedencePredicates = PrecedencePredicate::filterPrecedencePredicates($operands);
        if ($precedencePredicates)
        {
            // interested in the transition with the lowest precedence
            /** @var PrecedencePredicate $reduced */
            $reduced = Utils::minObjects($precedencePredicates);
            $operands->add($reduced);
        }

        $this->opnds = $operands->values();
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return Utils::equalArrays($this->opnds, $other->opnds);
    }

    // {@inheritDoc}
    // <p>The evaluation of predicates by this context is short-circuiting, but unordered.</p>
    function eval(Recognizer $parser, RuleContext $outerContext) : bool
    {
        foreach ($this->opnds as $opnd)
        {
            if (!$opnd->eval($parser, $outerContext)) return false;
        }
        return true;
    }

    function evalPrecedence($parser, $outerContext) : ?SemanticContext
    {
        $differs = false;
        $operands = [];
        foreach ($this->opnds as $iValue)
        {
            $context = $iValue;
            $evaluated = $context->evalPrecedence($parser, $outerContext);
            $differs |= $evaluated !== $context;

            // The AND context is false if any element is false
            if ($evaluated === null) return null;

            if ($evaluated !== SemanticContext::NONE())
            {
                // Reduce the result by skipping true elements
                $operands[] = $evaluated;
            }
        }
        if (!$differs) return $this;

        // all elements were true, so the AND context is true
        if (count($operands) === 0) return SemanticContext::NONE();

        $result = null;
        foreach ($operands as $o)
        {
            $result = $result === null ? $o : self::andContext($result, $o);
        }
        return $result;
    }

    function __toString()
    {
        $s = "";
        foreach ($this->opnds as $o) {
            $s .= "&& " . $o;
        }
        return strlen($s) > 3 ? (string)substr($s, 3) : $s;
    }
}