<?php

namespace Antlr4\Error\Listeners;

use Antlr4\Atn\ATNConfigSet;
use Antlr4\Dfa\DFA;
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Parser;
use Antlr4\Recognizer;
use Antlr4\Utils\BitSet;

class ProxyErrorListener implements ANTLRErrorListener
{
    /**
     * @var ANTLRErrorListener[]
     */
    public $delegates;

    function __construct(array $delegates)
    {
        $this->delegates = $delegates;
    }

   	function syntaxError(Recognizer $recognizer, ?object $offendingSymbol, int $line, int $charPositionInLine, string $msg, ?RecognitionException $e) : void
    {
        foreach ($this->delegates as $d)
        {
            $d->syntaxError($recognizer, $offendingSymbol, $line, $charPositionInLine, $msg, $e);
        }
    }

	function reportAmbiguity(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, bool $exact, ?BitSet $ambigAlts, ATNConfigSet $configs) : void
    {
        foreach ($this->delegates as $d)
        {
            $d->reportAmbiguity($recognizer, $dfa, $startIndex, $stopIndex, $exact, $ambigAlts, $configs);
        }
    }

	function reportAttemptingFullContext(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, ?BitSet $conflictingAlts, ATNConfigSet $configs) : void
    {
        foreach ($this->delegates as $d)
        {
            $d->reportAttemptingFullContext($recognizer, $dfa, $startIndex, $stopIndex, $conflictingAlts, $configs);
        }
    }

	function reportContextSensitivity(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, int $prediction, ATNConfigSet $configs) : void
    {
        foreach ($this->delegates as $d)
        {
            $d->reportContextSensitivity($recognizer, $dfa, $startIndex, $stopIndex, $prediction, $configs);
        }
    }
}
