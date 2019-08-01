<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection SenselessMethodDuplicationInspection */
/** @noinspection PhpUnusedParameterInspection */

namespace Antlr4;

use Antlr4\Atn\ATN;
use Antlr4\Atn\ATNDeserializationOptions;
use Antlr4\Atn\ATNDeserializer;
use Antlr4\Atn\ATNSimulator;
use Antlr4\Atn\ParserATNSimulator;
use Antlr4\Error\DefaultErrorStrategy;
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Tree\ErrorNode;
use Antlr4\Tree\ErrorNodeImpl;
use Antlr4\Tree\ParseTreeListener;
use Antlr4\Tree\TerminalNode;
use Antlr4\Tree\TerminalNodeImpl;
use Antlr4\Utils\Printer;

abstract class Parser extends Recognizer
{
    /**
     * this field maps from the serialized ATN string to the deserialized {@link ATN} with bypass alternatives.
     * @see ATNDeserializationOptions//isGenerateRuleBypassTransitions()
     * @var ATN[]
     */
    static $bypassAltsAtnCache = [];

    /**
     * @var TokenStream
     */
    public $_input;

    /**
     * @var DefaultErrorStrategy
     */
    public $_errHandler;

    /**
     * @var int[]
     */
    public $_precedenceStack;

    /**
     * @var ParserRuleContext
     */
    protected $_ctx;

    /**
     * @var bool 
     */
    protected $_buildParseTrees;

    public $_tracer;

    /**
     * @var ParseTreeListener[]
     */
    public $_parseListeners;

    public $_syntaxErrors;

    /**
     * @var Printer
     */
    public $printer;

    function __construct(TokenStream $input)
    {
        parent::__construct();

        // The input stream.
        $this->_input = null;

        // The error handling strategy for the parser. The default value is a new
        // instance of {@link DefaultErrorStrategy}.
        $this->_errHandler = new DefaultErrorStrategy();

        $this->_precedenceStack = [ 0 ];

        // The {@link ParserRuleContext} object for the currently executing rule.
        // this is always non-null during the parsing process.
        $this->_ctx = null;

        // Specifies whether or not the parser should construct a parse tree during
        // the parsing process. The default value is {@code true}.
        $this->_buildParseTrees = true;

        // When {@link //setTrace}{@code (true)} is called, a reference to the
        // {@link TraceListener} is stored here so it can be easily removed in a
        // later call to {@link //setTrace}{@code (false)}. The listener itself is
        // implemented as a parser listener so this field is not directly used by
        // other parser methods.
        $this->_tracer = null;

        // The list of {@link ParseTreeListener} listeners registered to receive
        // events during the parse.
        $this->_parseListeners = null;

        // The number of syntax errors reported during parsing. this value is
        // incremented each time {@link //notifyErrorListeners} is called.
        $this->_syntaxErrors = 0;

        $this->setInputStream($input);
    }

    // reset the parser's state
    function reset() : void
    {
        if ($this->_input) $this->_input->seek(0);
        $this->_errHandler->reset($this);
        $this->_ctx = null;
        $this->_syntaxErrors = 0;
        $this->setTrace(false);
        $this->_precedenceStack = [ 0 ];


        $interpreter = $this->getInterpreter();
		if ($interpreter) $interpreter->reset();
    }

    // Match current input symbol against {@code ttype}. If the symbol type
    // matches, {@link ANTLRErrorStrategy//reportMatch} and {@link //consume} are
    // called to complete the match process.
    //
    // <p>If the symbol type does not match,
    // {@link ANTLRErrorStrategy//recoverInline} is called on the current error
    // strategy to attempt recovery. If {@link //getBuildParseTree} is
    // {@code true} and the token index of the symbol returned by
    // {@link ANTLRErrorStrategy//recoverInline} is -1, the symbol is added to
    // the parse tree by calling {@link ParserRuleContext//addErrorNode}.</p>
    //
    // @param ttype the token type to match
    // @return the matched symbol
    // @throws RecognitionException if the current input symbol did not match
    // {@code ttype} and the error strategy could not recover from the mismatched symbol
    function match($ttype) : Token
    {
        $t = $this->getCurrentToken();
        if ($t->type === $ttype)
        {
            $this->_errHandler->reportMatch($this);
            $this->consume();
        }
        else
        {
            $t = $this->_errHandler->recoverInline($this);
            if ($this->_buildParseTrees && $t->tokenIndex === -1)
            {
                // we must have conjured up a new token during single token insertion if it's not the current symbol
                $this->_ctx->addErrorNode($this->createErrorNode($this->_ctx, $t));
            }
        }

        return $t;
    }

