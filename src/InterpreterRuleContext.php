<?php

namespace Antlr4;

class InterpreterRuleContext extends ParserRuleContext
{
    /** @var int */
    private $ruleIndex;

    function __construct($parent, $invokingStateNumber, $ruleIndex)
    {
        parent::__construct($parent, $invokingStateNumber);

        $this->ruleIndex = $ruleIndex;
    }

    function getRuleIndex() : int
    {
        return $this->ruleIndex;
    }
}
