<?php

namespace Antlr4\Atn\SemanticContexts;

use Antlr4\Recognizer;
use Antlr4\Utils\Set;

class PrecedencePredicate extends SemanticContext
{
    /**
     * @var int
     */
    public $precedence;

    function __construct(int $precedence=0)
    {
        parent::__construct();

        $this->precedence = $precedence;
    }

    function eval(Recognizer $parser, $outerContext) : bool
    {
        return $parser->precpred($outerContext, $this->precedence);
    }

    function evalPrecedence(Recognizer $parser, $outerContext) : ?SemanticContext
    {
        if ($parser->precpred($outerContext, $this->precedence)) {
            return SemanticContext::NONE();
        } else {
            return null;
        }
    }

    function compareTo(PrecedencePredicate $other) : int
    {
        return $this->precedence - $other->precedence;
    }

    function equals(object $other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->precedence === $other->precedence;
    }

    function __toString()
    {
        return "{" . $this->precedence . ">=prec}?";
    }

    /**
     * @param Set $set
     * @return PrecedencePredicate[]
     */
    static function filterPrecedencePredicates(Set $set) : array
    {
        $result = [];
        foreach ($set->values() as $context) {
            if ($context instanceof self) {
                $result[] = $context;
            }
        }
        return $result;
    }
}