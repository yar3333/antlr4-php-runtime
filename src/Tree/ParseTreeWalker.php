<?php

namespace Antlr4\Tree;

use Antlr4\ParserRuleContext;

class ParseTreeWalker
{
    private static $_DEFAULT;
    static function DEFAULT(): self { return self::$_DEFAULT ?? (self::$_DEFAULT = new self()); }

    function walk(ParseTreeListener $listener, ParseTree $t) : void
    {
		if ( $t instanceof ErrorNode) {
			$listener->visitErrorNode($t);
			return;
		}
		else if ( $t instanceof TerminalNode)
		{
			$listener->visitTerminal($t);
			return;
		}
		/** @var RuleNode $r */
		$r = $t;
        $this->enterRule($listener, $r);
        $n = $r->getChildCount();
        for ($i = 0; $i<$n; $i++)
        {
            $this->walk($listener, $r->getChild($i));
        }
		$this->exitRule($listener, $r);
    }

    // The discovery of a rule node, involves sending two events: the generic
    // {@link ParseTreeListener//enterEveryRule} and a
    // {@link RuleContext}-specific event. First we trigger the generic and then
    // the rule specific. We to them in reverse order upon finishing the node.
    function enterRule(ParseTreeListener $listener, RuleNode $r) : void
    {
        /** @var ParserRuleContext $ctx */
        $ctx = $r->getRuleContext();
        $listener->enterEveryRule($ctx);
        $ctx->enterRule($listener);
    }

    function exitRule(ParseTreeListener $listener, RuleNode $r) : void
    {
        /** @var ParserRuleContext $ctx */
        $ctx = $r->getRuleContext();
        $ctx->exitRule($listener);
        $listener->exitEveryRule($ctx);
    }
}