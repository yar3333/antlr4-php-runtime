<?php

namespace Antlr4;

use Antlr4\Tree\ErrorNode;
use Antlr4\Tree\ParseTreeListener;
use Antlr4\Tree\TerminalNode;

class ParserTraceListener implements ParseTreeListener
{
    /**
     * @var Parser
     */
    public $parser;

    function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    function enterEveryRule(ParserRuleContext $ctx) : void
    {
        //$console->log("enter   " + this.parser.ruleNames[ctx.ruleIndex] + ", LT(1)=" . $this->parser->_input->LT(1).$text);
    }

    function visitTerminal(TerminalNode $node) : void
    {
        //$console->log("consume " + node.symbol + " rule " . $this->parser->ruleNames[$this->parser->_ctx->ruleIndex]);
    }

    function exitEveryRule(ParserRuleContext $ctx) : void
    {
        //$console->log("exit    " + this.parser.ruleNames[ctx.ruleIndex] + ", LT(1)=" . $this->parser->_input->LT(1).$text);
    }

    function visitErrorNode(ErrorNode $node): void
    {
    }
}