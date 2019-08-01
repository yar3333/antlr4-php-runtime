<?php

namespace Antlr4;

class Logger
{
    public static function log(string $format, ...$args) : void
    {
        printf($format . "\n", ...$args);
    }
}