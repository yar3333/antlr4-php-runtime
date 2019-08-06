<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class BitSet
{
    public $data;

    function __construct()
    {
        $this->data = [];
    }

    function add($value) : void
    {
        $this->data[$value] = true;
    }

    function or(BitSet $set) : void
    {
        foreach ($set->data as $alt) $this->add($alt);
    }

    function remove($value) : void
    {
        unset($this->data[$value]);
    }

    function contains($value) : bool
    {
        return $this->data[$value] ?? false;
    }

    function values() : array
    {
        return array_keys($this->data);
    }

    function minValue()
    {
        return min($this->values());
    }

    function equals($other) : bool
    {
        if (!($other instanceof self)) return false;
        if (count($this->data) !== count($other->data)) return false;
        foreach ($this->data as $key => $v) if (!$other->contains($key)) return false;
        return true;
    }

    function length() : int
    {
        return count($this->data);
    }

    function __toString()
    {
        return "{" . implode(", ", $this->values()) . "}";
    }
}