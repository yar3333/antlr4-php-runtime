<?php

namespace Antlr4\Error\Listeners;

use Antlr4\Atn\ATNConfigSet;
use Antlr4\Dfa\DFA;
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Parser;
use Antlr4\Recognizer;
use Antlr4\Utils\BitSet;

/**
 * Provides an empty default implementation of {@link ANTLRErrorListener}. The
 * default implementation of each method does nothing, but can be overridden as necessary.
 */
class BaseErrorListener implements ANTLRErrorListener
{
	/**
	 * @param Recognizer $recognizer
	 * @param object $offendingSymbol
	 * @param int $line
	 * @param int $charPositionInLine
	 * @param string $msg
	 * @param RecognitionException $e
	 * @return void
	 */
	function syntaxError(Recognizer $recognizer, ?object $offendingSymbol, int $line, int $charPositionInLine, string $msg, ?RecognitionException $e) : void {}

	/**
	 * @param Parser $recognizer
	 * @param DFA $dfa
	 * @param int $startIndex
	 * @param int $stopIndex
	 * @param bool $exact
	 * @param BitSet $ambigAlts
	 * @param ATNConfigSet $configs
	 * @return void
	 */
	function reportAmbiguity(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, bool $exact, ?BitSet $ambigAlts, ATNConfigSet $configs) : void {}

	/**
	 * @param Parser $recognizer
	 * @param DFA $dfa
	 * @param int $startIndex
	 * @param int $stopIndex
	 * @param BitSet $conflictingAlts
	 * @param ATNConfigSet $configs
	 * @return void
	 */
	function reportAttemptingFullContext(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, ?BitSet $conflictingAlts, ATNConfigSet $configs) : void {}

	/**
	 * @param Parser $recognizer
	 * @param DFA $dfa
	 * @param int $startIndex
	 * @param int $stopIndex
	 * @param int $prediction
	 * @param ATNConfigSet $configs
	 * @return void
	 */
	function reportContextSensitivity(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, int $prediction, ATNConfigSet $configs) : void {}
}
