<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

//  Implements the {@code type} lexer action by calling {@link Lexer//setType} with the assigned type.
class LexerTypeAction extends LexerAction
{
    public $type;

    function __construct($type)
    {
        parent::__construct(LexerActionType::TYPE);
        $this->type = $type;
    }

    function execute(Lexer $lexer) : void
    {
        $lexer->setType($this->type);
    }

    function equals(LexerAction $other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->type === $other->type;
    }

    function __toString()
    {
        return "type(" . $this->type . ")";
    }
}