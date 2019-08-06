<?php

namespace Antlr4\Atn\States;

class StarLoopEntryState extends DecisionState
{
    /**
     * @var ATNState
     */
    public $loopBackState;

    /**
     * @var bool
     */
    public $isPrecedenceDecision;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::STAR_LOOP_ENTRY;

        $this->loopBackState = null;

        // Indicates whether this state can benefit from a precedence DFA during SLL decision making.
        $this->isPrecedenceDecision = null;
    }
}