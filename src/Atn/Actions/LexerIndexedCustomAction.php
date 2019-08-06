<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

// This implementation of {@link LexerAction} is used for tracking input offsets
// for position-dependent actions within a {@link LexerActionExecutor}.
//
// <p>This action is not serialized as part of the ATN, and is only required for
// position-dependent lexer actions which appear at a location other than the
// end of a rule. For more information about DFA optimizations employed for
// lexer actions, see {@link LexerActionExecutor//append} and
// {@link LexerActionExecutor//fixOffsetBeforeMatch}.</p>
class LexerIndexedCustomAction extends LexerAction
{
    /**
     * @var int
     */
    public $offset;

    /**
     * @var LexerAction
     */
    public $action;

    // Constructs a new indexed custom action by associating a character offset
    // with a {@link LexerAction}.
    //
    // <p>Note: This class is only required for lexer actions for which
    // {@link LexerAction//isPositionDependent} returns {@code true}.</p>
    //
    // @param offset The offset into the input {@link CharStream}, relative to
    // the token start index, at which the specified lexer action should be
    // executed.
    // @param action The lexer action to execute at a particular offset in the
    // input {@link CharStream}.
    function __construct(int $offset, LexerAction $action)
    {
        parent::__construct($action->actionType);

        $this->offset = $offset;
        $this->action = $action;
        $this->isPositionDependent = true;
    }

    // <p>This method calls {@link //execute} on the result of {@link //getAction} using the provided {@code lexer}.</p>
    function execute(Lexer $lexer) : void
    {
        // assume the input stream position was properly set by the calling code
        $this->action->execute($lexer);
    }

    function equals(LexerAction $other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->offset === $other->offset && $this->action === $other->action;
    }
}