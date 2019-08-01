<?php

namespace Antlr4\Atn\States;

class RuleStartState extends ATNState
{
    /**
     * @var ATNState
     */
    public $stopState;

    /**
     * @var bool
     */
    public $isPrecedenceRule;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::RULE_START;
        $this->stopState = null;
        $this->isPrecedenceRule = false;
    }
}