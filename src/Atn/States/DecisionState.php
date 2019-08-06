<?php

namespace Antlr4\Atn\States;

class DecisionState extends ATNState
{
    public $decision = -1;
    public $nonGreedy = false;
}