    // Match current input symbol as a wildcard. If the symbol type matches
    // (i.e. has a value greater than 0), {@link ANTLRErrorStrategy//reportMatch}
    // and {@link //consume} are called to complete the match process.
    //
    // <p>If the symbol type does not match,
    // {@link ANTLRErrorStrategy//recoverInline} is called on the current error
    // strategy to attempt recovery. If {@link //getBuildParseTree} is
    // {@code true} and the token index of the symbol returned by
    // {@link ANTLRErrorStrategy//recoverInline} is -1, the symbol is added to
    // the parse tree by calling {@link ParserRuleContext//addErrorNode}.</p>
    //
    // @return the matched symbol
    // @throws RecognitionException if the current input symbol did not match
    // a wildcard and the error strategy could not recover from the mismatched symbol
    function matchWildcard() : Token
    {
        $t = $this->getCurrentToken();
        if ($t->type > 0)
        {
            $this->_errHandler->reportMatch($this);
            $this->consume();
        }
        else
        {
            $t = $this->_errHandler->recoverInline($this);
            if ($this->_buildParseTrees && $t->tokenIndex === -1)
            {
                // we must have conjured up a new token during single token insertion if it's not the current symbol
                $this->_ctx->addErrorNode($this->createErrorNode($this->_ctx, $t));
            }
        }
        return $t;
    }

    /**
     * @return ParseTreeListener[]
     */
    function getParseListeners() : array
    {
        return $this->_parseListeners ?? [];
    }

    // Registers {@code listener} to receive events during the parsing process.
    //
    // <p>To support output-preserving grammar transformations (including but not
    // limited to left-recursion removal, automated left-factoring, and
    // optimized code generation), calls to listener methods during the parse
    // may differ substantially from calls made by
    // {@link ParseTreeWalker//DEFAULT} used after the parse is complete. In
    // particular, rule entry and exit events may occur in a different order
    // during the parse than after the parser. In addition, calls to certain
    // rule entry methods may be omitted.</p>
    //
    // <p>With the following specific exceptions, calls to listener events are
    // <em>deterministic</em>, i.e. for identical input the calls to listener
    // methods will be the same.</p>
    //
    // <ul>
    // <li>Alterations to the grammar used to generate code may change the
    // behavior of the listener calls.</li>
    // <li>Alterations to the command line options passed to ANTLR 4 when
    // generating the parser may change the behavior of the listener calls.</li>
    // <li>Changing the version of the ANTLR Tool used to generate the parser
    // may change the behavior of the listener calls.</li>
    // </ul>
    //
    // @param listener the listener to add
    //
    // @throws NullPointerException if {@code} listener is {@code null}
    function addParseListener(ParseTreeListener $listener) : void
    {
        if ($this->_parseListeners === null)
        {
            $this->_parseListeners = [];
        }
        $this->_parseListeners[] = $listener;
    }

    // Remove {@code listener} from the list of parse listeners.
    //
    // <p>If {@code listener} is {@code null} or has not been added as a parse
    // listener, this method does nothing.</p>
    // @param listener the listener to remove
    function removeParseListener(?ParseTreeListener $listener) : void
    {
        if ($this->_parseListeners === null) return;

        $idx = array_search($listener, $this->_parseListeners, true);

        if ($idx !== false)
        {
            array_splice($this->_parseListeners, $idx, 1);
        }

        if (!$this->_parseListeners)
        {
            $this->_parseListeners = null;
        }
    }

    // Remove all parse listeners.
    function removeParseListeners() : void
    {
        $this->_parseListeners = null;
    }

    // Notify any parse listeners of an enter rule event.
    function triggerEnterRuleEvent() : void
    {
        if ($this->_parseListeners !== null)
        {
            $ctx = $this->_ctx;
            foreach ($this->_parseListeners as $listener)
            {
                $listener->enterEveryRule($ctx);
                $ctx->enterRule($listener);
            }
        }
    }

    // Notify any parse listeners of an exit rule event.
    //
    // @see //addParseListener
    function triggerExitRuleEvent() : void
    {
        if ($this->_parseListeners !== null)
        {
            // reverse order walk of listeners
            $ctx = $this->_ctx;
            foreach (array_reverse($this->_parseListeners) as $listener)
            {
                $ctx->exitRule($listener);
                $listener->exitEveryRule($ctx);
            }
        }
    }

    function getTokenFactory() : TokenFactory
    {
        return $this->_input->tokenSource()->getTokenFactory();
    }

    // Tell our token source and error strategy about a new way to create tokens.//
    function setTokenFactory(TokenFactory $factory) : void
    {
        $this->_input->tokenSource()->setTokenFactory($factory);
    }

