<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\States\ATNState;

final class ActionTransition extends Transition
{
    public $serializationType = Transition::ACTION;
    public $isEpsilon = true;

    /**
     * @var int
     */
    public $ruleIndex;

    /**
     * @var int
     */
    public $actionIndex;

    /**
     * @var bool
     */
    public $isCtxDependent;

    function __construct(ATNState $target, int $ruleIndex, int $actionIndex=null, bool $isCtxDependent=null)
    {
        parent::__construct($target);

        $this->ruleIndex = $ruleIndex;
        $this->actionIndex = $actionIndex ?? -1;
        $this->isCtxDependent = $isCtxDependent ?? false;
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }

    function __toString()
    {
        return "action_" . $this->ruleIndex . ":" . $this->actionIndex;
    }
}