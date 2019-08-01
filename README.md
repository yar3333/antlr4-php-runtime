# ANTLR4 runtime for PHP

[![Build Status](https://travis-ci.org/yar3333/antlr4.svg?branch=master)](https://travis-ci.org/yar3333/antlr4)
[![Latest Stable Version](https://poser.pugx.org/antlr4/antlr4/version)](https://packagist.org/packages/antlr4/antlr4)
[![Total Downloads](https://poser.pugx.org/antlr4/antlr4/downloads)](https://packagist.org/packages/antlr4/antlr4)

PHP 7.1+ runtime for ANTLR4. See [antlr4-php-workspace](https://github.com/yar3333/antlr4-php-workspace).

## Installation
```
composer require "antlr4/antlr4"
```

## Using

Download [ANTLR4 with PHP support](https://github.com/yar3333/antlr4-php-workspace/releases).

Write a grammar file (named `Gram.g4` below).

Generate lexer and parser PHP classes. For example, for Windows:
```
SET CLASSPATH=my-path-to-jar\antlr4-4.7.2-complete.jar;%CLASSPATH%
java org.antlr.v4.Tool -o Generated -Dlanguage=Php -visitor -no-listener -package MyPackage\Generated Gram.g4
```

Write your visitor class:
```php
<?php
use MyPackage\Generated\Contexts\AContext;
use MyPackage\Generated\Contexts\BContext;

class MyVisitor extends Generated\GramBaseVisitor
{
    function visitA(AContext $ctx)
    {
        return $this->visitChildren($ctx);
    }

    function visitB(BContext $ctx)
    {
        return $this->visitChildren($ctx);
    }
}
```

Use visitor to parse expression:
```php
<?php
use Antlr4\CharStreams;
use Antlr4\CommonTokenStream;
use MyPackage\Generated\GramLexer;
use MyPackage\Generated\GramParser;

$lexer = new GramLexer(CharStreams::fromString("foo * bar"));
$tokens = new CommonTokenStream($lexer);
$parser = new GramParser($tokens);

$treeA = $parser->a();
$visitor = new MyVisitor();
$rA = $visitor->visit($treeA);
echo $rA;
```

Please, see [examples](https://github.com/yar3333/antlr4-php-workspace/tree/master/examples) for details.
