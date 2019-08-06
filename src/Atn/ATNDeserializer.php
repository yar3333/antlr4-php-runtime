<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection PhpUnusedParameterInspection */

namespace Antlr4\Atn;

use Antlr4\Atn\Actions\LexerActionType;
use Antlr4\Atn\Actions\LexerChannelAction;
use Antlr4\Atn\Actions\LexerCustomAction;
use Antlr4\Atn\Actions\LexerModeAction;
use Antlr4\Atn\Actions\LexerMoreAction;
use Antlr4\Atn\Actions\LexerPopModeAction;
use Antlr4\Atn\Actions\LexerPushModeAction;
use Antlr4\Atn\Actions\LexerSkipAction;
use Antlr4\Atn\Actions\LexerTypeAction;
use Antlr4\Atn\States\ATNState;
use Antlr4\Atn\States\DecisionState;
use Antlr4\Atn\States\LoopEndState;
use Antlr4\Atn\States\RuleStopState;
use Antlr4\Atn\States\StarLoopEntryState;
use Antlr4\Atn\Transitions\Transition;
use Antlr4\IntervalSet;
use Antlr4\Token;
use Antlr4\Utils\Utils;

class ATNDeserializer
{
    // This is the earliest supported serialized UUID.
    // stick to serialized version for now, we don't need a UUID instance
    const BASE_SERIALIZED_UUID = "AADB8D7E-AEEF-4415-AD2B-8204D6CF042E";

    //
    // This UUID indicates the serialized ATN contains two sets of
    // IntervalSets, where the second set's values are encoded as
    // 32-bit integers to support the full Unicode SMP range up to U+10FFFF.
    //
    const ADDED_UNICODE_SMP = "59627784-3BE5-417A-B9EB-8131A7286089";

    // This list contains all of the currently supported UUIDs, ordered by when
    // the feature first appeared in this branch.
    const SUPPORTED_UUIDS = [ self::BASE_SERIALIZED_UUID, self::ADDED_UNICODE_SMP ];

    const SERIALIZED_VERSION = 3;

    // This is the current serialized UUID.
    const SERIALIZED_UUID = self::ADDED_UNICODE_SMP;

    /**
     * @var ATNDeserializationOptions
     */
    public $deserializationOptions;

    /**
     * @var int[]
     */
    public $data;

    /**
     * @var int
     */
    public $pos;

    /**
     * @var string
     */
    public $uuid;

    private $stateFactories;

    private $actionFactories;

    function __construct(ATNDeserializationOptions $options=null)
    {
        $this->deserializationOptions = $options ?? ATNDeserializationOptions::defaultOptions();
    }

    // Determines if a particular serialized representation of an ATN supports
    // a particular feature, identified by the {@link UUID} used for serializing
    // the ATN at the time the feature was first introduced.
    //
    // @param feature The {@link UUID} marking the first time the feature was supported in the serialized ATN.
    // @param actualUuid The {@link UUID} of the actual serialized ATN which is currently being deserialized.
    // @return {@code true} if the {@code actualUuid} value represents a
    // serialized ATN at or after the feature identified by {@code feature} was
    // introduced; otherwise, {@code false}.
    function isFeatureSupported(string $feature, string $actualUuid) : bool
    {
        $idx1 = array_search($feature, self::SUPPORTED_UUIDS, true);
        if ($idx1 === false) return false;
        $idx2 = array_search($actualUuid, self::SUPPORTED_UUIDS, true);
        return $idx2 >= $idx1;
    }

