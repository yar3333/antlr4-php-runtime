<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

// A token has properties: text, type, line, character position in the line
// (so we can ignore tabs), token channel, index, and source from which
// we obtained this token.
use Antlr4\Utils\Pair;

class  Token
{
    const INVALID_TYPE = 0;

    // During lookahead operations, this "token" signifies we hit rule end ATN state
    // and did not follow it despite needing to.
    const EPSILON = -2;

    const MIN_USER_TOKEN_TYPE = 1;

    const EOF = -1;

    // All tokens go to the parser (unless skip() is called in that rule)
    // on a particular "channel". The parser tunes to a particular channel
    // so that whitespace etc... can go to the parser on a "hidden" channel.
    const DEFAULT_CHANNEL = 0;

    // Anything on different channel than DEFAULT_CHANNEL is not parsed
    // by parser.
    const HIDDEN_CHANNEL = 1;

    /**
     * @var Pair
     */
    public $source;
    
    /**
     * token type of the token
     * @var int
     */
    public $type;
    
    /**
     * The parser ignores everything not on DEFAULT_CHANNEL
     * @var int
     */
    public $channel;
    
    /**
     * optional; return -1 if not implemented.
     * @var int
     */
    public $start;
    
    /**
     * optional; return -1 if not implemented.
     * @var int
     */
    public $stop;
    
    /**
     * from 0..n-1 of the token object in the input stream
     * @var int
     */
    public $tokenIndex;
    
    /**
     * line=1..n of the 1st character
     * @var int
     */
    public $line;
    
    /**
     * beginning of the line at which it occurs, 0..n-1
     * @var int
     */
    public $charPositionInLine;

    /**
     * text of the token.
     * @var string
     */
    protected $_text;

    function __construct()
    {
        $this->source = null;
        $this->type = null;// token type of the token
        $this->channel = null;// The parser ignores everything not on DEFAULT_CHANNEL
        $this->start = null;// optional; return -1 if not implemented.
        $this->stop = null;// optional; return -1 if not implemented.
        $this->tokenIndex = -1;// from 0..n-1 of the token object in the input stream
        $this->line = null;// line=1..n of the 1st character
        $this->charPositionInLine = null;// beginning of the line at which it occurs, 0..n-1
        $this->_text = null;// text of the token.
    }

    // Explicitly set the text for this token. If {code text} is not
    // {@code null}, then {@link //getText} will return this value rather than
    // extracting the text from the input.
    function getText() : string { return $this->_text; }

    // @param text The explicit text of the token, or {@code null} if the text
    // should be obtained from the input along with the start and stop indexes
    // of the token.
    function setText(string $text) : void { $this->_text = $text; }

    function getTokenSource() : TokenSource
    {
        return $this->source->a;
    }

    function getInputStream() : CharStream
    {
        return $this->source->b;
    }
}
