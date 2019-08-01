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

    /**
     * @var callable
     */
    public $hashFunction;

    /*
     * @var callable
     */
    public $equalsFunction;

    function __construct(callable $hashFunction=null, callable $equalsFunction=null)
    {
        $this->data = [];
        $this->hashFunction = $hashFunction ?? function ($a) { return Utils::standardHashCodeFunction($a); };
        $this->equalsFunction = $equalsFunction ?? function ($a, $b) { return Utils::standardEqualsFunction($a, $b); };
    }

    function size() : int
    {
        $l = 0;
        foreach ($this->data as $hashKey => $v) {
            if (strpos($hashKey, "hash_") === 0) {
                $l += count($this->data[$hashKey]);
            }
        }
        return $l;
    }

    function put($key, $value)
    {
        $hashKey = "hash_" . ($this->hashFunction)($key);

        if (isset($this->data[$hashKey])) {
            $entries = &$this->data[$hashKey];
            foreach ($entries as $entry) {
                if (($this->equalsFunction)($key, $entry['key'])) {
                    $oldValue = $entry['value'];
                    $entry['value'] = $value;
                    return $oldValue;
                }
            }
            $entries[] = ['key' => $key, 'value' => $value];
            return $value;
        }

        $this->data[$hashKey] = [['key' => $key, 'value' => $value]];
        return $value;
    }

    function containsKey($key) : bool
    {
        $hashKey = "hash_" . ($this->hashFunction)($key);
        if (isset($this->data[$hashKey]))
        {
            $entries = $this->data[$hashKey];
            foreach ($entries as $entry)
            {
                if (($this->equalsFunction)($key, $entry['key'])) return true;
            }
        }
        return false;
    }

    function get($key)
    {
        $hashKey = "hash_" . ($this->hashFunction)($key);
        if (isset($this->data[$hashKey]))
        {
            $entries = $this->data[$hashKey];
            foreach ($entries as $entry)
            {
                if (($this->equalsFunction)($key, $entry['key'])) return $entry['value'];
            }
        }
        return null;
    }

    function entries() : array
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

    function keys(): array
    {
        return Utils::arrayMap($this->entries(), function ($e) { return $e['key']; });
    }

    function values(): array
    {
        return Utils::arrayMap($this->entries(), function ($e) { return $e['value']; });
    }


    function __toString()
    {
        $ss = [];
        foreach ($this->entries() as $entry)
        {
            $ss[] = '{' . $entry['key'] . ':' . $entry['value'] . '}';
        }
        return '[' . implode(", ", $ss) . ']';
    }
}
