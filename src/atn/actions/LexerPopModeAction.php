<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

class LexerPopModeAction extends LexerAction
{
    // Implements the {@code popMode} lexer action by calling {@link Lexer//popMode}.
    //
    // <p>The {@code popMode} command does not have any parameters, so this action is
    // implemented as a singleton instance exposed by {@link //INSTANCE}.</p>
    function __construct()
    {
        parent::__construct(LexerActionType::POP_MODE);
    }

    private static $_INSTANCE;
    public static function INSTANCE() : LexerPopModeAction { return self::$_INSTANCE ?? (self::$_INSTANCE = new LexerPopModeAction()); }

    // <p>This action is implemented by calling {@link Lexer//popMode}.</p>
    function execute(Lexer $lexer) : void
    {
        $lexer->popMode();
    }

    function __toString()
    {
        return "popMode";
    }
}