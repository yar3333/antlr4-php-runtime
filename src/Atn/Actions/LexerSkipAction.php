<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

// Implements the {@code skip} lexer action by calling {@link Lexer//skip}.
// <p>The {@code skip} command does not have any parameters, so this action is implemented as a singleton instance exposed by {@link //INSTANCE}.</p>
class LexerSkipAction extends LexerAction
{
    private static $_INSTANCE;
    static function INSTANCE() : LexerSkipAction { return self::$_INSTANCE ?? (self::$_INSTANCE = new LexerSkipAction()); }

    function __construct()
    {
        parent::__construct(LexerActionType::SKIP);
    }

    function execute(Lexer $lexer) : void
    {
        $lexer->skip();
    }

    function __toString()
    {
        return "skip";
    }
}