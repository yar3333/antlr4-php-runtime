<?php

namespace Antlr4\Error\Listeners;

use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Recognizer;

class ConsoleErrorListener extends BaseErrorListener
{
    private static $_INSTANCE;
    static function INSTANCE(): ConsoleErrorListener { return self::$_INSTANCE ?? (self::$_INSTANCE = new self()); }

    // {@inheritDoc}
    //
    // <p>
    // This implementation prints messages to {@link System//err} containing the
    // values of {@code line}, {@code charPositionInLine}, and {@code msg} using
    // the following format.</p>
    //
    // <pre>
    // line <em>line</em>:<em>charPositionInLine</em> <em>msg</em>
    // </pre>
	function syntaxError(Recognizer $recognizer, ?object $offendingSymbol, int $line, int $charPositionInLine, string $msg, ?RecognitionException $e) : void
    {
        //$console->error("line " . $line . ":" . $charPositionInLine . " " . $msg);
    }
}