    // The ATN with bypass alternatives is expensive to create so we create it lazily.
    // @throws UnsupportedOperationException if the current parser does not implement the {@link //getSerializedATN()} method.
    function getATNWithBypassAlts()
    {
        $serializedAtn = $this->getSerializedATN();
        if ($serializedAtn === null)
        {
            throw new \RuntimeException("The current parser does not support an ATN with bypass alternatives.");
        }
        $result = self::$bypassAltsAtnCache[$serializedAtn];
        if ($result === null)
        {
            $deserializationOptions = new ATNDeserializationOptions();
            $deserializationOptions->generateRuleBypassTransitions = true;
            $result = (new ATNDeserializer($deserializationOptions))->deserialize($serializedAtn);
            self::$bypassAltsAtnCache[$serializedAtn] = $result;
        }
        return $result;
    }

    // The preferred method of getting a tree pattern. For example, here's a sample use:
    // <pre>
    // ParseTree t = parser.expr();
    // ParseTreePattern p = parser.compileParseTreePattern("&lt;ID&gt;+0",
    // MyParser.RULE_expr);
    // ParseTreeMatch m = p.match(t);
    // String id = m.get("ID");
    // </pre>
    /*function compileParseTreePattern($pattern, $patternRuleIndex, $lexer)
    {
        if (!$lexer)
        {
            if ($this->getTokenStream())
            {
                $tokenSource = $this->getTokenStream()->tokenSource();
                if ($tokenSource instanceof Lexer)
                {
                    $lexer = $tokenSource;
                }
            }
        }
        if (!$lexer)
        {
            throw new \RuntimeException("Parser can't discover a lexer to use");
        }
        $m = new ParseTreePatternMatcher($lexer, $this);
        return $m->compile($pattern, $patternRuleIndex);
    }*/

    function getInputStream() : TokenStream
    {
        return $this->getTokenStream();
    }

    function setInputStream(TokenStream $input) : void
    {
        $this->setTokenStream($input);
    }

    function getTokenStream() : TokenStream
    {
        return $this->_input;
    }

    // Set the token stream and reset the parser.//
    function setTokenStream(TokenStream $input) : void
    {
        $this->_input = null;
        $this->reset();
        $this->_input = $input;
    }

    // Match needs to return the current input symbol, which gets put
    // into the label for the associated token ref; e.g., x=ID.
    function getCurrentToken() : Token
    {
        return $this->_input->LT(1);
    }

    function notifyErrorListeners(string $msg, Token $offendingToken=null, RecognitionException $err=null) : void
    {
        if ($offendingToken === null)
        {
            $offendingToken = $this->getCurrentToken();
        }
        $this->_syntaxErrors++;
        $line = $offendingToken->line;
        $column = $offendingToken->charPositionInLine;
        $listener = $this->getErrorListenerDispatch();
        $listener->syntaxError($this, $offendingToken, $line, $column, $msg, $err);
    }

    // Consume and return the {@linkplain //getCurrentToken current symbol}.
    //
    // <p>E.g., given the following input with {@code A} being the current
    // lookahead symbol, this function moves the cursor to {@code B} and returns
    // {@code A}.</p>
    //
    // <pre>
    // A B
    // ^
    // </pre>
    //
    // If the parser is not in error recovery mode, the consumed symbol is added
    // to the parse tree using {@link ParserRuleContext//addChild(Token)}, and
    // {@link ParseTreeListener//visitTerminal} is called on any parse listeners.
    // If the parser <em>is</em> in error recovery mode, the consumed symbol is
    // added to the parse tree using
    // {@link ParserRuleContext//addErrorNode(Token)}, and
    // {@link ParseTreeListener//visitErrorNode} is called on any parse listeners.
    function consume() : Token
    {
		$o = $this->getCurrentToken();
		if ($o->type !== Token::EOF)
		{
			$this->getInputStream()->consume();
		}

		if ($this->_buildParseTrees || $this->_parseListeners)
		{
			if ($this->_errHandler->inErrorRecoveryMode($this))
			{
				/** @var ErrorNode $node */
				$node = $this->_ctx->addErrorNode($this->createErrorNode($this->_ctx, $o));
				if ($this->_parseListeners)
				{
					foreach ($this->_parseListeners as $listener)
					{
						$listener->visitErrorNode($node);
					}
				}
			}
			else
			{
				/** @var TerminalNode $node */
				$node = $this->_ctx->addChild($this->createTerminalNode($this->_ctx, $o));
				if ($this->_parseListeners)
				{
					foreach ($this->_parseListeners as $listener)
					{
						$listener->visitTerminal($node);
					}
				}
			}
		}
		return $o;
    }

