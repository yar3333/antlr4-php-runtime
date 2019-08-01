<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class Hash
{
    /**
     * @var int
     */
    public $count;

    /**
     * @var int
     */
    public $hash;

    function __construct()
    {
        $this->count = 0;
        $this->hash = 0;
    }

    function update(...$arguments) : void
    {
        foreach ($arguments as $value)
        {
            if ($value === null) continue;

            if (is_array($value))
            {
                foreach ($value as $v) $this->update($v);
            }
            else
            {
                if (is_string($value)) $k = Utils::hashCode($value);
                else
                if (is_int($value)) $k = $value;
                else
                if (is_float($value)) $k = $value;
                else
                if (is_bool($value)) $k = $value ? 1 : 0;
                else
                {
                    if (method_exists($value, 'updateHashCode')) {
                        $value->updateHashCode($this);
                    }
                    continue;
                }

                $k *= 0xCC9E2D51;
                $k = ($k << 15) | ($k >> (32 - 15));
                $k *= 0x1B873593;

                $this->count++;

                $hash = $this->hash ^ $k;
                $hash = ($hash << 13) | ($hash >> (32 - 13));
                $hash = $hash * 5 + 0xE6546B64;

                $this->hash = $hash;
            }
        }
    }

    function finish() : int
    {
        $hash = $this->hash ^ ($this->count * 4);
        $hash ^= $hash >> 16;
        $hash *= 0x85EBCA6B;
        $hash ^= $hash >> 13;
        $hash *= 0xC2B2AE35;
        $hash ^= $hash >> 16;
        return $hash;
    }
}