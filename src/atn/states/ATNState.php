<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn\States;

use Antlr4\Atn\Transitions\Transition;

// The following images show the relation of states and
// {@link ATNState//transitions} for various grammar constructs.
//
// <ul>
// <li>Solid edges marked with an &//0949; indicate a required
// {@link EpsilonTransition}.</li>
//
// <li>Dashed edges indicate locations where any transition derived from
// {@link Transition} might appear.</li>
//
// <li>Dashed nodes are place holders for either a sequence of linked
// {@link BasicState} states or the inclusion of a block representing a nested
// construct in one of the forms below.</li>
//
// <li>Nodes showing multiple outgoing alternatives with a {@code ...} support
// any number of alternatives (one or more). Nodes without the {@code ...} only
// support the exact number of alternatives shown in the diagram.</li>
// </ul>
//
// <h2>Basic Blocks</h2>
// <h3>Rule</h3>
// <embed src="images/Rule.svg" type="image/svg+xml"/>
// <h3>Block of 1 or more alternatives</h3>
// <embed src="images/Block.svg" type="image/svg+xml"/>
// <h2>Greedy Loops</h2>
// <h3>Greedy Closure: {@code (...)*}</h3>
// <embed src="images/ClosureGreedy.svg" type="image/svg+xml"/>
// <h3>Greedy Positive Closure: {@code (...)+}</h3>
// <embed src="images/PositiveClosureGreedy.svg" type="image/svg+xml"/>
// <h3>Greedy Optional: {@code (...)?}</h3>
// <embed src="images/OptionalGreedy.svg" type="image/svg+xml"/>
// <h2>Non-Greedy Loops</h2>
// <h3>Non-Greedy Closure: {@code (...)*?}</h3>
// <embed src="images/ClosureNonGreedy.svg" type="image/svg+xml"/>
// <h3>Non-Greedy Positive Closure: {@code (...)+?}</h3>
// <embed src="images/PositiveClosureNonGreedy.svg" type="image/svg+xml"/>
// <h3>Non-Greedy Optional: {@code (...)??}</h3>
// <embed src="images/OptionalNonGreedy.svg" type="image/svg+xml"/>
class ATNState
{
    // constants for serialization
    const INVALID_TYPE = 0;
    const BASIC = 1;
    const RULE_START = 2;
    const BLOCK_START = 3;
    const PLUS_BLOCK_START = 4;
    const STAR_BLOCK_START = 5;
    const TOKEN_START = 6;
    const RULE_STOP = 7;
    const BLOCK_END = 8;
    const STAR_LOOP_BACK = 9;
    const STAR_LOOP_ENTRY = 10;
    const PLUS_LOOP_BACK = 11;
    const LOOP_END = 12;

    const INVALID_STATE_NUMBER = -1;

    const serializationNames = [
        "INVALID",
        "BASIC",
        "RULE_START",
        "BLOCK_START",
        "PLUS_BLOCK_START",
        "STAR_BLOCK_START",
        "TOKEN_START",
        "RULE_STOP",
        "BLOCK_END",
        "STAR_LOOP_BACK",
        "STAR_LOOP_ENTRY",
        "PLUS_LOOP_BACK",
        "LOOP_END"
    ];

    public $atn;

    /**
     * @var int
     */
    public $stateNumber;

    /**
     * @var int
     */
    public $stateType;

    /**
     * @var int
     */
    public $ruleIndex;

    /**
     * @var bool
     */
    public $epsilonOnlyTransitions = false;

    /**
     * @var null
     */
    public $nextTokenWithinRule;

    /**
     * @var Transition[]
     */
    public $transitions;

    function __construct()
    {
        // Which ATN are we in?
        $this->atn = null;
        $this->stateNumber = self::INVALID_STATE_NUMBER;
        $this->stateType = null;
        $this->ruleIndex = 0;// at runtime, we don't have Rule objects

        // Track the transitions emanating from this ATN state.
        $this->transitions = [];

        // Used to cache lookahead during parsing, not used during construction
        $this->nextTokenWithinRule = null;
    }

    function __toString()
    {
        return (string)$this->stateNumber;
    }

    function equals($other) : bool
    {
        return $other instanceof self && $this->stateNumber === $other->stateNumber;
    }

    function isNonGreedyExitState() : bool
    {
        return false;
    }

    function addTransition($trans, $index=null) : void
    {
        $index = $index ?? -1;

        if (!$this->transitions)
        {
            $this->epsilonOnlyTransitions = $trans->isEpsilon;
        }
        else if ($this->epsilonOnlyTransitions !== $trans->isEpsilon)
        {
            $this->epsilonOnlyTransitions = false;
        }

        if ($index === -1)
        {
            $this->transitions[] = $trans;
        }
        else
        {
            array_splice($this->transitions, $index, 1, $trans);
        }
    }

    function onlyHasEpsilonTransitions() : bool
    {
        return $this->epsilonOnlyTransitions;
    }
}
