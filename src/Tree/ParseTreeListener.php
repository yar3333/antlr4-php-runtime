<?php

namespace Antlr4\Tree;

use Antlr4\ParserRuleContext;

interface ParseTreeListener
{
    function visitTerminal(TerminalNode $node) : void;

    function visitErrorNode(ErrorNode $node) : void;

    function enterEveryRule(ParserRuleContext $ctx) : void;

    function exitEveryRule(ParserRuleContext $nctx) : void;
}