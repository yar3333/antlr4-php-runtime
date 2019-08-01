<?php

namespace Antlr4\Atn;

use Antlr4\Dfa\DFAState;

class SimState
{
    /**
     * @var int
     */
    public $index;

    /**
     * @var int
     */
    public $line;

    /**
     * @var int
     */
    public $charPos;

    /**
     * @var DFAState
     */
    public $dfaState;

    function __construct()
    {
        $this->reset();
    }

    function reset() : void
    {
        $this->index = -1;
        $this->line = 0;
        $this->charPos = -1;
        $this->dfaState = null;
    }
}