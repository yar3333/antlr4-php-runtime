<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\States\ATNState;
use Antlr4\Atn\States\RuleStartState;

class RuleTransition extends Transition
{
    public $serializationType = Transition::RULE;
    public $isEpsilon = true;

    public $ruleIndex;
    public $precedence;
    public $followState;

    function __construct(RuleStartState $ruleStart, int $ruleIndex, int $precedence, ATNState $followState)
    {
        parent::__construct($ruleStart);

        $this->ruleIndex = $ruleIndex;// ptr to the rule definition object for this rule ref
        $this->precedence = $precedence;
        $this->followState = $followState;// what node to begin computations following ref to rule
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }
}