<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

use Antlr4\Atn\ATNSimulator;
use Antlr4\Atn\LexerATNSimulator;
use Antlr4\Error\Exceptions\LexerNoViableAltException;
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Utils\Pair;
use Antlr4\Utils\Utils;

// A lexer is recognizer that draws input symbols from a character stream.
//  lexer grammars result in a subclass of this object. A Lexer object
//  uses simplified match() and error recovery mechanisms in the interest of speed.
abstract class Lexer extends Recognizer implements TokenSource
{
    const DEFAULT_MODE = 0;
    const MORE = -2;
    const SKIP = -3;

    const DEFAULT_TOKEN_CHANNEL = Token::DEFAULT_CHANNEL;
    const HIDDEN = Token::HIDDEN_CHANNEL;
    const MIN_CHAR_VALUE = 0x0000;
    const MAX_CHAR_VALUE = 0x10FFFF;

    /**
     * @var CharStream
     */
    protected $_input;

    /**
     * @var TokenFactory
     */
    protected $_factory;

    /**
     * @var Pair Pair<TokenSource, CharStream>
     */
    protected $_tokenFactorySourcePair;

    /**
     * @var LexerATNSimulator
     */
    protected $_interp;

    /**
     * @var Token
     */
    public $_token;

    public $_tokenStartCharIndex;

    public $_tokenStartLine;

    public $_tokenStartColumn;

    protected $_hitEOF;

    public $_channel;

    protected $_type;

    protected $_modeStack;

    /**
     * @var int
     */
    protected $_mode;

    protected $_text;

    function __construct(CharStream $input)
    {
        parent::__construct();

        $this->_input = $input;
        $this->_factory = CommonTokenFactory::DEFAULT();
        $this->_tokenFactorySourcePair = new Pair($this, $input);

        $this->_interp = null;// child classes must populate this

        // The goal of all lexer rules/methods is to create a token object.
        // this is an instance variable as multiple rules may collaborate to
        // create a single token. nextToken will return this object after
        // matching lexer rule(s). If you subclass to allow multiple token
        // emissions, then set this to the last token to be matched or
        // something nonnull so that the auto token emit mechanism will not
        // emit another token.
        $this->_token = null;

        // What character index in the stream did the current token start at?
        // Needed, for example, to get the text for current token. Set at
        // the start of nextToken.
        $this->_tokenStartCharIndex = -1;

        // The line on which the first character of the token resides///
        $this->_tokenStartLine = -1;

        // The character position of first character within the line///
        $this->_tokenStartColumn = -1;

        // Once we see EOF on char stream, next token will be EOF.
        // If you have DONE : EOF ; then you see DONE EOF.
        $this->_hitEOF = false;

        // The channel number for the current token///
        $this->_channel = Token::DEFAULT_CHANNEL;

        // The token type for the current token///
        $this->_type = Token::INVALID_TYPE;

        $this->_modeStack = [];
        $this->_mode = self::DEFAULT_MODE;

        // You can set the text for the current token to override what is in
        // the input char buffer. Use setText() or can set this instance var.
        $this->_text = null;
    }

    function reset() : void
    {
        // wack Lexer state variables
        if ($this->_input !== null)
        {
            $this->_input->seek(0);// rewind the input
        }
        $this->_token = null;
        $this->_type = Token::INVALID_TYPE;
        $this->_channel = Token::DEFAULT_CHANNEL;
        $this->_tokenStartCharIndex = -1;
        $this->_tokenStartColumn = -1;
        $this->_tokenStartLine = -1;
        $this->_text = null;

        $this->_hitEOF = false;
        $this->_mode = self::DEFAULT_MODE;
        $this->_modeStack = [];

        $this->_interp->reset();
    }

