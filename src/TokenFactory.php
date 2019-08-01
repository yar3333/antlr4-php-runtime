<?php

namespace Antlr4;

use Antlr4\Utils\Pair;

interface TokenFactory
{
	/** This is the method used to create tokens in the lexer and in the
	 *  error handling strategy. If text!=null, than the start and stop positions
	 *  are wiped to -1 in the text override is set in the CommonToken.
     * @param Pair<TokenSource, CharStream> $source
     * @param int $type
     * @param string $text
     * @param int $channel
     * @param int $start
     * @param int $stop
     * @param int $line
     * @param int $charPositionInLine
     * @return Token
     */
	function createEx(Pair $source, int $type, string $text, int $channel, int $start, int $stop, int $line, int $charPositionInLine) : Token;

	/** Generically useful
     * @param int $type
     * @param string $text
     * @return Token
     */
	function create(int $type, string $text) : Token;
}