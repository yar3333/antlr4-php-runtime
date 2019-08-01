<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class Map
{
    /**
     * @var array
     */
    public $data;

    /*
     * @var callable
     */
    public $equalsFunction;

    function __construct()
    {
        $this->data = [];
        $this->equalsFunction = function ($a, $b) { return Utils::standardEqualsFunction($a, $b); };
    }

    function size() : int
    {
        return count($this->data);
    }

    function put($key, $value)
    {
        foreach ($this->data as $entry)
        {
            if (($this->equalsFunction)($key, $entry['key']))
            {
                $oldValue = $entry['value'];
                $entry['value'] = $value;
                return $oldValue;
            }
        }
        $this->data[] = [ 'key' => $key, 'value' => $value ];
        return $value;
    }

    function containsKey($key) : bool
    {
        foreach ($this->data as $entry)
        {
            if (($this->equalsFunction)($key, $entry['key']))
            {
                return true;
            }
        }
        return false;
    }

    function get($key)
    {
        foreach ($this->data as $entry)
        {
            if (($this->equalsFunction)($key, $entry['key']))
            {
                return $entry['value'];
            }
        }
        return null;
    }

    function entries() : array
    {
        return $this->data;
    }

    function keys(): array
    {
        return Utils::arrayMap($this->data, function ($e) { return $e['key']; });
    }

    function values(): array
    {
        return Utils::arrayMap($this->data, function ($e) { return $e['value']; });
    }

    function __toString()
    {
        $ss = [];
        foreach ($this->data as $entry)
        {
            $ss[] = '{' . $entry['key'] . ':' . $entry['value'] . '}';
        }
        return '[' . implode(", ", $ss) . ']';
    }
}