    function deserialize(string $data) : ATN
    {
        $this->reset($data);
        $this->checkVersion();
        $this->checkUUID();
        $atn = $this->readATN();
        $this->readStates($atn);
        $this->readRules($atn);
        $this->readModes($atn);
        $sets = [];

        // First, deserialize sets with 16-bit arguments <= U+FFFF.
        $this->readSets($sets, function() { return $this->readInt(); });

        // Next, if the ATN was serialized with the Unicode SMP feature,
        // deserialize sets with 32-bit arguments <= U+10FFFF.

        if ($this->isFeatureSupported(self::ADDED_UNICODE_SMP, $this->uuid))
        {
            $this->readSets($sets, function() { return $this->readInt32(); });
        }
        $this->readEdges($atn, $sets);
        $this->readDecisions($atn);
        $this->readLexerActions($atn);
        $this->markPrecedenceDecisions($atn);
        $this->verifyATN($atn);
        if ($this->deserializationOptions->generateRuleBypassTransitions && $atn->grammarType === ATN::GRAMMAR_TYPE_PARSER)
        {
            $this->generateRuleBypassTransitions($atn);
            // re-verify after modification
            $this->verifyATN($atn);
        }
        return $atn;
    }

    // TODO: IS THIS NEED???
    private function reset_adjust($c) : int
    {
        $v = Utils::charCodeAt($c, 0);
        return $v > 1  ? $v - 2 : $v + 65533;
    }

    function reset(string $data) : void
    {
        $temp = []; foreach (preg_split('//u', $data, null, PREG_SPLIT_NO_EMPTY) as $c) $temp[] = $this->reset_adjust($c);
        // don't adjust the first value since that's the version number
        $temp[0] = Utils::charCodeAt($data, 0);
        $this->data = $temp;
        $this->pos = 0;
    }

    function checkVersion() : void
    {
        $version = $this->readInt();
        if ($version !== self::SERIALIZED_VERSION)
        {
            throw new \RuntimeException("Could not deserialize ATN with version " . $version . " (expected " . self::SERIALIZED_VERSION . ").");
        }
    }

    function checkUUID() : void
    {
        $uuid = $this->readUUID();
        if (!in_array($uuid, self::SUPPORTED_UUIDS, true))
        {
            throw new \RuntimeException("Could not deserialize ATN with UUID: " . $uuid . " (expected " . self::SERIALIZED_UUID . " or a legacy UUID).");
        }
        $this->uuid = $uuid;
    }

    function readATN() : ATN
    {
        $grammarType = $this->readInt();
        $maxTokenType = $this->readInt();
        return new ATN($grammarType, $maxTokenType);
    }

    function readStates(ATN $atn) : void
    {
        $loopBackStateNumbers = [];
        $endStateNumbers = [];
        $nstates = $this->readInt();
        for ($i=0; $i<$nstates; $i++)
        {
            $stype = $this->readInt();
            // ignore bad type of states
            if ($stype === ATNState::INVALID_TYPE)
            {
                $atn->addState(null);
                continue;
            }
            $ruleIndex = $this->readInt();
            if ($ruleIndex === 0xFFFF)
            {
                $ruleIndex = -1;
            }
            $s = $this->stateFactory($stype, $ruleIndex);
            if ($stype === ATNState::LOOP_END)
            {
                // special case
                $loopBackStateNumber = $this->readInt();
                $loopBackStateNumbers[] = [ $s, $loopBackStateNumber ];
            }
            else if($s instanceof \Antlr4\Atn\States\BlockStartState)
            {
                $endStateNumber = $this->readInt();
                $endStateNumbers[] = [ $s, $endStateNumber ];
            }
            $atn->addState($s);
        }
        // delay the assignment of loop back and end states until we know all the
        // state instances have been initialized
        foreach ($loopBackStateNumbers as $pair)
        {
            $pair[0]->loopBackState = $atn->states[$pair[1]];
        }

        foreach ($endStateNumbers as $pair)
        {
            $pair[0]->endState = $atn->states[$pair[1]];
        }

        $numNonGreedyStates = $this->readInt();
        for ($j=0; $j<$numNonGreedyStates; $j++)
        {
            $stateNumber = $this->readInt();
            $atn->states[$stateNumber]->nonGreedy = true;
        }

        $numPrecedenceStates = $this->readInt();
        for ($j=0; $j<$numPrecedenceStates; $j++)
        {
            $stateNumber = $this->readInt();
            $atn->states[$stateNumber]->isPrecedenceRule = true;
        }
    }