    // Return a token from this source; i.e., match a token on the char stream.
    function nextToken() : Token
    {
        if ($this->_input === null) throw new \RuntimeException("nextToken requires a non-null input stream.");

        // Mark start location in char stream so unbuffered streams are
        // guaranteed at least have text of current token
        $tokenStartMarker = $this->_input->mark();
        try
        {
            while (true)
            {
                if ($this->_hitEOF)
                {
                    $this->emitEOF();
                    return $this->_token;
                }
                $this->_token = null;
                $this->_channel = Token::DEFAULT_CHANNEL;
                $this->_tokenStartCharIndex = $this->_input->index();
                $this->_tokenStartColumn = $this->_interp->charPositionInLine;
                $this->_tokenStartLine = $this->_interp->line;
                $this->_text = null;
                $continueOuter = false;
                while (true)
                {
                    $this->_type = Token::INVALID_TYPE;
                    $ttype = self::SKIP;
                    try
                    {
                        $ttype = $this->_interp->match($this->_input, $this->_mode);
                    }
                    catch (\Throwable $e)
                    {
                        if ($e instanceof RecognitionException)
                        {
                            $this->notifyListeners($e);// report error
                            $this->recover($e);
                        }
                        else
                        {
                            //$console->log($e->stack);
                            throw $e;
                        }
                    }
                    if ($this->_input->LA(1) === Token::EOF)
                    {
                        $this->_hitEOF = true;
                    }
                    if ($this->_type === Token::INVALID_TYPE)
                    {
                        $this->_type = $ttype;
                    }
                    if ($this->_type === self::SKIP)
                    {
                        $continueOuter = true;
                        break;
                    }
                    if ($this->_type !== self::MORE)
                    {
                        break;
                    }
                }

                if ($continueOuter)continue;

                if ($this->_token === null) $this->emit();

                return $this->_token;
            }
        }
        finally
        {
            // make sure we release marker after match or
            // unbuffered char stream will keep buffering
            $this->_input->release($tokenStartMarker);
        }

        return null;
    }

    // Instruct the lexer to skip creating a token for current lexer rule
    // and look for another token. nextToken() knows to keep looking when
    // a lexer rule finishes with token set to SKIP_TOKEN. Recall that
    // if token==null at end of any token rule, it creates one for you
    // and emits it.
    // /
    function skip() : void
    {
        $this->_type = self::SKIP;
    }

    function more() : void
    {
        $this->_type = self::MORE;
    }

    function mode(int $m) : void
    {
        $this->_mode = $m;
    }

    function pushMode(int $m) : void
    {
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        if ($this->_interp->debug)
        {
            //$console->log("pushMode " . $m);
        }
        $this->_modeStack[] = $this->_mode;
        $this->mode($m);
    }

    function popMode() : int
    {
        if (count($this->_modeStack) === 0)
        {
            throw new \RuntimeException("Empty Stack");
        }
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        if ($this->_interp->debug)
        {
            //$console->log("popMode back to " . $this->_modeStack->slice(0, -1));
        }
        $this->mode(array_pop($this->_modeStack));
        return $this->_mode;
    }

    // Set the char stream and reset the lexer
    function getInputStream() : CharStream { return $this->_input; }
    function setInputStream($input) : void
    {
            $this->_input = null;
            $this->_tokenFactorySourcePair = new Pair($this, $this->_input);
            $this->reset();
            $this->_input = $input;
            $this->_tokenFactorySourcePair = new Pair($this, $this->_input);
    }

    function getSourceName() : string { return $this->_input->getSourceName(); }

    // By default does not support multiple emits per nextToken invocation
    // for efficiency reasons. Subclass and override this method, nextToken,
    // and getToken (to push tokens into a list and pull from that list
    // rather than a single variable as this implementation does).
    // /
    function emitToken($token) : void
    {
        $this->_token = $token;
    }

    // The standard method called to automatically emit a token at the
    // outermost lexical rule. The token object should point into the
    // char buffer start..stop. If there is a text override in 'text',
    // use that to set the token's text. Override this method to emit
    // custom Token objects or provide a new factory.
    // /
    function emit() : Token
    {
        $t = $this->_factory->createEx
        (
            $this->_tokenFactorySourcePair,
            $this->_type,
            $this->_text,
            $this->_channel,
            $this->_tokenStartCharIndex,
            $this->getCharIndex() - 1,
            $this->_tokenStartLine,
            $this->_tokenStartColumn
        );
        $this->emitToken($t);
        return $t;
    }

