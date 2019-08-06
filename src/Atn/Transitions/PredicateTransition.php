<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\SemanticContexts\SemanticContextPredicate;
use Antlr4\Atn\States\ATNState;

class PredicateTransition extends AbstractPredicateTransition
{
    public $serializationType = Transition::PREDICATE;
    public $isEpsilon = true;

    /**
     * @var int
     */
    public $ruleIndex;

    /**
     * @var int
     */
    public $predIndex;

    /**
     * @var bool
     */
    public $isCtxDependent;

    function __construct(ATNState $target, int $ruleIndex, int $predIndex, bool $isCtxDependent)
    {
        parent::__construct($target);

        $this->ruleIndex = $ruleIndex;
        $this->predIndex = $predIndex;
        $this->isCtxDependent = $isCtxDependent;// e.g., $i ref in pred
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }

    function getPredicate() : SemanticContextPredicate
    {
        return new SemanticContextPredicate($this->ruleIndex, $this->predIndex, $this->isCtxDependent);
    }

    function __toString()
    {
        return "pred_" . $this->ruleIndex . ":" . $this->predIndex;
    }
}