    function readRules(ATN $atn) : void
    {
        $nrules = $this->readInt();
        if ($atn->grammarType === ATN::GRAMMAR_TYPE_LEXER)
        {
            $atn->ruleToTokenType = self::initArray($nrules, 0);
        }
        $atn->ruleToStartState = self::initArray($nrules, 0);
        for ($i=0; $i<$nrules; $i++)
        {
            $s = $this->readInt();
            $startState = $atn->states[$s];
            $atn->ruleToStartState[$i] = $startState;
            if ($atn->grammarType === ATN::GRAMMAR_TYPE_LEXER)
            {
                $tokenType = $this->readInt();
                if ($tokenType === 0xFFFF)
                {
                    $tokenType = Token::EOF;
                }
                $atn->ruleToTokenType[$i] = $tokenType;
            }
        }
        $atn->ruleToStopState = self::initArray($nrules, 0);
        foreach ($atn->states as $state)
        {
            if (!($state instanceof \Antlr4\Atn\States\RuleStopState))
            {
                continue;
            }
            $atn->ruleToStopState[$state->ruleIndex] = $state;
            $atn->ruleToStartState[$state->ruleIndex]->stopState = $state;
        }
    }

    function readModes(ATN $atn) : void
    {
        $nmodes = $this->readInt();
        for ($i=0; $i<$nmodes; $i++)
        {
            $s = $this->readInt();
            $atn->modeToStartState[] = $atn->states[$s];
        }
    }

    function readSets(array &$sets, callable $readUnicode) : void
    {
        $m = $this->readInt();
        for ($i=0; $i < $m; $i++)
        {
            $iset = new IntervalSet();
            $sets[] = $iset;
            $n = $this->readInt();
            $containsEof = $this->readInt();
            if ($containsEof !== 0)
            {
                $iset->addOne(-1);
            }
            for ($j=0; $j<$n; $j++)
            {
                $i1 = $readUnicode();
                $i2 = $readUnicode();
                $iset->addRange($i1, $i2);
            }
        }
    }

    function readEdges(ATN $atn, array &$sets) : void
    {
        $nedges = $this->readInt();
        for ($i=0; $i<$nedges; $i++)
        {
            $src = $this->readInt();
            $trg = $this->readInt();
            $ttype = $this->readInt();
            $arg1 = $this->readInt();
            $arg2 = $this->readInt();
            $arg3 = $this->readInt();
            $trans = $this->edgeFactory($atn, $ttype, $src, $trg, $arg1, $arg2, $arg3, $sets);
            $srcState = $atn->states[$src];
            $srcState->addTransition($trans);
        }

        // edges for rule stop states can be derived, so they aren't serialized
        foreach ($atn->states as $state)
        {
            foreach ($state->transitions as $t)
            {
                if (!($t instanceof \Antlr4\Atn\Transitions\RuleTransition)) continue;

                $outermostPrecedenceReturn = -1;
                if ($atn->ruleToStartState[$t->target->ruleIndex]->isPrecedenceRule)
                {
                    if ($t->precedence === 0)
                    {
                        $outermostPrecedenceReturn = $t->target->ruleIndex;
                    }
                }

                $trans = new \Antlr4\Atn\Transitions\EpsilonTransition($t->followState, $outermostPrecedenceReturn);
                $atn->ruleToStopState[$t->target->ruleIndex]->addTransition($trans);
            }
        }

        foreach ($atn->states as $state)
        {
            if ($state instanceof \Antlr4\Atn\States\BlockStartState)
            {
                // we need to know the end state to set its start state
                if ($state->endState === null) throw new \RuntimeException("IllegalState");

                // block end states can only be associated to a single block start state
                if ( $state->endState->startState !== null) throw new \RuntimeException("IllegalState");

                $state->endState->startState = $state;
            }
            if ($state instanceof \Antlr4\Atn\States\PlusLoopbackState)
            {
                foreach ($state->transitions as $t) {
                    $target = $t->target;
                    if ($target instanceof \Antlr4\Atn\States\PlusBlockStartState)
                    {
                        $target->loopBackState = $state;
                    }
                }
            }
            else if ($state instanceof \Antlr4\Atn\States\StarLoopbackState)
            {
                foreach ($state->transitions as $t) {
                    $target = $t->target;
                    if ($target instanceof \Antlr4\Atn\States\StarLoopEntryState)
                    {
                        $target->loopBackState = $state;
                    }
                }
            }
        }
    }

