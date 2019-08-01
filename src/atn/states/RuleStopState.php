<?php

namespace Antlr4\Atn\States;

class RuleStopState extends ATNState
{
    /**
     * @var ATNState
     */
    public $stopState;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::RULE_STOP;
    }
}