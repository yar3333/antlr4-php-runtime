<?php

namespace Antlr4\Atn\States;

class BlockStartState extends DecisionState
{
    /**
     * @var ATNState
     */
    public $endState;

    function __construct()
    {
        parent::__construct();

        $this->endState = null;
    }
}