    function readDecisions(ATN $atn) : void
    {
        $ndecisions = $this->readInt();
        for ($i = 0; $i < $ndecisions; $i++)
        {
            $s = $this->readInt();
            /** @var DecisionState $decState */
            $decState = $atn->states[$s];
            $atn->decisionToState[] = $decState;
            $decState->decision = $i;
        }
    }

    function readLexerActions(ATN $atn) : void
    {
        if ($atn->grammarType === ATN::GRAMMAR_TYPE_LEXER)
        {
            $count = $this->readInt();
            $atn->lexerActions = self::initArray($count, null);
            for ($i=0; $i<$count; $i++)
            {
                $actionType = $this->readInt();
                $data1 = $this->readInt();
                if ($data1 === 0xFFFF)
                {
                    $data1 = -1;
                }
                $data2 = $this->readInt();
                if ($data2 === 0xFFFF)
                {
                    $data2 = -1;
                }
                $lexerAction = $this->lexerActionFactory($actionType, $data1, $data2);
                $atn->lexerActions[$i] = $lexerAction;
            }
        }
    }

    function generateRuleBypassTransitions(ATN $atn) : void
    {
        $count = count($atn->ruleToStartState);
        for ($i=0; $i<$count; $i++)
        {
            $atn->ruleToTokenType[$i] = $atn->maxTokenType + $i + 1;
        }
        for ($i=0; $i<$count; $i++)
        {
            $this->generateRuleBypassTransition($atn, $i);
        }
    }

    function generateRuleBypassTransition(ATN $atn, int $idx) : void
    {
        $bypassStart = new \Antlr4\Atn\States\BasicBlockStartState();
        $bypassStart->ruleIndex = $idx;
        $atn->addState($bypassStart);

        $bypassStop = new \Antlr4\Atn\States\BlockEndState();
        $bypassStop->ruleIndex = $idx;
        $atn->addState($bypassStop);

        $bypassStart->endState = $bypassStop;
        $atn->defineDecisionState($bypassStart);

        $bypassStop->startState = $bypassStart;

        $excludeTransition = null;
        $endState = null;

        if ($atn->ruleToStartState[$idx]->isPrecedenceRule)
        {
            // wrap from the beginning of the rule to the StarLoopEntryState
            $endState = null;
            foreach ($atn->states as $state)
            {
                if ($this->stateIsEndStateFor($state, $idx))
                {
                    $endState = $state;
                    /** @var LoopEndState  $state */
                    $excludeTransition = $state->loopBackState->transitions[0];
                    break;
                }
            }

            if ($excludeTransition === null) throw new \RuntimeException("Couldn't identify final state of the precedence rule prefix section.");
        }
        else
        {
            $endState = $atn->ruleToStopState[$idx];
        }

        // all non-excluded transitions that currently target end state need to target blockEnd instead
        // TODO:looks like a bug
        foreach ($atn->states as $state)
        {
            foreach ($state->transitions as $transition)
            {
                if ($transition === $excludeTransition)
                {
                    continue;
                }
                if ($transition->target === $endState)
                {
                    $transition->target = $bypassStop;
                }
            }
        }

        // all transitions leaving the rule start state need to leave blockStart instead
        $ruleToStartState = $atn->ruleToStartState[$idx];
        $count = count($ruleToStartState->transitions);
        while ($count > 0)
        {
            $bypassStart->addTransition($ruleToStartState->transitions[$count-1]);
            $ruleToStartState->transitions = array_slice($ruleToStartState->transitions, -1);
        }
        // link the new states
        $atn->ruleToStartState[$idx]->addTransition(new \Antlr4\Atn\Transitions\EpsilonTransition($bypassStart));
        $bypassStop->addTransition(new \Antlr4\Atn\Transitions\EpsilonTransition($endState));

        $matchState = new \Antlr4\Atn\States\BasicState();
        $atn->addState($matchState);
        $matchState->addTransition(new \Antlr4\Atn\Transitions\AtomTransition($bypassStop, $atn->ruleToTokenType[$idx]));
        $bypassStart->addTransition(new \Antlr4\Atn\Transitions\EpsilonTransition($matchState));
    }

