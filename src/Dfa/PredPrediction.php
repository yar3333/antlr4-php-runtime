<?php

namespace Antlr4\Dfa;

// Map a predicate to a predicted alternative.
class PredPrediction
{
    /**
     * @var
     */
    public $pred;

    /**
     * @var
     */
    public $alt;

    function __construct($pred, $alt)
    {
        $this->pred = $pred;
        $this->alt = $alt;
    }

    function __toString()
    {
        return "(" . $this->pred . ", " . $this->alt . ")";
    }
}