<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

// Executes a custom lexer action by calling {@link Recognizer//action} with the
// rule and action indexes assigned to the custom action. The implementation of
// a custom action is added to the generated code for the lexer in an override
// of {@link Recognizer//action} when the grammar is compiled.
//
// <p>This class may represent embedded actions created with the <code>{...}</code>
// syntax in ANTLR 4, as well as actions created for lexer commands where the
// command argument could not be evaluated when the grammar was compiled.</p>


// Constructs a custom lexer action with the specified rule and action
// indexes.
//
// @param ruleIndex The rule index to use for calls to
// {@link Recognizer//action}.
// @param actionIndex The action index to use for calls to
// {@link Recognizer//action}.
class LexerCustomAction extends LexerAction
{
    public $ruleIndex;
    public $actionIndex;

    function __construct($ruleIndex, $actionIndex)
    {
        parent::__construct(LexerActionType::CUSTOM);

        $this->ruleIndex = $ruleIndex;
        $this->actionIndex = $actionIndex;
        $this->isPositionDependent = true;
    }

    // <p>Custom actions are implemented by calling {@link Lexer//action} with the
    // appropriate rule and action indexes.</p>
    function execute(Lexer $lexer) : void
    {
        $lexer->action(null, $this->ruleIndex, $this->actionIndex);
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->ruleIndex === $other->ruleIndex && $this->actionIndex === $other->actionIndex;
    }
}