<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class AltDict
{
    public $data;

    function __construct()
    {
        $this->data = [];
    }

    function get($key)
    {
        $key = "k-" . $key;
        return $this->data[$key] ?? null;
    }

    function put($key, $value) : void
    {
        $key = "k-" . $key;
        $this->data[$key] = $value;
    }

    function values() : array
    {
        return array_values($this->data);
    }
}