<?php

namespace Antlr4\Error\Exceptions;

use Antlr4\Atn\ATNConfigSet;
use Antlr4\Parser;
use Antlr4\ParserRuleContext;
use Antlr4\Token;
use Antlr4\TokenStream;

// Indicates that the parser could not decide which of two or more paths
// to take based upon the remaining input. It tracks the starting token
// of the offending input and also knows where the parser was
// in the various paths when the error. Reported by reportNoViableAlternative()
class NoViableAltException extends RecognitionException
{
    /**
     * @var Token
     */
    public $startToken;

    /**
     * @var ATNConfigSet
     */
    public $deadEndConfigs;

    function __construct(Parser $recognizer, TokenStream $input=null, Token $startToken=null, Token $offendingToken=null, ATNConfigSet $deadEndConfigs=null, ParserRuleContext $ctx=null)
    {
        if (!$ctx) $ctx = $recognizer->getContext();
        if (!$offendingToken) $offendingToken = $recognizer->getCurrentToken();
        if (!$startToken) $startToken = $recognizer->getCurrentToken();
        if (!$input) $input = $recognizer->getInputStream();

        parent::__construct((object)['message' => "", 'recognizer' => $recognizer, 'input' => $input, 'ctx' => $ctx]);

        // Which configurations did we try at $input->index() that couldn't match $input->LT(1)?
        $this->deadEndConfigs = $deadEndConfigs;

        // The token object at the start index; the input stream might
        // not be buffering tokens so get a reference to it. (At the
        // time the error occurred, of course the stream needs to keep a
        // buffer all of the tokens but later we might not have access to those.)
        $this->startToken = $startToken;
        $this->offendingToken = $offendingToken;
    }
}