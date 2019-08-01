<?php

namespace Antlr4\Error\Exceptions;

use Antlr4\Atn\Transitions\PredicateTransition;
use Antlr4\Parser;

// A semantic predicate failed during validation. Validation of predicates
// occurs when normally parsing the alternative just like matching a token.
// Disambiguating predicate evaluation occurs when we test a predicate during
// prediction.
class FailedPredicateException extends RecognitionException
{
    /**
     * @var int
     */
    public $ruleIndex;

    /**
     * @var int
     */
    public $predicateIndex;

    /**
     * @var string
     */
    public $predicate;

    function __construct(Parser $recognizer, string $predicate, string $message=null)
    {
        parent::__construct((object)[
            'message' => $this->formatMessage($predicate, $message),
            'recognizer' => $recognizer,
            'input' => $recognizer->getInputStream(),
            'ctx' => $recognizer->getContext()
        ]);
        $s = $recognizer->getInterpreter()->atn->states[$recognizer->getState()];
        $trans = $s->transitions[0];
        if ($trans instanceof PredicateTransition)
        {
            $this->ruleIndex = $trans->ruleIndex;
            $this->predicateIndex = $trans->predIndex;
        }
        else
        {
            $this->ruleIndex = 0;
            $this->predicateIndex = 0;
        }
        $this->predicate = $predicate;
        $this->offendingToken = $recognizer->getCurrentToken();
    }

    function formatMessage(string $predicate, ?string $message)
    {
        if ($message !== null) return $message;
        return "failed predicate: {" . $predicate . "}?";
    }
}