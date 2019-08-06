<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn;

use Antlr4\Dfa\DFAState;
use Antlr4\PredictionContexts\PredictionContext;
use Antlr4\PredictionContexts\PredictionContextCache;
use Antlr4\PredictionContexts\PredictionContextUtils;

// The context cache maps all PredictionContext objects that are ==
//  to a single cached copy. This cache is shared across all contexts
//  in all ATNConfigs in all DFA states.  We rebuild each ATNConfigSet
//  to use only cached nodes/graphs in addDFAState(). We don't want to
//  fill this during closure() since there are lots of contexts that
//  pop up but are not used ever again. It also greatly slows down closure().
//
//  <p>This cache makes a huge difference in memory and a little bit in speed.
//  For the Java grammar on java.*, it dropped the memory requirements
//  at the end from 25M to 16M. We don't store any of the full context
//  graphs in the DFA because they are limited to local context only,
//  but apparently there's a lot of repetition there as well. We optimize
//  the config contexts before storing the config set in the DFA states
//  by literally rebuilding them with cached subgraphs only.</p>
//
//  <p>I tried a cache for use during closure operations, that was
//  whacked after each adaptivePredict(). It cost a little bit
//  more time I think and doesn't save on the overall footprint
//  so it's not worth the complexity.</p>
abstract class ATNSimulator
{
    private static $_ERROR;
    public static function ERROR() : DFAState { return self::$_ERROR ?? (self::$_ERROR = new DFAState(0x7FFFFFFF, new ATNConfigSet())); }

    /**
     * @var ATN
     */
    public $atn;

    /**
     * @var PredictionContextCache
     */
    public $sharedContextCache;

    function __construct(ATN $atn, PredictionContextCache $sharedContextCache)
    {
        $this->atn = $atn;
        $this->sharedContextCache = $sharedContextCache;
    }

    function getCachedContext(PredictionContext $context) : PredictionContext
    {
        if ($this->sharedContextCache === null) return $context;
        return PredictionContextUtils::getCachedPredictionContext($context, $this->sharedContextCache, new \ArrayObject());
    }

    abstract function reset() : void;
}
