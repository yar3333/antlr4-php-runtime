<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Tree;

use Antlr4\Atn\ATN;
use Antlr4\ParserRuleContext;
use Antlr4\RuleContext;
use Antlr4\Token;
use Antlr4\Utils\Utils;

/** A set of utility routines useful for all kinds of ANTLR trees. */
class Trees
{
    /**
     * Print out a whole tree in LISP form. {@link #getNodeText} is used on the
     * node payloads to get the text for the nodes.  Detect
     * parse trees and extract data appropriately.
     *
     * @param Tree $tree
     * @param string[]|\ArrayObject $ruleNames
     * @return string
     */
    static function toStringTree(Tree $tree, \ArrayObject $ruleNames=null) : string
    {
        $s = self::getNodeText($tree, $ruleNames);
        $s = Utils::escapeWhitespace($s, false);

        $childCount = $tree->getChildCount();
        if ($childCount === 0) return $s;

        $res = "(" . $s . " ";
        for ($i = 0; $i < $childCount; $i++)
        {
            $res .= ($i > 0 ? ' ' : '') . self::toStringTree($tree->getChild($i), $ruleNames);
        }
        $res .= ")";
        return $res;
    }

    /**
     * @param Tree $t
     * @param string[]|\ArrayObject $ruleNames
     * @return string
     */
    static function getNodeText(Tree $t, ?\ArrayObject $ruleNames) : string
    {
        if ($ruleNames !== null)
        {
           if ($t instanceof RuleContext)
           {
                $ruleIndex = $t->getRuleContext()->getRuleIndex();
				$ruleName = $ruleNames[$ruleIndex];
				$altNumber = $t->getAltNumber();
				if ($altNumber !== ATN::INVALID_ALT_NUMBER)
				{
					return "$ruleName:$altNumber";
				}
				return $ruleName;
           }
           else if ($t instanceof ErrorNode)
           {
               return (string)$t;
           }
           else if ($t instanceof TerminalNode)
           {
               if ($t->getSymbol()!==null)
               {
                   return $t->getSymbol()->getText();
               }
           }
        }
        // no recog for rule names
        $payload = $t->getPayload();
        if ($payload instanceof Token)
        {
           return $payload->getText();
        }
        return (string)$t->getPayload();
    }

    // Return ordered list of all children of this node

    /**
     * @param Tree $t
     * @return Tree[]
     */
    static function getChildren(Tree $t) : array
    {
        $list = [];
        $count = $t->getChildCount();
        for($i = 0; $i < $count; $i++)
        {
            $list[] = $t->getChild($i);
        }
        return $list;
    }

    /**
     * Return a list of all ancestors of this node. The first node of list is the root and the last is the parent of this node.
     * @param Tree $t
     * @return Tree[]
     */
    static function getAncestors(Tree $t) : array
    {
        $ancestors = [];
        $t = $t->getParent();
        while ($t)
        {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $ancestors = array_merge([$t],  $ancestors);
            $t = $t->getParent();
        }
        return $ancestors;
    }

    static function findAllTokenNodes(ParseTree $t, int $ttype) : array
    {
        return self::findAllNodes($t, $ttype, true);
    }

    static function findAllRuleNodes(ParseTree $t, int $ruleIndex) : array
    {
        return self::findAllNodes($t, $ruleIndex, false);
    }

    static function findAllNodes(ParseTree $t, int $index, bool $findTokens) : array
    {
        $nodes = new \ArrayObject();
        self::_findAllNodes($t, $index, $findTokens, $nodes);
        return $nodes->getArrayCopy();
    }

    static function _findAllNodes(ParseTree $t, int $index, bool $findTokens, \ArrayObject $nodes) : void
    {
        // check this node (the root) first
        if ($findTokens && ($t instanceof TerminalNode))
        {
            if ($t->getSymbol()->type === $index)
            {
                $nodes->append($t);
            }
        }
        else if(!$findTokens && ($t instanceof ParserRuleContext))
        {
            if ($t->getRuleIndex() === $index)
            {
                $nodes->append($t);
            }
        }

        // check children
        for ($i = 0; $i < $t->getChildCount(); $i++)
        {
            self::_findAllNodes($t->getChild($i), $index, $findTokens, $nodes);
        }
    }

    static function descendants(ParseTree $t) : array
    {
        $nodes = [$t];
        for ($i = 0; $i < $t->getChildCount(); $i++)
        {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $nodes = array_merge($nodes, self::descendants($t->getChild($i)));
        }
        return $nodes;
    }
}
