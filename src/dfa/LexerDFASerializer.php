<?php

namespace Antlr4\Dfa;

use Antlr4\Utils\Utils;
use Antlr4\VocabularyImpl;

class LexerDFASerializer extends DFASerializer
{
    function __construct(DFA $dfa)
    {
        parent::__construct($dfa, new VocabularyImpl(null , null));
    }

    function getEdgeLabel(int $i) : string
    {
        return "'" . Utils::fromCharCode($i) . "'";
    }
}