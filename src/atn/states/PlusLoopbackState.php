<?php

namespace Antlr4\Atn\States;

class PlusLoopbackState extends DecisionState
{
    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::PLUS_LOOP_BACK;
    }
}