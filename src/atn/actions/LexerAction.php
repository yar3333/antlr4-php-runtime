<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

abstract class LexerAction
{
    public $actionType;
    public $isPositionDependent;

    function __construct(int $actionType)
    {
        $this->actionType = $actionType;
        $this->isPositionDependent = false;
    }

    function equals(LexerAction $other) : bool
    {
        return $this === $other;
    }

    abstract function execute(Lexer $lexer);
}
