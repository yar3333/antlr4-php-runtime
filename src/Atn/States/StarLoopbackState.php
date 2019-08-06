<?php

namespace Antlr4\Atn\States;

class StarLoopbackState extends ATNState
{
    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::STAR_LOOP_BACK;
    }
}