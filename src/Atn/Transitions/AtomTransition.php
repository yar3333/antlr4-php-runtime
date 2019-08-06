<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\States\ATNState;
use Antlr4\IntervalSet;

class AtomTransition extends Transition
{
    public $serializationType = Transition::ATOM;

    /**
     * @var int
     */
    public $label;

    function __construct(ATNState $target, int $label)
    {
        parent::__construct($target);

        $this->label = $label;
    }

	function label() : IntervalSet { return IntervalSet::fromInt($this->label); }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return $this->label === $symbol;
    }

    function __toString()
    {
        return (string)$this->label;
    }
}