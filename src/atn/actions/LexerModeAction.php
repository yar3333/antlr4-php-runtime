<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;
use Antlr4\Utils\Hash;

class LexerModeAction extends LexerAction
{
    public $mode;

    function __construct($mode)
    {
        parent::__construct(LexerActionType::MODE);

        $this->mode = $mode;
    }

    // <p>This action is implemented by calling {@link Lexer//mode} with the
    // value provided by {@link //getMode}.</p>
    function execute(Lexer $lexer) : void
    {
        $lexer->mode($this->mode);
    }

    function updateHashCode(Hash $hash) : void
    {
        $hash->update($this->actionType, $this->mode);
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->mode === $other->mode;
    }

    function __toString()
    {
        return "mode(" . $this->mode . ")";
    }
}