    function emitEOF()
    {
        $cpos = $this->getColumn();
        $lpos = $this->getLine();
        $eof = $this->_factory->createEx
        (
            $this->_tokenFactorySourcePair,
            Token::EOF,
            null,
            Token::DEFAULT_CHANNEL,
            $this->_input->index(),
            $this->_input->index() - 1,
            $lpos,
            $cpos
        );
        $this->emitToken($eof);
        return $eof;
    }

    // TODO: LOOKS LIKE ERROR: function getType() { return $this->type; }
    // FIXED `type` TO `_type`
    function getType() : int { return $this->_type; }
    function setType($type) : void { $this->_type = $type; }

    function getLine() : int { return $this->_interp->line; }
    function setLine($line) : void { $this->_interp->line = $line; }

    function getColumn() : int { return $this->_interp->charPositionInLine; }
    function setColumn(int $column) : void { $this->_interp->charPositionInLine = $column; }

    // What is the index of the current character of lookahead?
    function getCharIndex() : int
    {
        return $this->_input->index();
    }

    // Return the text matched so far for the current token or any text override.
    // Set the complete text of this token; it wipes any previous changes to the text.
    function getText() : string
    {
        return $this->_text ?? $this->_interp->getText($this->_input);
    }
    function setText(string $text) : void
    {
        $this->_text = $text;
    }

    // Return a list of all Token objects in input char stream.
    // Forces load of all tokens. Does not include EOF token.
    function getAllTokens() : array
    {
        $tokens = [];
        $t = $this->nextToken();
        while ($t && $t->type !== Token::EOF)
        {
            $tokens[] = $t;
            $t = $this->nextToken();
        }
        return $tokens;
    }

    function notifyListeners($e) : void
    {
        $start = $this->_tokenStartCharIndex;
        $stop = $this->_input->index();
        $text = $this->_input->getText($start, $stop);
        $msg = "token recognition error at: '" . $this->getErrorDisplay($text) . "'";
        $listener = $this->getErrorListenerDispatch();
        $listener->syntaxError($this, null, $this->_tokenStartLine, $this->_tokenStartColumn, $msg, $e);
    }

    function getErrorDisplay($s)
    {
        // TODO: ??????????
        /*$d = [];
        for ($i = 0; $i < $s->length; $i++)
        {
            $d[] = $s[$i];
        }
        return $d->join('');*/
        return $s;
    }

    function getErrorDisplayForChar($c)
    {
        if (Utils::charCodeAt($c, 0) === Token::EOF)
        {
            return "<EOF>";
        }
        else if ($c === '\n') {
            return "\\n";
        }
        else if ($c === '\t') {
            return "\\t";
        }
        else if ($c === '\r') {
            return "\\r";
        }
        else
        {
            return $c;
        }
    }

    function getCharErrorDisplay($c) : string
    {
        return "'" . $this->getErrorDisplayForChar($c) . "'";
    }

    // Lexers can normally match any char in it's vocabulary after matching
    // a token, so do the easy thing and just kill a character and hope
    // it all works out. You can instead use the rule invocation stack
    // to do sophisticated error recovery if you are in a fragment rule.
    // /
    function recover($re) : void
    {
        if ($this->_input->LA(1) !== Token::EOF)
        {
            if ($re instanceof LexerNoViableAltException)
            {
                // skip a char and try again
                $this->_interp->consume($this->_input);
            }
            else
            {
                // TODO: Do we lose character or line position information?
                $this->_input->consume();
            }
        }
    }

    function getTokenFactory() : TokenFactory { return $this->_factory; }
    function setTokenFactory(TokenFactory $factory) : void { $this->_factory = $factory; }

    /**
     * @return LexerATNSimulator
     */
	function getInterpreter() : ?ATNSimulator { return parent::getInterpreter(); }

    function getCharPositionInLine() : int { return $this->getInterpreter()->charPositionInLine; }
}
