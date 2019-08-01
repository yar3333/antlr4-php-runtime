<?php

namespace Antlr4\Atn\Actions;

// Implements the {@code channel} lexer action by calling
// {@link Lexer//setChannel} with the assigned channel.
use Antlr4\Lexer;

class LexerChannelAction extends LexerAction
{
    /**
     * @var int
     */
    public $channel;

    // Constructs a new {@code channel} action with the specified channel value.
    // @param channel The channel value to pass to {@link Lexer//setChannel}.
    function __construct(int $channel)
    {
        parent::__construct(LexerActionType::CHANNEL);

        $this->channel = $channel;
    }

    // <p>This action is implemented by calling {@link Lexer//setChannel} with the value provided by {@link //getChannel}.</p>
    function execute(Lexer $lexer) : void
    {
        $lexer->_channel = $this->channel;
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->channel === $other->channel;
    }

    function __toString()
    {
        return "channel(" . $this->channel . ")";
    }
}