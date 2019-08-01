<?php

namespace Antlr4\Tree;

use Antlr4\Token;

interface TerminalNode extends ParseTree
{
    function getSymbol() : Token;
}