<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\IntervalSet;
use Antlr4\Token;

class SetTransition extends Transition
{
    public $serializationType;

    /**
     * @var IntervalSet
     */
    public $set;

    // A transition containing a set of values.
    function __construct($target, IntervalSet $set)
    {
        parent::__construct($target);

        $this->serializationType = Transition::SET;
        if ($set) {
            $this->set = $set;
        } else {
            $this->set = new IntervalSet();
            $this->set->addOne(Token::INVALID_TYPE);
        }
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return $this->set->contains($symbol);
    }

	function label() : IntervalSet { return $this->set; }

    function __toString()
    {
        return (string)$this->set;
    }
}