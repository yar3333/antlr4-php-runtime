<?php

namespace Antlr4\PredictionContexts;

class EmptyPredictionContext extends SingletonPredictionContext
{
    function __construct()
    {
        parent::__construct(null, PredictionContext::EMPTY_RETURN_STATE);
    }

    function isEmpty() : bool
    {
        return true;
    }

    function getParent(int $index=null) : PredictionContext
    {
        return null;
    }

    function equals($other) : bool
    {
        return $this === $other;
    }

    function __toString()
    {
        return "$";
    }
}