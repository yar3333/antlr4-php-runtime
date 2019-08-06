<?php

namespace Antlr4\Atn\States;

class BlockEndState extends ATNState
{
    /**
     * @var ATNState
     */
    public $startState;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::BLOCK_END;
        $this->startState = null;
    }
}