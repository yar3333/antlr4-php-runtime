<?php

namespace Antlr4\Tree;

use Antlr4\RuleContext;

interface RuleNode extends ParseTree
{
    function getRuleContext() : RuleContext;
}