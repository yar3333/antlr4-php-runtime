<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

use Antlr4\Utils\Utils;

/**
 * Vacuum all input from a string and then treat it like a buffer.
 */
class InputStream implements CharStream
{
    /**
     * @var int
     */
    protected $_index;

    /**
     * @var int
     */
    protected $_size;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $strdata;

    /**
     * @var bool
     */
    public $decodeToUnicodeCodePoints;

    /**
     * @var array
     */
    public $data;

    // If decodeToUnicodeCodePoints is true, the input is treated
    // as a series of Unicode code points.
    // Otherwise, the input is treated as a series of 16-bit UTF-16 code
    // units.
    function __construct(string $data, bool $decodeToUnicodeCodePoints=false)
    {
        $this->name = "<empty>";
        $this->strdata = $data;
        $this->decodeToUnicodeCodePoints = $decodeToUnicodeCodePoints;
        self::_loadString($this);
    }

    static function _loadString(InputStream $stream) : void
    {
        $stream->_index = 0;
        $stream->data = [];
        if ($stream->decodeToUnicodeCodePoints) {
            for ($i = 0, $length = mb_strlen($stream->strdata, 'UTF-8'); $i < $length; $i++) {
                $stream->data[] = mb_ord(mb_substr($stream->strdata, $i, 1, 'UTF-8'), 'UTF-8');
            }
        } else {
            for ($i = 0, $length = strlen($stream->strdata); $i < $length; $i++) {
                $stream->data[] = ord($stream->strdata[$i]);
            }
        }

        $stream->_size = count($stream->data);
    }

    function index() : int { return $this->_index; }

    function size() : int { return $this->_size; }

    // Reset the stream so that it's in the same state it was
    // when the object was created *except* the data array is not touched.
    function reset() : void
    {
        $this->_index = 0;
    }

    function consume() : void
    {
        if ($this->_index >= $this->_size)
        {
            // assert this.LA(1) == Token.EOF
            throw new \RuntimeException("cannot consume EOF");
        }
        $this->_index++;
    }

    function LA(int $offset) : int
    {
        if ($offset === 0)
        {
            return 0;// undefined
        }
        if ($offset < 0)
        {
            $offset++;// e.g., translate LA(-1) to use offset=0
        }
        $pos = $this->_index + $offset - 1;
        if ($pos < 0 || $pos >= $this->_size)
        {
            // invalid
            return Token::EOF;
        }
        return $this->data[$pos];
    }

    function LT(int $offset) : int
    {
        return $this->LA($offset);
    }

    // mark/release do nothing; we have entire buffer
    function mark() : int
    {
        return -1;
    }

    function release($marker) : void
    {
    }

    // consume() ahead until p==_index; can't just set p=_index as we must
    // update line and column. If we seek backwards, just set p
    function seek(int $_index) : void
    {
        if ($_index <= $this->_index)
        {
            $this->_index = $_index;// just jump; don't update stream state (line, ...)
            return;
        }
        // seek forward
        $this->_index = min($_index, $this->_size);
    }

    function getText(int $start, int $stop) : string
    {
        if ($stop >= $this->_size)
        {
            $stop = $this->_size - 1;
        }

        if ($start >= $this->_size) return "";

        if ($this->decodeToUnicodeCodePoints)
        {
            $result = "";
            for ($i = $start; $i <= $stop; $i++)
            {
                $result .= Utils::fromCodePoint($this->data[$i]);
            }
            return $result;
        }

        return substr($this->strdata, $start, $stop - $start + 1);
    }

    function __toString() : string
    {
        return $this->strdata;
    }

    function get(int $index) : int { return $this->data[$index]; }

    function getSourceName() : string { return ""; }
}

