<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection PhpUnusedParameterInspection */

namespace Antlr4\Atn\SemanticContexts;

use Antlr4\Recognizer;
use Antlr4\RuleContext;

// A tree structure used to record the semantic context in which
//  an ATN configuration is valid.  It's either a single predicate,
//  a conjunction {@code p1&&p2}, or a sum of products {@code p1||p2}.
//  <p>I have scoped the {@link AND}, {@link OR}, and {@link Predicate} subclasses of
//  {@link SemanticContext} within the scope of this outer class.</p>
abstract class SemanticContext
{
    //The default {@link SemanticContext}, which is semantically equivalent to a predicate of the form {@code {true}?}.
    private static $_NONE;
    static function NONE() : SemanticContextPredicate { return self::$_NONE ?? (self::$_NONE = new SemanticContextPredicate()); }

    function __construct() {}

    // For context independent predicates, we evaluate them without a local
    // context (i.e., null context). That way, we can evaluate them without
    // having to create proper rule-specific context during prediction (as
    // opposed to the parser, which creates them naturally). In a practical
    // sense, this avoids a cast exception from RuleContext to myruleContext.
    //
    // <p>For context dependent predicates, we must pass in a local context so that
    // references such as $arg evaluate properly as _localctx.arg. We only
    // capture context dependent predicates in the context in which we begin
    // prediction, so we passed in the outer context here in case of context
    // dependent predicate evaluation.</p>
    abstract function eval(Recognizer $parser, RuleContext $outerContext);

    //
    // Evaluate the precedence predicates for the context and reduce the result.
    //
    // @param parser The parser instance.
    // @param outerContext The current parser context object.
    // @return The simplified semantic context after precedence predicates are
    // evaluated, which will be one of the following values.
    // <ul>
    // <li>{@link //NONE}: if the predicate simplifies to {@code true} after
    // precedence predicates are evaluated.</li>
    // <li>{@code null}: if the predicate simplifies to {@code false} after
    // precedence predicates are evaluated.</li>
    // <li>{@code this}: if the semantic context is not changed as a result of
    // precedence predicate evaluation.</li>
    // <li>A non-{@code null} {@link SemanticContext}: the new simplified
    // semantic context after precedence predicates are evaluated.</li>
    // </ul>
    function evalPrecedence(Recognizer $parser, RuleContext $outerContext) : ?self
    {
        return $this;
    }

    static function andContext(?self $a, ?self $b)
    {
        if ($a === null || $a === self::NONE()) return $b;
        if ($b === null || $b === self::NONE()) return $a;

        $result = new SemanticContextAnd($a, $b);
        return count($result->opnds) === 1 ? $result->opnds[0] : $result;
    }

    static function orContext(?self $a, ?self $b)
    {
        if ($a === null) return $b;
        if ($b === null) return $a;
        if ($a === self::NONE() || $b === self::NONE()) return self::NONE();

        $result = new SemanticContextOr($a, $b);
        return count($result->opnds) === 1 ? $result->opnds[0] : $result;
    }
}