    function createTerminalNode(ParserRuleContext $parent, Token $t) : TerminalNode
    {
		return new TerminalNodeImpl($t);
	}

	function createErrorNode(ParserRuleContext $parent, Token $t) : ErrorNode
    {
		return new ErrorNodeImpl($t);
	}

    protected function addContextToParseTree() : void
    {
        /** @var ParserRuleContext $parent */
        $parent = $this->_ctx->getParent();
		// add current context to parent if we have a parent
		if ($parent) $parent->addChild($this->_ctx);
    }

    // Always called by generated parsers upon entry to a rule. Access field
    // {@link //_ctx} get the current context.
    function enterRule(ParserRuleContext $localctx, int $state, int $ruleIndex) : void
    {
        $this->setState($state);
        $this->_ctx = $localctx;
        $this->_ctx->start = $this->_input->LT(1);
        if ($this->_buildParseTrees) $this->addContextToParseTree();
        if ($this->_parseListeners !== null) $this->triggerEnterRuleEvent();
    }

    function exitRule() : void
    {
        $this->_ctx->stop = $this->_input->LT(-1);
        // trigger event on _ctx, before it reverts to parent
        if ($this->_parseListeners !== null)
        {
            $this->triggerExitRuleEvent();
        }
        $this->setState($this->_ctx->invokingState);
        $this->_ctx = $this->_ctx->getParent();
    }

    function enterOuterAlt(ParserRuleContext $localctx, int $altNum) : void
    {
        $localctx->setAltNumber($altNum);
        // if we have new localctx, make sure we replace existing ctx
        // that is previous child of parse tree
        if ($this->_buildParseTrees && $this->_ctx !== $localctx)
        {
            /** @var ParserRuleContext $parent */
            $parent = $this->_ctx->getParent();
            if ($parent)
            {
                $parent->removeLastChild();
                $parent->addChild($localctx);
            }
        }
        $this->_ctx = $localctx;
    }

    // Get the precedence level for the top-most precedence rule.
    // @return The precedence level for the top-most precedence rule, or -1 if
    // the parser context is not nested within a precedence rule.
    function getPrecedence() : int
    {
        if (count($this->_precedenceStack) === 0) return -1;
        return $this->_precedenceStack[count($this->_precedenceStack)-1];
    }

    function enterRecursionRule($localctx, $state, $ruleIndex, $precedence) : void
    {
        $this->setState($state);
        $this->_precedenceStack[] = $precedence;
        $this->_ctx = $localctx;
        $this->_ctx->start = $this->_input->LT(1);
        if ($this->_parseListeners !== null)
        {
            $this->triggerEnterRuleEvent();// simulates rule entry for left-recursive rules
        }
    }

    // Like {@link //enterRule} but for recursive rules.
    function pushNewRecursionContext(ParserRuleContext $localctx, int $state, int $ruleIndex) : void
    {
        $previous = $this->_ctx;
        $previous->setParent($localctx);
        $previous->invokingState = $state;
        $previous->stop = $this->_input->LT(-1);

        $this->_ctx = $localctx;
        $this->_ctx->start = $previous->start;
        if ($this->_buildParseTrees)
        {
            $this->_ctx->addChild($previous);
        }
        if ($this->_parseListeners !== null)
        {
            $this->triggerEnterRuleEvent();// simulates rule entry for left-recursive rules
        }
    }

    function unrollRecursionContexts(?ParserRuleContext $parentCtx) : void
    {
        array_pop($this->_precedenceStack);
        $this->_ctx->stop = $this->_input->LT(-1);
        $retCtx = $this->_ctx;// save current ctx (return value)
        // unroll so _ctx is as it was before call to recursive method
        if ($this->_parseListeners !== null)
        {
            while ($this->_ctx !== $parentCtx)
            {
                $this->triggerExitRuleEvent();
                $this->_ctx = $this->_ctx->getParent();
            }
        }
        else
        {
            $this->_ctx = $parentCtx;
        }
        // hook into tree
        $retCtx->setParent($parentCtx);
        if ($this->_buildParseTrees && $parentCtx)
        {
            // add return ctx into invoking rule's tree
            $parentCtx->addChild($retCtx);
        }
    }

    function getInvokingContext($ruleIndex) : ?ParserRuleContext
    {
        $ctx = $this->_ctx;
        while ($ctx)
        {
            if ($ctx->getRuleIndex() === $ruleIndex) return $ctx;
            $ctx = $ctx->getParent();
        }
        return null;
    }

