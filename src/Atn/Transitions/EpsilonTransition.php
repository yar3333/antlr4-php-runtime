<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\States\ATNState;

class EpsilonTransition extends Transition
{
    public $serializationType = Transition::EPSILON;
    public $isEpsilon = true;

    public $outermostPrecedenceReturn;

    function __construct(ATNState $target, $outermostPrecedenceReturn=null)
    {
        parent::__construct($target);

        $this->outermostPrecedenceReturn = $outermostPrecedenceReturn;
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }

    function __toString()
    {
        return "epsilon";
    }
}