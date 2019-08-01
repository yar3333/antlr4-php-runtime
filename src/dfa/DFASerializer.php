<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Dfa;

use Antlr4\Utils\Utils;
use Antlr4\Vocabulary;

/** A DFA walker that knows how to dump them to serialized strings. */
class DFASerializer
{
    /**
     * @var DFA
     */
    public $dfa;

    /**
     * @var Vocabulary
     */
    public $vocabulary;


    function __construct(DFA $dfa, Vocabulary $vocabulary)
	{
        $this->dfa = $dfa;
        $this->vocabulary = $vocabulary;
    }

    function __toString() 
    {
        if ($this->dfa->s0 === null) return "";

		$buf = "";

   		/** @var DFAState $s */
		foreach ($this->dfa->states as $s)
		{
			$n = 0;
			if ($s->edges !== null) $n = count($s->edges);
			for ($i=0; $i<$n; $i++)
			{
				/** @var DFAState $t */
				$t = $s->edges[$i];
				if ($t !== null && $t->stateNumber !== 0x7FFFFFFF)
				{
					$buf .= $this->getStateString($s);
					$label = $this->getEdgeLabel($i);
					$buf .= "-" . $label . "->" . $this->getStateString($t) . "\n";
				}
			}
		}

		return $buf;
    }
    
    function getEdgeLabel(int $i) : string
    {
        return $this->vocabulary->getDisplayName($i - 1);
    }
    
    function getStateString(DFAState $s) : string
    {
        $baseStateStr = ($s->isAcceptState ? ":" : "") . "s" . $s->stateNumber . ($s->requiresFullContext ? "^" : "");
        if ($s->isAcceptState)
        {
            if ($s->predicates !== null) 
            {
                return $baseStateStr . "=>" . Utils::arrayToString($s->predicates);
            }

            return $baseStateStr . "=>" . $s->prediction;
        }

        return $baseStateStr;
    }
}
