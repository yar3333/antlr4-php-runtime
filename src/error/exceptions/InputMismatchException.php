<?php

namespace Antlr4\Error\Exceptions;

use Antlr4\Parser;

// This signifies any kind of mismatched input exceptions such as
// when the current input does not match the expected token.
class InputMismatchException extends RecognitionException
{
    function __construct(Parser $recognizer)
    {
        parent::__construct((object)['message' => "", 'recognizer' => $recognizer, 'input' => $recognizer->getInputStream(), 'ctx' => $recognizer->getContext()]);
        $this->offendingToken = $recognizer->getCurrentToken();
    }
}