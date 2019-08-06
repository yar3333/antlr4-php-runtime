<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\States\ATNState;
use Antlr4\IntervalSet;

/** An ATN transition between any two ATN states.  Subclasses define
 *  atom, set, epsilon, action, predicate, rule transitions.
 *
 *  <p>This is a one way link.  It emanates from a state (usually via a list of
 *  transitions) and has a target state.</p>
 *
 *  <p>Since we never have to change the ATN transitions once we construct it,
 *  we can fix these transitions as specific classes. The DFA transitions
 *  on the other hand need to update the labels as it adds transitions to
 *  the states. We'll use the term Edge for the DFA to distinguish them from
 *  ATN transitions.</p>
 */
abstract class Transition
{
    // constants for serialization
    const EPSILON = 1;
    const RANGE = 2;
    const RULE = 3;
    const PREDICATE = 4;// e.g., {isType(input.LT(1))}?
    const ATOM = 5;
    const ACTION = 6;
    const SET = 7;// ~(A|B) or ~atom, wildcard, which convert to next 2
    const NOT_SET = 8;
    const WILDCARD = 9;
    const PRECEDENCE = 10;

    const serializationNames = [
        "INVALID",
        "EPSILON",
        "RANGE",
        "RULE",
        "PREDICATE",
        "ATOM",
        "ACTION",
        "SET",
        "NOT_SET",
        "WILDCARD",
        "PRECEDENCE"
    ];

    const serializationTypes =
    [
        'EpsilonTransition' => Transition::EPSILON,
        'RangeTransition' => Transition::RANGE,
        'RuleTransition' => Transition::RULE,
        'PredicateTransition' => Transition::PREDICATE,
        'AtomTransition' => Transition::ATOM,
        'ActionTransition' => Transition::ACTION,
        'SetTransition' => Transition::SET,
        'NotSetTransition' => Transition::NOT_SET,
        'WildcardTransition' => Transition::WILDCARD,
        'PrecedencePredicateTransition' => Transition::PRECEDENCE
    ];

    /**
     * @var int
     */
    public $serializationType;

    /**
     * @var bool
     */
    public $isEpsilon = false;

    /**
     * @var ATNState
     */
    public $target;


    function __construct(ATNState $target)
    {
        $this->target = $target;
    }

    function label() : IntervalSet { return null; }

    abstract function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool;
}