    function precpred(?RuleContext $localctx, int $precedence) : bool
    {
        return $precedence >= $this->_precedenceStack[count($this->_precedenceStack) - 1];
    }

    function inContext($context) : bool
    {
        // TODO: useful in parser?
        return false;
    }

    // Checks whether or not {@code symbol} can follow the current state in the
    // ATN. The behavior of this method is equivalent to the following, but is
    // implemented such that the complete context-sensitive follow set does not
    // need to be explicitly constructed.
    //
    // <pre>
    // return getExpectedTokens().contains(symbol);
    // </pre>
    //
    // @param symbol the symbol type to check
    // @return {@code true} if {@code symbol} can follow the current state in
    // the ATN, otherwise {@code false}.
    function isExpectedToken(int $symbol) : bool
    {
        $atn = $this->_interp->atn;
        $ctx = $this->_ctx;
        $s = $atn->states[$this->getState()];
        $following = $atn->nextTokens($s);

        if ($following->contains($symbol)) return true;
        if (!$following->contains(Token::EPSILON)) return false;

        while ($ctx !== null && $ctx->invokingState >= 0 && $following->contains(Token::EPSILON))
        {
            $invokingState = $atn->states[$ctx->invokingState];
            $rt = $invokingState->transitions[0];
            $following = $atn->nextTokens($rt->followState);
            if ($following->contains($symbol))
            {
                return true;
            }
            $ctx = $ctx->getParent();
        }

        return $following->contains(Token::EPSILON) && $symbol === Token::EOF;
    }

    // Computes the set of input symbols which could follow the current parser
    // state and context, as given by {@link //getState} and {@link //getContext},
    // respectively.
    //
    // @see ATN//getExpectedTokens(int, RuleContext)
    function getExpectedTokens() : IntervalSet
    {
        return $this->_interp->atn->getExpectedTokens($this->getState(), $this->_ctx);
    }

    function getExpectedTokensWithinCurrentRule() : IntervalSet
    {
        $atn = $this->_interp->atn;
        $s = $atn->states[$this->getState()];
        return $atn->nextTokens($s);
    }

    // Get a rule's index (i.e., {@code RULE_ruleName} field) or -1 if not found.//
    function getRuleIndex($ruleName)
    {
        $ruleIndex = $this->getRuleIndexMap()[$ruleName];
        if ($ruleIndex !== null) return $ruleIndex;
        return -1;
    }

    // Return List&lt;String&gt; of the rule names in your parser instance
    // leading up to a call to the current rule. You could override if
    // you want more details such as the file/line info of where
    // in the ATN a rule is invoked.
    //
    // this is very useful for error messages.
    function getRuleInvocationStack(ParserRuleContext $p=null) : array
    {
        if (!$p) $p = $this->_ctx;

        $stack = [];
        while ($p)
        {
            // compute what follows who invoked us
            $ruleIndex = $p->getRuleIndex();
            if ($ruleIndex < 0)
            {
                $stack[] = "n/a";
            }
            else
            {
                $stack[] = $this->getRuleNames()[$ruleIndex];
            }
            $p = $p->getParent();
        }
        return $stack;
    }

    // For debugging and other purposes.
    function getDFAStrings() : string
    {
        return "[" . implode(", ", $this->getInterpreter()->decisionToDFA) . "]";
    }

    // For debugging and other purposes.
    function dumpDFA() : void
    {
        $seenOne = false;
        foreach ($this->getInterpreter()->decisionToDFA as $dfa)
        {
            if (!$dfa->states->isEmpty())
            {
                if ($seenOne) $this->printer->println();
                $this->printer->println("Decision " . $dfa->decision . ":");
                $this->printer->print($dfa->toString($this->getVocabulary()));
                $seenOne = true;
            }
        }
    }

    function getSourceName() : string
    {
        return $this->_input->getSourceName();
    }

    // During a parse is sometimes useful to listen in on the rule entry and exit
    // events as well as token matches. this is for quick and dirty debugging.
    function setTrace($trace) : void
    {
        if (!$trace)
        {
            $this->removeParseListener($this->_tracer);
            $this->_tracer = null;
        }
        else
        {
            if ($this->_tracer !== null)
            {
                $this->removeParseListener($this->_tracer);
            }
            $this->_tracer = new ParserTraceListener($this);
            $this->addParseListener($this->_tracer);
        }
    }

	function getContext() : ParserRuleContext { return $this->_ctx;	}
	function setContext(ParserRuleContext $ctx) : void { $this->_ctx = $ctx; }

    /**
     * @return ParserATNSimulator
     */
	function getInterpreter() : ?ATNSimulator { return $this->_interp; }
}
