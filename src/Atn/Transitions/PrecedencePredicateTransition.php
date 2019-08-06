<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\SemanticContexts\PrecedencePredicate;
use Antlr4\Atn\States\ATNState;

class PrecedencePredicateTransition extends AbstractPredicateTransition
{
    public $serializationType = Transition::PRECEDENCE;
    public $isEpsilon = true;

    /**
     * @var int
     */
    public $precedence;

    function __construct(ATNState $target, int $precedence)
    {
        parent::__construct($target);

        $this->precedence = $precedence;
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }

    function getPredicate() : PrecedencePredicate
    {
        return new PrecedencePredicate($this->precedence);
    }

    function __toString()
    {
        return $this->precedence . " >= _p";
    }
}