<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Error\Listeners;

use Antlr4\Atn\ATNConfigSet;
use Antlr4\Dfa\DFA;
use Antlr4\Parser;
use Antlr4\Utils\BitSet;
use Antlr4\Interval;

// This implementation of {@link ANTLRErrorListener} can be used to identify
// certain potential correctness and performance problems in grammars. "Reports"
// are made by calling {@link Parser//notifyErrorListeners} with the appropriate
// message.
//
// <ul>
// <li><b>Ambiguities</b>: These are cases where more than one path through the
// grammar can match the input.</li>
// <li><b>Weak context sensitivity</b>: These are cases where full-context
// prediction resolved an SLL conflict to a unique alternative which equaled the
// minimum alternative of the SLL conflict.</li>
// <li><b>Strong (forced) context sensitivity</b>: These are cases where the
// full-context prediction resolved an SLL conflict to a unique alternative,
// <em>and</em> the minimum alternative of the SLL conflict was found to not be
// a truly viable alternative. Two-stage parsing cannot be used for inputs where
// this situation occurs.</li>
// </ul>
class DiagnosticErrorListener extends BaseErrorListener
{
    /**
     * @var bool
     */
    public $exactOnly;

    function __construct(bool $exactOnly=true)
    {
        // whether all ambiguities or only exact ambiguities are reported.
        $this->exactOnly = $exactOnly;
    }

	function reportAmbiguity(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, bool $exact, ?BitSet $ambigAlts, ATNConfigSet $configs) : void
	{
        if ($this->exactOnly && !$exact) return;

        $msg = "reportAmbiguity d=" . $this->getDecisionDescription($recognizer, $dfa) .
                ": ambigAlts=" . $this->getConflictingAlts($ambigAlts, $configs) .
                ", input='" . $recognizer->getTokenStream()->getTextByInterval(new Interval($startIndex, $stopIndex)) . "'";

        $recognizer->notifyErrorListeners($msg);
    }

	function reportAttemptingFullContext(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, ?BitSet $conflictingAlts, ATNConfigSet $configs) : void
	{
        $msg = "reportAttemptingFullContext d=" . $this->getDecisionDescription($recognizer, $dfa) . ", input='" . $recognizer->getTokenStream()->getTextByInterval(new Interval($startIndex, $stopIndex)) . "'";
        $recognizer->notifyErrorListeners($msg);
    }

	function reportContextSensitivity(Parser $recognizer, DFA $dfa, int $startIndex, int $stopIndex, int $prediction, ATNConfigSet $configs) : void
	{
        $msg = "reportContextSensitivity d=" . $this->getDecisionDescription($recognizer, $dfa) . ", input='" . $recognizer->getTokenStream()->getTextByInterval(new Interval($startIndex, $stopIndex)) . "'";
        $recognizer->notifyErrorListeners($msg);
    }

    function getDecisionDescription(Parser $recognizer, DFA $dfa) : string
    {
        $decision = $dfa->decision;
        $ruleIndex = $dfa->atnStartState->ruleIndex;

        $ruleNames = $recognizer->getRuleNames();
        if ($ruleIndex < 0 || $ruleIndex >= count($ruleNames))
        {
            return (string)$decision;
        }
        $ruleName = $ruleNames[$ruleIndex];
        if (empty($ruleName)) return (string)$decision;
        return $decision . " (" . $ruleName . ")";
    }

    // Computes the set of conflicting or ambiguous alternatives from a
    // configuration set, if that information was not already provided by the
    // parser.
    //
    // @param reportedAlts The set of conflicting or ambiguous alternatives, as
    // reported by the parser.
    // @param configs The conflicting or ambiguous configuration set.
    // @return Returns {@code reportedAlts} if it is not {@code null}, otherwise
    // returns the set of alternatives represented in {@code configs}.
    function getConflictingAlts($reportedAlts, $configs)
    {
        if ($reportedAlts !== null)
        {
            return $reportedAlts;
        }
        $result = new BitSet();
        foreach ($configs->items as $item)
        {
            $result->add($item->alt);
        }
        return "{" . implode(", ", $result->values()) . "}";
    }
}