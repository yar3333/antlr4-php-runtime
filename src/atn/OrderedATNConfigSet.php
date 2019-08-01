<?php

namespace Antlr4\Atn;

use Antlr4\Utils\Set;

class OrderedATNConfigSet extends ATNConfigSet
{
    function __construct()
    {
        parent::__construct();

        $this->configLookup = new Set();
    }
}