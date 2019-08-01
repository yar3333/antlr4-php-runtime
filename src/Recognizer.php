<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection CascadeStringReplacementInspection */

namespace Antlr4;

use Antlr4\Atn\ATN;
use Antlr4\Atn\ATNSimulator;
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Error\Listeners\ANTLRErrorListener;
use Antlr4\Error\Listeners\ConsoleErrorListener;
use Antlr4\Error\Listeners\ProxyErrorListener;
use Antlr4\Utils\Map;
use Antlr4\Utils\Utils;

abstract class Recognizer
{
    public const EOF = -1;

    /**
     * @var Map[] Original type: Map<Vocabulary, Map<String, Integer>>
     */
    private static $tokenTypeMapCache = [];

    /**
     * @var Map[] Original type: Map<String[], Map<String, Integer>>
     */
    private static $ruleIndexMapCache = [];

    /**
     * @var ANTLRErrorListener[]
     */
    public $_listeners;

    /**
     * @var ATNSimulator
     */
    protected $_interp;

    /**
     * @var int
     */
    public $_stateNumber;

    function __construct()
    {
        $this->_listeners = [ ConsoleErrorListener::INSTANCE() ];
        $this->_interp = null;
        $this->_stateNumber = -1;
    }

    function checkVersion($toolVersion) : void
    {
        $runtimeVersion = "4.7.2";
        if ($runtimeVersion !== $toolVersion)
        {
            Logger::log("ANTLR runtime and generated code versions disagree: $runtimeVersion != $toolVersion");
        }
    }

    function addErrorListener($listener) : void
    {
        $this->_listeners[] = $listener;
    }

    function removeErrorListeners() : void
    {
        $this->_listeners = [];
    }

	/**
	 * Get the vocabulary used by the recognizer.
	 * @return Vocabulary A {@link Vocabulary} instance providing information about the vocabulary used by the grammar.
	 */
	function getVocabulary() : Vocabulary
    {
		return VocabularyImpl::fromTokenNames($this->getTokenNames());
	}

	function getTokenTypeMap() : array
    {
		$vocabulary = $this->getVocabulary();

        $result = self::$tokenTypeMapCache[spl_object_hash($vocabulary)];
        if ($result === null)
        {
            $result = new Map(); //new Map<String, Integer>();
            for ($i = 0; $i <= $this->getATN()->maxTokenType; $i++)
            {
                $literalName = $vocabulary->getLiteralName($i);
                if ($literalName !== null) {
                    $result->put($literalName, $i);
                }

                $symbolicName = $vocabulary->getSymbolicName($i);
                if ($symbolicName !== null) {
                    $result->put($symbolicName, $i);
                }
            }

            $result->put("EOF", Token::EOF);
            self::$tokenTypeMapCache[spl_object_hash($vocabulary)] = $result;
        }

        return $result;
	}


	/**
	 * Get a map from rule names to rule indexes.
	 * <p>Used for XPath and tree pattern compilation.</p>
	 */
	function getRuleIndexMap() : array
    {
		$ruleNames = $this->getRuleNames();
		if ($ruleNames === null) throw new \RuntimeException("The current recognizer does not provide a list of rule names.");

        $result = self::$ruleIndexMapCache[spl_object_hash($ruleNames)];
        if ($result === null) {
            $result = Utils::toMap($ruleNames);
            self::$ruleIndexMapCache[spl_object_hash($ruleNames)] = $result;
        }

        return $result;
	}

    function getTokenType($tokenName) : int
    {
        $ttype = $this->getTokenTypeMap()[$tokenName];
        return $ttype ?? Token::INVALID_TYPE;
    }

    // What is the error header, normally line/character position information?
    function getErrorHeader(RecognitionException $e) : string
    {
        $line = $e->offendingToken->line;
        $column = $e->offendingToken->charPositionInLine;
        return "line " . $line . ":" . $column;
    }

    // How should a token be displayed in an error message? The default
    //  is to display just the text, but during development you might
    //  want to have a lot of information spit out.  Override in that case
    //  to use t.toString() (which, for CommonToken, dumps everything about
    //  the token). This is better than forcing you to override a method in
    //  your token objects because you don't have to go modify your lexer
    //  so that it creates a new Java type.
    //
    // @deprecated This method is not called by the ANTLR 4 Runtime. Specific
    // implementations of {@link ANTLRErrorStrategy} may provide a similar
    // feature when necessary. For example, see
    // {@link DefaultErrorStrategy//getTokenErrorDisplay}.
    function getTokenErrorDisplay(Token $t) : string
    {
        if ($t===null) return "<no token>";

        $s = $t->getText();
        if ($s === null)
        {
            if ($t->type===Token::EOF)
            {
                $s = "<EOF>";
            }
            else
            {
                $s = "<" . $t->type . ">";
            }

        }
        $s = str_replace("\n","\\n", $s);
        $s = str_replace("\r","\\r", $s);
        $s = str_replace("\t","\\t", $s);

        return "'$s'";
    }

    function getErrorListenerDispatch() : ANTLRErrorListener
    {
        return new ProxyErrorListener($this->_listeners);
    }

    /**
     * Subclass needs to override these if there are sempreds or actions that the ATN interp needs to execute
     * @param RuleContext $localctx
     * @param int $ruleIndex
     * @param int $actionIndex
     * @return bool
     */
    function sempred(?RuleContext $localctx, int $ruleIndex, int $actionIndex) : bool
    {
        return true;
    }

    function precpred(?RuleContext $localctx , int $precedence) : bool
    {
        return true;
    }

    //Indicate that the recognizer has changed internal state that is
    //consistent with the ATN state passed in.  This way we always know
    //where we are in the ATN as the parser goes along. The rule
    //context objects form a stack that lets us see the stack of
    //invoking rules. Combine this and we have complete ATN
    //configuration information.
    function getState() : int { return $this->_stateNumber; }
    function setState($state) : void { $this->_stateNumber = $state; }

    function getInterpreter() : ?ATNSimulator { return $this->_interp; }

    /**
     * If this recognizer was generated, it will have a serialized ATN representation of the grammar.
     * <p>For interpreters, we don't know their serialized ATN despite having created the interpreter from it.</p>
     */
    function getSerializedATN() : string
    {
        throw new \RuntimeException("there is no serialized ATN");
    }

	function action(RuleContext $_localctx, int $ruleIndex, int $actionIndex) : void {}

	/**
	 * @deprecated
     */
    abstract function getTokenNames() : array;

    abstract function getRuleNames() : \ArrayObject;

    abstract function getATN() : ATN;
}
