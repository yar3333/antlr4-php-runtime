<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

class LexerMoreAction extends LexerAction
{
    function __construct()
    {
        parent::__construct(LexerActionType::MORE);
    }

    private static $_INSTANCE;
    static function INSTANCE() : LexerMoreAction { return self::$_INSTANCE ?? (self::$_INSTANCE = new LexerMoreAction()); }

    // <p>This action is implemented by calling {@link Lexer//popMode}.</p>
    function execute(Lexer $lexer) : void
    {
        $lexer->more();
    }

    function __toString()
    {
        return "more";
    }
}