<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace Antlr4\Tree;

use Antlr4\RuleContext;

interface ParseTree extends SyntaxTree
{
    /**
     * @return ParseTree
     */
    function getParent();

    /**
     * @param int $i
     * @param mixed $type
     * @return ParseTree
     */
    function getChild(int $i, string $type=null);

    /** Set the parent for this node.
     *
     *  This is not backward compatible as it changes
     *  the interface but no one was able to create custom
     *  nodes anyway so I'm adding as it improves internal
     *  code quality.
     *
     *  One could argue for a restructuring of
     *  the class/interface hierarchy so that
     *  setParent, addChild are moved up to Tree
     *  but that's a major change. So I'll do the
     *  minimal change, which is to add this method.
     *
     *  @param RuleContext $parent
     */
    function setParent(RuleContext $parent) : void;

    /** The {@link ParseTreeVisitor} needs a double dispatch method.
     *  @param ParseTreeVisitor $visitor
     */
    function accept(ParseTreeVisitor $visitor);

    /** Return the combined text of all leaf nodes. Does not get any
     *  off-channel tokens (if any) so won't return whitespace and
     *  comments if they are sent to parser on hidden channel.
     */
    function getText() : string;

    /**
     * Specialize toStringTree so that it can print out more information
     * based upon the parser.
     * @param string[]|\ArrayObject $ruleNames
     * @return string
     */
    function toStringTree(\ArrayObject $ruleNames=null) : string;
}