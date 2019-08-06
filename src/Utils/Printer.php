<?php

namespace Antlr4\Utils;

interface Printer
{
    function print($s) : void;
    function println($s="") : void;
}