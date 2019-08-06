<?php

namespace Antlr4\Atn\SemanticContexts;

use Antlr4\Recognizer;
use Antlr4\RuleContext;
use Antlr4\Utils\Set;
use Antlr4\Utils\Utils;

// A semantic context which is true whenever at least one of the contained contexts is true.
class SemanticContextOr extends SemanticContext
{
    /**
     * @var SemanticContext[]
     */
    public $opnds;

    function __construct($a, $b)
    {
        parent::__construct();

        $operands = new Set();
        if ($a instanceof self) {
            foreach ($a->opnds as $o) {
                $operands->add($o);
            }
        } else {
            $operands->add($a);
        }
        if ($b instanceof self) {
            foreach ($b->opnds as $o) {
                $operands->add($o);
            }
        } else {
            $operands->add($b);
        }

        $precedencePredicates = PrecedencePredicate::filterPrecedencePredicates($operands);

        if ($precedencePredicates)
        {
            // interested in the transition with the highest precedence
            usort($precedencePredicates, function (object $a, object $b) { return $a->compareTo($b); });
            $reduced = $precedencePredicates[count($precedencePredicates) - 1];
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

    /**
     * The evaluation of predicates by this context is short-circuiting, but unordered.
     * @param $parser
     * @param $outerContext
     * @return bool
     */
    function eval(Recognizer $parser, RuleContext $outerContext) : bool
    {
        foreach ($this->opnds as $opnd)
        {
            if ($opnd->eval($parser, $outerContext)) return true;
        }
        return false;
    }

    function evalPrecedence($parser, $outerContext) : ?SemanticContext
    {
        $differs = false;
        $operands = [];
        foreach ($this->opnds as $context) {
            $evaluated = $context->evalPrecedence($parser, $outerContext);
            $differs |= ($evaluated !== $context);
            if ($evaluated === SemanticContext::NONE())
            {
                // The OR context is true if any element is true
                return SemanticContext::NONE();
            }
            else if ($evaluated !== null)
            {
                // Reduce the result by skipping false elements
                $operands[] = $evaluated;
            }
        }
        if (!$differs) return $this;

        // all elements were false, so the OR context is false
        if (!$operands) return null;

        $result = null;
        foreach ($operands as $o) {
            //return result === null ? o : SemanticContext.orContext(result, o);
            $result = $result === null ? $o : SemanticContext::orContext($result, $o);
        }
        return $result;
    }

    function __toString()
    {
        $s = "";
        foreach ($this->opnds as $o) $s .= "|| " . $o;
        return strlen($s) > 3 ? (string)substr($s, 3) : $s;
    }
}