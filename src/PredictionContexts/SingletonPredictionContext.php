<?php

namespace Antlr4\PredictionContexts;

// Used to cache {@link PredictionContext} objects. Its used for the shared
// context cash associated with contexts in DFA states. This cache
// can be used for both lexers and parsers.
class SingletonPredictionContext extends PredictionContext
{
    /**
     * @var PredictionContext
     */
    public $parentCtx;

    /**
     * @var int
     */
    public $returnState;

    function __construct(?PredictionContext $parent, int $returnState)
    {
        parent::__construct();

        $this->parentCtx = $parent;
        $this->returnState = $returnState;
    }

    static function create(?PredictionContext $parent, int $returnState) : PredictionContext
    {
        // someone can pass in the bits of an array ctx that mean $
        if ($returnState === PredictionContext::EMPTY_RETURN_STATE && !$parent) return PredictionContext::EMPTY();
        return new SingletonPredictionContext($parent, $returnState);
    }

    function getLength(): int
    {
        return 1;
    }

    function getParent(int $index=null): PredictionContext
    {
        return $this->parentCtx;
    }

    function getReturnState(int $index): int
    {
        return $this->returnState;
    }

    /**
     * @return int[]
     */
    function getReturnStates(): array
    {
        return [ $this->returnState ];
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        if ($this->returnState !== $other->returnState) return false;
        if (!$this->parentCtx) return $other->parentCtx === null;
        return $this->parentCtx->equals($other->parentCtx);
    }

    function __toString()
    {
        $up = $this->parentCtx === null ? "" : (string)$this->parentCtx;
        if ($up === "") {
            if ($this->returnState === PredictionContext::EMPTY_RETURN_STATE) {
                return "$";
            } else {
                return "" . $this->returnState;
            }
        } else {
            return "" . $this->returnState . " " . $up;
        }
    }
}
