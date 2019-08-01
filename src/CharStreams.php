<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
* Use of this file is governed by the BSD 3-clause license that
* can be found in the LICENSE.txt file in the project root.
*/

namespace Antlr4;

// Utility functions to create InputStreams from various sources.
// All returned InputStreams support the full range of Unicode
// up to U+10FFFF (the default behavior of InputStream only supports
// code points up to U+FFFF).
class CharStreams
{
    static function fromString($str) : InputStream
    {
        return new InputStream($str, true);
    }
}

