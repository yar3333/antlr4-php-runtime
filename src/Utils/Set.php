<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class Set implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var callable
     */
    public $equalsFunction;

    function __construct()
    {
        $this->data = [];
        $this->equalsFunction = function ($a, $b) { return Utils::standardEqualsFunction($a, $b); };
    }

    function length()
    {
        return count($this->data);
    }

    function addAll(array $values) : void
    {
        foreach ($values as $v) $this->add($v);
    }

    function add($value)
    {
        foreach ($this->data as $v)
        {
            if (($this->equalsFunction)($value, $v)) return $v;
        }
        $this->data[] = $value;
        return $value;
    }

    function contains($value) : bool
    {
        return $this->get($value) !== null;
    }

    function get($value)
    {
        foreach ($this->data as $v)
        {
            if (($this->equalsFunction)($value, $v)) return $v;
        }
        return null;
    }

    function values() : array
    {
        return $this->data;
    }

    function isEmpty() : bool { return !$this->data; }

    function __toString()
    {
        return Utils::arrayToString($this->values());
    }

    function getIterator() : \Iterator
    {
        return new \ArrayIterator($this->data);
    }
}
