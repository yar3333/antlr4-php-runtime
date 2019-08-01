<?php

namespace Antlr4;

interface TokenSource
{
    /**
     * Return a {@link Token} object from your input stream (usually a
     * {@link CharStream}). Do not fail/return upon lexing error; keep chewing
     * on the characters until you get a good one; errors are not passed through
     * to the parser.
     */
    public function nextToken() : Token;

    /**
     * Get the line number for the current position in the input stream. The
     * first line in the input is line 1.
     *
     * @return int The line number for the current position in the input stream, or
     * 0 if the current token source does not track line numbers.
     */
    public function getLine() : int;

    /**
     * Get the index into the current line for the current position in the input
     * stream. The first character on a line has position 0.
     *
     * @return int The line number for the current position in the input stream, or
     * -1 if the current token source does not track character positions.
     */
    public function getCharPositionInLine() : int;

    /**
     * Get the {@link CharStream} from which this token source is currently
     * providing tokens.
     *
     * @return CharStream The {@link CharStream} associated with the current position in
     * the input, or {@code null} if no input stream is available for the token
     * source.
     */
    public function getInputStream() : CharStream;

    /**
     * Gets the name of the underlying input source. This method returns a
     * non-null, non-empty string. If such a name is not known, this method
     * returns {@link IntStream#UNKNOWN_SOURCE_NAME}.
     */
    public function getSourceName() : string;

    /**
     * Set the {@link TokenFactory} this token source should use for creating
     * {@link Token} objects from the input.
     *
     * @param TokenFactory $factory The {@link TokenFactory} to use for creating tokens.
     */
    public function setTokenFactory(TokenFactory $factory) : void;

    /**
    * Gets the {@link TokenFactory} this token source is currently using for
    * creating {@link Token} objects from the input.
    *
    * @return TokenFactory The {@link TokenFactory} currently used by this token source.
    */
    public function getTokenFactory() : TokenFactory;
}
