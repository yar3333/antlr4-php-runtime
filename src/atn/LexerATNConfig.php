<?php

namespace Antlr4\Atn;

use Antlr4\Atn\States\DecisionState;

class LexerATNConfig extends ATNConfig
{
    /**
     * @var LexerActionExecutor
     */
    public $lexerActionExecutor;

    /**
     * @var bool
     */
    public $passedThroughNonGreedyDecision;

    function __construct(?object $params, ?self $config)
    {
        parent::__construct($params, $config);

        $this->lexerActionExecutor = $params->lexerActionExecutor ?? ($config->lexerActionExecutor ?? null);
        $this->passedThroughNonGreedyDecision = $config ? $this->checkNonGreedyDecision($config, $this->state) : false;
    }

    function equals($other) : bool
    {
        return $this === $other ||
            (
                $other instanceof self &&
                $this->passedThroughNonGreedyDecision === $other->passedThroughNonGreedyDecision &&
                ($this->lexerActionExecutor ? $this->lexerActionExecutor->equals($other->lexerActionExecutor) : !$other->lexerActionExecutor) &&
                parent::equals($other)
            );
    }

    function equalsForConfigSet($other) : bool
    {
        return $this->equals($other);
    }

    function checkNonGreedyDecision(object $source, $target) : bool
    {
        return $source->passedThroughNonGreedyDecision || (($target instanceof DecisionState) && $target->nonGreedy);
    }
}