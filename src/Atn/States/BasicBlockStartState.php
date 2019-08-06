<?php

namespace Antlr4\Atn\States;

class BasicBlockStartState extends BlockStartState
{
    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::BLOCK_START;
    }
}