    function stateIsEndStateFor(ATNState $state, int $idx) : ?ATNState
    {
        if ($state->ruleIndex !== $idx) return null;

        if (!($state instanceof StarLoopEntryState))
        {
            return null;
        }

        $maybeLoopEndState = $state->transitions[count($state->transitions) - 1]->target;
        if (!($maybeLoopEndState instanceof LoopEndState)) return null;

        if ($maybeLoopEndState->epsilonOnlyTransitions && ($maybeLoopEndState->transitions[0]->target instanceof RuleStopState))
        {
            return $state;
        }

        return null;
    }

    //
    // Analyze the {@link StarLoopEntryState} states in the specified ATN to set
    // the {@link StarLoopEntryState//isPrecedenceDecision} field to the
    // correct value.
    //
    // @param atn The ATN.
    function markPrecedenceDecisions(ATN $atn) : void
    {
        foreach ($atn->states as $state)
        {
            if (!($state instanceof StarLoopEntryState)) continue;

            // We analyze the ATN to determine if this ATN decision state is the
            // decision for the closure block that determines whether a
            // precedence rule should continue or complete.
            if ( $atn->ruleToStartState[$state->ruleIndex]->isPrecedenceRule)
            {
                $maybeLoopEndState = $state->transitions[count($state->transitions) - 1]->target;
                if ($maybeLoopEndState instanceof \Antlr4\Atn\States\LoopEndState)
                {
                    if ($maybeLoopEndState->epsilonOnlyTransitions && ($maybeLoopEndState->transitions[0]->target instanceof \Antlr4\Atn\States\RuleStopState))
                    {
                        $state->isPrecedenceDecision = true;
                    }
                }
            }
        }
    }

    function verifyATN(ATN $atn) : void
    {
        if (!$this->deserializationOptions->verifyATN) return;

        // verify assumptions
        foreach ($atn->states as $state)
        {
            if ($state === null) continue;

            $this->checkCondition($state->epsilonOnlyTransitions || count($state->transitions) <= 1);
            if ($state instanceof \Antlr4\Atn\States\PlusBlockStartState)
            {
                $this->checkCondition($state->loopBackState !== null);
            }
            else  if ($state instanceof \Antlr4\Atn\States\StarLoopEntryState)
            {
                $this->checkCondition($state->loopBackState !== null);
                $this->checkCondition(count($state->transitions) === 2);
                if ($state->transitions[0]->target instanceof \Antlr4\Atn\States\StarBlockStartState)
                {
                    $this->checkCondition($state->transitions[1]->target instanceof \Antlr4\Atn\States\LoopEndState);
                    $this->checkCondition(!$state->nonGreedy);
                }
                else if ($state->transitions[0]->target instanceof \Antlr4\Atn\States\LoopEndState)
                {
                    $this->checkCondition($state->transitions[1]->target instanceof \Antlr4\Atn\States\StarBlockStartState);
                    $this->checkCondition($state->nonGreedy);
                }
                else
                {
                    throw new \RuntimeException("IllegalState");
                }
            }
            else if ($state instanceof \Antlr4\Atn\States\StarLoopbackState)
            {
                $this->checkCondition(count($state->transitions) === 1);
                $this->checkCondition($state->transitions[0]->target instanceof \Antlr4\Atn\States\StarLoopEntryState);
            }
            else if ($state instanceof \Antlr4\Atn\States\LoopEndState)
            {
                $this->checkCondition($state->loopBackState !== null);
            }
            else if ($state instanceof \Antlr4\Atn\States\RuleStartState)
            {
                $this->checkCondition($state->stopState !== null);
            }
            else if ($state instanceof \Antlr4\Atn\States\BlockStartState)
            {
                $this->checkCondition($state->endState !== null);
            }
            else if ($state instanceof \Antlr4\Atn\States\BlockEndState)
            {
                $this->checkCondition($state->startState !== null);
            }
            else if ($state instanceof\Antlr4\Atn\States\ DecisionState)
            {
                $this->checkCondition(count($state->transitions) <= 1 || $state->decision >= 0);
            }
            else
            {
                $this->checkCondition(count($state->transitions) <= 1 || ($state instanceof \Antlr4\Atn\States\RuleStopState));
            }
        }
    }

