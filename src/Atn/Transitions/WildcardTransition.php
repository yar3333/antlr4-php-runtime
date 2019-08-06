<?php

namespace Antlr4\Atn\Transitions;

class WildcardTransition extends Transition
{
    public $serializationType = Transition::WILDCARD;

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return $symbol >= $minVocabSymbol && $symbol <= $maxVocabSymbol;
    }

    function __toString()
    {
        return ".";
    }
}