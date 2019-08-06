<?php

namespace Antlr4\Atn\Transitions;

class NotSetTransition extends SetTransition
{
    public $serializationType = Transition::NOT_SET;

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return $symbol >= $minVocabSymbol && $symbol <= $maxVocabSymbol &&
            !parent::matches($symbol, $minVocabSymbol, $maxVocabSymbol);
    }

    function __toString()
    {
        /** @noinspection TypesCastingCanBeUsedInspection */
        return '~' . parent::__toString();
    }
}