    function checkCondition($condition, $message="IllegalState") : void
    {
        if (!$condition)
        {
            throw new \RuntimeException($message);
        }
    }

    function readInt() : int
    {
        return $this->data[$this->pos++];
    }

    function readInt32() : int
    {
        $low = $this->readInt();
        $high = $this->readInt();
        return $low | ($high << 16);
    }

    function readLong() : int
    {
        $low = $this->readInt32();
        $high = $this->readInt32();
        return ($low & 0x00000000FFFFFFFF) | ($high << 32);
    }

    function readUUID() : string
    {
        $bb = [];
        for ($i=0; $i < 8; $i++)
        {
            $int = $this->readInt();
            $bb[] = $int & 0xFF;
            $bb[] = ($int >> 8) & 0xFF;
        }
        $bb = array_reverse($bb);
        $hex = strtoupper(bin2hex(implode(array_map("chr", $bb))));
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    /**
     * @param ATN $atn
     * @param int $type
     * @param int $src
     * @param int $trg
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     * @param IntervalSet[] $sets
     * @return Transition
     * @throws \Exception
     */
    function edgeFactory(ATN $atn, int $type, int $src, int $trg, int $arg1, int $arg2, int $arg3, array $sets) : Transition
    {
        $target = $atn->states[$trg];
        switch($type)
        {
            case Transition::EPSILON:
                return new \Antlr4\Atn\Transitions\EpsilonTransition($target);
            case Transition::RANGE:
                return $arg3 !== 0 ? new \Antlr4\Atn\Transitions\RangeTransition($target, Token::EOF, $arg2) : new \Antlr4\Atn\Transitions\RangeTransition($target, $arg1, $arg2);
            case Transition::RULE:
                /** @noinspection PhpParamsInspection */
                return new \Antlr4\Atn\Transitions\RuleTransition($atn->states[$arg1], $arg2, $arg3, $target);
            case Transition::PREDICATE:
                return new \Antlr4\Atn\Transitions\PredicateTransition($target, $arg1, $arg2, $arg3 !== 0);
            case Transition::PRECEDENCE:
                return new \Antlr4\Atn\Transitions\PrecedencePredicateTransition($target, $arg1);
            case Transition::ATOM:
                return $arg3 !== 0 ? new \Antlr4\Atn\Transitions\AtomTransition($target, Token::EOF) : new \Antlr4\Atn\Transitions\AtomTransition($target, $arg1);
            case Transition::ACTION:
                return new \Antlr4\Atn\Transitions\ActionTransition($target, $arg1, $arg2, $arg3 !== 0);
            case Transition::SET:
                return new \Antlr4\Atn\Transitions\SetTransition($target, $sets[$arg1]);
            case Transition::NOT_SET:
                return new \Antlr4\Atn\Transitions\NotSetTransition($target, $sets[$arg1]);
            case Transition::WILDCARD:
                return new \Antlr4\Atn\Transitions\WildcardTransition($target);
            default:
                throw new \RuntimeException("The specified transition type: " . $type . " is not valid.");
        }
    }

    function stateFactory(int $type, int $ruleIndex)
    {
        if ($this->stateFactories === null)
        {
            $sf = [];
            $sf[ATNState::INVALID_TYPE] = null;
            $sf[ATNState::BASIC] = function() { return new \Antlr4\Atn\States\BasicState(); };
            $sf[ATNState::RULE_START] = function() { return new \Antlr4\Atn\States\RuleStartState(); };
            $sf[ATNState::BLOCK_START] = function() { return new \Antlr4\Atn\States\BasicBlockStartState(); };
            $sf[ATNState::PLUS_BLOCK_START] = function() { return new \Antlr4\Atn\States\PlusBlockStartState(); };
            $sf[ATNState::STAR_BLOCK_START] = function() { return new \Antlr4\Atn\States\StarBlockStartState(); };
            $sf[ATNState::TOKEN_START] = function() { return new \Antlr4\Atn\States\TokensStartState(); };
            $sf[ATNState::RULE_STOP] = function() { return new \Antlr4\Atn\States\RuleStopState(); };
            $sf[ATNState::BLOCK_END] = function() { return new \Antlr4\Atn\States\BlockEndState(); };
            $sf[ATNState::STAR_LOOP_BACK] = function() { return new \Antlr4\Atn\States\StarLoopbackState(); };
            $sf[ATNState::STAR_LOOP_ENTRY] = function() { return new \Antlr4\Atn\States\StarLoopEntryState(); };
            $sf[ATNState::PLUS_LOOP_BACK] = function() { return new \Antlr4\Atn\States\PlusLoopbackState(); };
            $sf[ATNState::LOOP_END] = function() { return new \Antlr4\Atn\States\LoopEndState(); };
            $this->stateFactories = $sf;
        }

        if ($type > count($this->stateFactories) || $this->stateFactories[$type] === null)
        {
            throw new \RuntimeException("The specified state type " . $type . " is not valid.");
        }

        $s = $this->stateFactories[$type]();
        if ($s)
        {
            $s->ruleIndex = $ruleIndex;
            return $s;
        }

        return null;
    }

    function lexerActionFactory($type, $data1, $data2)
    {
        if ($this->actionFactories === null)
        {
            $af = [];
            $af[LexerActionType::CHANNEL] = function($data1, $data2) { return new LexerChannelAction($data1); };
            $af[LexerActionType::CUSTOM] = function($data1, $data2) { return new LexerCustomAction($data1, $data2); };
            $af[LexerActionType::MODE] = function($data1, $data2) { return new LexerModeAction($data1); };
            $af[LexerActionType::MORE] = function($data1, $data2) { return LexerMoreAction::INSTANCE(); };
            $af[LexerActionType::POP_MODE] = function($data1, $data2) { return LexerPopModeAction::INSTANCE(); };
            $af[LexerActionType::PUSH_MODE] = function($data1, $data2) { return new LexerPushModeAction($data1); };
            $af[LexerActionType::SKIP] = function($data1, $data2) { return LexerSkipAction::INSTANCE(); };
            $af[LexerActionType::TYPE] = function($data1, $data2) { return new LexerTypeAction($data1); };
            $this->actionFactories = $af;
        }
        if ($type > count($this->actionFactories) || $this->actionFactories[$type] === null)
        {
            throw new \RuntimeException("The specified lexer action type " . $type . " is not valid.");
        }

        return $this->actionFactories[$type]($data1, $data2);
    }

    static function initArray($length, $value) : array
    {
        $tmp = [];
        for ($i = 0; $i < $length; $i++) $tmp[] = $value;
        return $tmp;
    }
}