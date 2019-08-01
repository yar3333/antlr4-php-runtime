<?php

namespace Antlr4\Atn\States;

class PlusBlockStartState extends BlockStartState
{
    /**
     * @var ATNState
     */
    public $loopBackState;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::PLUS_BLOCK_START;

        $this->loopBackState = null;
    }
}