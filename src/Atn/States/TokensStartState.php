<?php

namespace Antlr4\Atn\States;

class TokensStartState extends DecisionState
{
    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::TOKEN_START;
    }
}