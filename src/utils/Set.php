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
    public $data;

    /**
     * @var callable
     */
    public $hashFunction;

    /**
     * @var callable
     */
    public $equalsFunction;

    function __construct(callable $hashFunction=null, callable $equalsFunction=null)
    {
        $this->data = [];
        $this->hashFunction = $hashFunction ?? function ($a) { return Utils::standardHashCodeFunction($a); };
        $this->equalsFunction = $equalsFunction ?? function ($a, $b) { return Utils::standardEqualsFunction($a, $b); };
    }

    function length()
    {
        $l = 0;
        foreach ($this->data as $key => $value)
        {
            if (strpos($key, "hash_") === 0)
            {
                $l += count($this->data[$key]);
            }
        }
        return $l;
    }

    function addAll(array $values) : void
    {
        foreach ($values as $v) $this->add($v);
    }

    function add($value)
    {
        $hash = ($this->hashFunction)($value);
        $key = "hash_" . $hash;

        if (isset($this->data[$key]))
        {
            /** @var array $values */
            $values = &$this->data[$key];
            foreach ($values as $v)
            {
                if (($this->equalsFunction)($value, $v)) return $v;
            }
            $values[] = $value;
            return $value;
        }

        $this->data[$key] = [$value];
        return $value;
    }

    function contains($value) : bool
    {
        return $this->get($value) !== null;
    }

    function get($value)
    {
        $hash = ($this->hashFunction)($value);
        $key = "hash_" . $hash;
        if (isset($this->data[$key]))
        {
            $values = $this->data[$key];
            foreach ($values as $i => $v)
            {
                if (($this->equalsFunction)($value, $v)) return $values[$i];
            }
        }
        return null;
    }

    function values() : array
    {
        $l = [];
        foreach ($this->data as $key => $value)
        {
            if (strpos($key, "hash_") === 0)
            {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $l = array_merge($l, $value);
            }
        }
        return $l;
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
