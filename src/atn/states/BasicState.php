<?php

namespace Antlr4\Atn\States;

class BasicState extends ATNState
{
    /**
     * @var ATNState
     */
    public $loopBackState;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::BASIC;
    }
}