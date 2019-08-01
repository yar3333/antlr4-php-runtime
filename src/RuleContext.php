<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace Antlr4;

use Antlr4\Atn\ATN;
use Antlr4\Tree\ParseTree;
use Antlr4\Tree\ParseTreeVisitor;
use Antlr4\Tree\RuleNode;
use Antlr4\Tree\Trees;

//  A rule context is a record of a single rule invocation. It knows
//  which context invoked it, if any. If there is no parent context, then
//  naturally the invoking state is not valid.  The parent link
//  provides a chain upwards from the current rule invocation to the root
//  of the invocation tree, forming a stack. We actually carry no
//  information about the rule associated with this context (except
//  when parsing). We keep only the state number of the invoking state from
//  the ATN submachine that invoked this. Contrast this with the s
//  pointer inside ParserRuleContext that tracks the current state
//  being "executed" for the current rule.
//
//  The parent contexts are useful for computing lookahead sets and
//  getting error information.
//
//  These objects are used during parsing and prediction.
//  For the special case of parsers, we use the subclass
//  ParserRuleContext.
//
//  @see ParserRuleContext
class RuleContext implements RuleNode
{
    private static $_EMPTY;

    public static function emptyContext() : ParserRuleContext { return self::$_EMPTY ?? (self::$_EMPTY = new ParserRuleContext()); }

    /**
     * @var RuleContext
     */
    public $parentCtx;

    /**
     * @var int
     */
    public $invokingState;

    function __construct(RuleContext $parent=null, int $invokingState=null)
    {
        // What context invoked this rule?
        $this->parentCtx = $parent;

        // What state invoked the rule associated with this context?
        // The "return address" is the followState of invokingState
        // If parent is null, this should be -1.
        $this->invokingState = $invokingState ?? -1;
    }

    function depth() : int
    {
        $n = 0;
        $p = $this;
        while ($p !== null)
        {
            $p = $p->parentCtx;
            $n++;
        }
        return $n;
    }

    // A context is empty if there is no invoking state; meaning nobody call
    // current context.
    function isEmpty() : bool
    {
        return $this->invokingState === -1;
    }

    // satisfy the ParseTree / SyntaxTree interface

    function getSourceInterval() : Interval
    {
        return Interval::INVALID();
    }

    function getRuleContext() : RuleContext
    {
        return $this;
    }

    function getPayload()
    {
        return $this;
    }

    // Return the combined text of all child nodes. This method only considers
    // tokens which have been added to the parse tree.
    // <p>
    // Since tokens on hidden channels (e.g. whitespace or comments) are not
    // added to the parse trees, they will not appear in the output of this
    // method.
    function getText() : string
    {
        $r = "";
        for ($i = 0; $i < $this->getChildCount(); $i++)
        {
            $r .= $this->getChild($i)->getText();
        }
        return $r;
    }

    // For rule associated with this parse tree internal node, return
    // the outer alternative number used to match the input. Default
    // implementation does not compute nor store this alt num. Create
    // a subclass of ParserRuleContext with backing field and set
    // option contextSuperClass.
    // to set it.
    function getAltNumber() : int { return ATN::INVALID_ALT_NUMBER; }

    // Set the outer alternative number for this context node. Default
    // implementation does nothing to avoid backing field overhead for
    // trees that don't need it.  Create
    // a subclass of ParserRuleContext with backing field and set
    // option contextSuperClass.
    function setAltNumber(int $altNumber) : void {}

    /**
     * @param int $i
     * @param string $type
     * @return ParseTree
     */
    function getChild(int $i, string $type=null)
    {
        return null;
    }

    function getChildCount() : int
    {
        return 0;
    }

    function accept(ParseTreeVisitor $visitor)
    {
        return $visitor->visitChildren($this);
    }

    /**
     * @param string[]|\ArrayObject $ruleNames
     * @return string
     */
    function toStringTree(\ArrayObject $ruleNames=null) : string
    {
        return Trees::toStringTree($this, $ruleNames);
    }

    function __toString() : string
    {
        return $this->toString();
    }

    function getRuleIndex() : int { return -1; }

    /**
     * @param string[]|\ArrayObject $ruleNames
     * @param RuleContext|null $stop
     * @return string
     */
    function toString(\ArrayObject$ruleNames=null, ?RuleContext $stop=null) : string
    {
        $p = $this;
        $s = "[";
        while ($p !== null && $p !== $stop)
        {
            if ($ruleNames === null)
            {
                if (!$p->isEmpty())
                {
                    $s .= $p->invokingState;
                }
            }
            else
            {
                $ri = $p->getRuleIndex();
                $ruleName = ($ri >= 0 && $ri < count($ruleNames)) ? $ruleNames[$ri] : (string)$ri;
                $s .= $ruleName;
            }
            if ($p->parentCtx !== null && ($ruleNames !== null || !$p->parentCtx->isEmpty()))
            {
                $s .= " ";
            }
            $p = $p->parentCtx;
        }
        $s .= "]";
        return $s;
    }

    /**
     * @return ParseTree
     */
    function getParent() { return $this->parentCtx; }

    function setParent(?RuleContext $ctx): void { $this->parentCtx = $ctx; }
}
