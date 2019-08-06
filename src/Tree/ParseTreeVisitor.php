<?php

namespace Antlr4\Tree;

/**
 * This interface defines the basic notion of a parse tree visitor. Generated
 * visitors implement this interface and the {@code XVisitor} interface for grammar {@code X}.
 */
interface ParseTreeVisitor
{
	/**
	 * Visit a parse tree, and return a user-defined result of the operation.
     * Must return the result of visiting the parse tree.
	 *
	 * @param ParseTree $tree  The {@link ParseTree} to visit.
	 */
	function visit(ParseTree $tree);

	/**
	 * Visit the children of a node, and return a user-defined result of the
	 * operation.
     * Must return the result of visiting the parse tree.
	 *
	 * @param RuleNode $node  The {@link RuleNode} whose children should be visited.
	 */
	function visitChildren(RuleNode $node);

	/**
	 * Visit a terminal node, and return a user-defined result of the operation.
     * Must return the result of visiting the parse tree.
	 *
	 * @param TerminalNode $node  The {@link TerminalNode} to visit.
	 */
	function visitTerminal(TerminalNode $node);

	/**
	 * Visit an error node, and return a user-defined result of the operation.
     * Must return the result of visiting the parse tree.
	 *
	 * @param ErrorNode $node  The {@link ErrorNode} to visit.
	 */
	function visitErrorNode(ErrorNode $node);
}
