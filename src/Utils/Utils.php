<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/** @noinspection UnnecessaryParenthesesInspection */

namespace Antlr4\Utils;

class Utils
{
    static function arrayToString(array $a) : string
    {
        return "[" . implode(", ", $a) . "]";
    }

    static function standardEqualsFunction(object $a, object $b) : bool
    {
        if ($a === null && $b === null) return true;
        if ($a === null || $b === null) return false;
        return $a->equals($b);
    }

    static function escapeWhitespace(string $s, bool $escapeSpaces) : string
    {
        $s = preg_replace('/\n/', "\\n", $s);
        $s = preg_replace('/\r/', "\\r", $s);
        $s = preg_replace('/\t/', "\\t", $s);

        if ($escapeSpaces)
        {
            $s = preg_replace('/ /', "\u00B7", $s);
        }

        return $s;
    }

    static function titleCase($str) : string
    {
        return preg_replace_callback('/\w\S*/', function($txt) { return mb_strtoupper($txt[0]) . substr($txt, 1); }, $str);
    }

    static function equalArrays(?array $a, ?array $b) : bool
    {
        if ($a === $b) return true;
        if (!is_array($a) || !is_array($b)) return false;
        if (count($a) !== count($b)) return false;
        $count = count($a);
        for ($i = 0; $i < $count; $i++)
        {
            if ($a[$i] === $b[$i]) continue;
            if (!$a[$i]->equals($b[$i])) return false;
        }
        return true;
    }

    static function fromCodePoint(...$codes) : string
    {
        $s = '';
        foreach ($codes as $code) $s .= mb_chr($code, 'UTF-8');
        return $s;
    }

    static function codePointAt(string $s, int $pos) : int
    {
        return mb_ord(mb_substr($s, $pos, 1, 'UTF-8'), 'UTF-8');
    }

    static function charCodeAt(string $s, int $pos) : int
    {
        return mb_ord(mb_substr($s, $pos, 1, 'UTF-8'), 'UTF-8');
    }

    static function fromCharCode(int $code) : string
    {
        return mb_chr($code, 'UTF-8');
    }

    static function arrayMap(array $arr, callable $func) : array
    {
        return array_map($func, $arr);
    }

    static function minObjects(array $objectsWithCompareTo) : object
    {
        $i = new \ArrayIterator($objectsWithCompareTo);

        $candidate = $i->current(); $i->next();

        while ($i->valid())
        {
            $next = $i->current(); $i->next();
            if ($next->compareTo($candidate) < 0) $candidate = $next;
        }

        return $candidate;
    }

    /**
     * @param string[]|\ArrayObject $keys
     * @return Map<string, Integer>
     */
    static function toMap($keys) : Map
    {
		$r = new Map();
		foreach ($keys as $i => $v)
		{
		    $r->put($v, $i);
        }
        return $r;
    }
}
