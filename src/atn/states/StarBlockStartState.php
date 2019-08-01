<?php

namespace Antlr4\Atn\States;

class StarBlockStartState extends BlockStartState
{
    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::STAR_BLOCK_START;
    }
}