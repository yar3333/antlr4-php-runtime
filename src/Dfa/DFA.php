<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
*/

namespace Antlr4\Dfa;

use Antlr4\Atn\ATNConfigSet;
use Antlr4\Atn\States\DecisionState;
use Antlr4\Atn\States\StarLoopEntryState;
use Antlr4\Utils\Set;
use Antlr4\Vocabulary;

class DFA
{
    /**
     * @var Set Map<DFAState, DFAState>();
     */
    public $states;

    /**
     * @var DecisionState
     */
    public $atnStartState;

    /**
     * @var int
     */
    public $decision;

    /**
     * @var DFAState
     */
    public $s0;

    /**
     * @var bool
     */
    public $precedenceDfa;

    function __construct(DecisionState $atnStartState, int $decision=0)
	{
        // From which ATN state did we create this DFA?
        $this->atnStartState = $atnStartState;
        $this->decision = $decision;

        // A set of all DFA states. Use {@link Map} so we can get old state back
        // ({@link Set} only allows you to see if it's there).
        $this->states = new Set();
        $this->s0 = null;

        // {@code true} if this DFA is for a precedence decision; otherwise,
        // {@code false}. This is the backing field for {@link //isPrecedenceDfa},
        // {@link //setPrecedenceDfa}.
        $this->precedenceDfa = false;

        if ($atnStartState instanceof StarLoopEntryState)
        {
            if ($atnStartState->isPrecedenceDecision)
            {
                $this->precedenceDfa = true;
                $precedenceState = new DFAState(null, new ATNConfigSet());
                $precedenceState->edges = [];
                $precedenceState->isAcceptState = false;
                $precedenceState->requiresFullContext = false;
                $this->s0 = $precedenceState;
            }
        }
    }

    function isPrecedenceDfa() : bool
    {
        return $this->precedenceDfa;
    }

    // Get the start state for a specific precedence value.
    //
    // @param precedence The current precedence.
    // @return The start state corresponding to the specified precedence, or
    // {@code null} if no start state exists for the specified precedence.
    //
    // @throws IllegalStateException if this is not a precedence DFA.
    // @see //isPrecedenceDfa()
    function getPrecedenceStartState(int $precedence) : ?DFAState
    {
        if (!$this->precedenceDfa) throw new \RuntimeException("Only precedence DFAs may contain a precedence start state.");

        // s0.edges is never null for a precedence DFA
        if ($precedence < 0 || $precedence >= count($this->s0->edges)) return null;

        return $this->s0->edges[$precedence] ?? null;
    }

    // Set the start state for a specific precedence value.
    //
    // @param precedence The current precedence.
    // @param startState The start state corresponding to the specified precedence.
    //
    // @throws IllegalStateException if this is not a precedence DFA.
    // @see //isPrecedenceDfa()
    //
    function setPrecedenceStartState($precedence, $startState) : void
    {
        if (!$this->precedenceDfa) throw new \RuntimeException("Only precedence DFAs may contain a precedence start state.");


        if ($precedence < 0) return;

        // synchronization on s0 here is ok. when the DFA is turned into a
        // precedence DFA, s0 will be initialized once and not updated again
        // s0.edges is never null for a precedence DFA
        $this->s0->edges[$precedence] = $startState;
    }

    /**
     * Return a list of all states in this DFA, ordered by state number.
     * @return DFAState[]
     */
    function getStates() : array
    {
        $list = $this->states->values();
        usort($list, function($a, $b)
        {
            return $a->stateNumber - $b->stateNumber;
        });
        return $list;
    }

    function __toString()
    {
        return $this->toString(null);
    }

	/**
	 * @param Vocabulary $vocabulary
	 * @return string
	 */
	public function toString(Vocabulary $vocabulary) : string
    {
		if ($this->s0 === null) return "";

		$serializer = new DFASerializer($this, $vocabulary);
		return (string)$serializer;
	}

    function toLexerString() : string
    {
        if ($this->s0 === null) return "";
        $serializer = new LexerDFASerializer($this);
        return (string)$serializer;
    }
}
