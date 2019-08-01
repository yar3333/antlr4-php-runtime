<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

/**
 * This class extends {@link BufferedTokenStream} with functionality to filter
 * token streams to tokens on a particular channel (tokens where
 * {@link Token#getChannel} returns a particular value).
 *
 * <p>
 * This token stream provides access to all tokens by index or when calling
 * methods like {@link #getText}. The channel filtering is only used for code
 * accessing tokens via the lookahead methods {@link #LA}, {@link #LT}, and
 * {@link #LB}.</p>
 *
 * <p>
 * By default, tokens are placed on the default channel
 * ({@link Token#DEFAULT_CHANNEL}), but may be reassigned by using the
 * {@code ->channel(HIDDEN)} lexer command, or by using an embedded action to
 * call {@link Lexer#setChannel}.
 * </p>
 *
 * <p>
 * Note: lexer rules which use the {@code ->skip} lexer command or call
 * {@link Lexer#skip} do not produce tokens at all, so input text matched by
 * such a rule will not be available as part of the token stream, regardless of
 * channel.</p>we
 */
class CommonTokenStream extends BufferedTokenStream
{
    /**
     * @var int
     */
    public $channel;

    function __construct(TokenSource $tokenSource, int $channel=Token::DEFAULT_CHANNEL)
    {
        parent::__construct($tokenSource);
        $this->channel = $channel;
    }

    function adjustSeekIndex($i)
    {
        return $this->nextTokenOnChannel($i, $this->channel);
    }

    function LB($k) : ?Token
    {
        if ($k === 0 || $this->index-$k < 0) return null;

        $i = $this->index;
        $n = 1;
        // find k good tokens looking backwards
        while ($n <= $k)
        {
            // skip off-channel tokens
            $i = $this->previousTokenOnChannel($i - 1, $this->channel);
            $n++;
        }
        if ($i < 0)
        {
            return null;
        }
        return $this->tokens[$i];
    }

    function LT(int $k) : ?Token
    {
        $this->lazyInit();
        if ($k === 0) return null;

        if ($k < 0)
        {
            return $this->LB(-$k);
        }
        $i = $this->index;
        $n = 1;// we know tokens[pos] is a good one
        // find k good tokens
        while ($n < $k)
        {
            // skip off-channel tokens, but make sure to not look past EOF
            if ($this->sync($i + 1))
            {
                $i = $this->nextTokenOnChannel($i + 1, $this->channel);
            }
            $n++;
        }
        return $this->tokens[$i];
    }

    // Count EOF just once.
    function getNumberOfOnChannelTokens() : int
    {
        $n = 0;
        $this->fill();
        foreach ($this->tokens as $t)
        {
            if ($t->channel === $this->channel) $n++;
            if ($t->type === Token::EOF) break;
        }
        return $n;
    }
}