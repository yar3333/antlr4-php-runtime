<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

//  This is an InputStream that is loaded from a file all at once
//  when you construct the object.
class FileStream extends InputStream
{
    public $fileName;

    function __construct($fileName, $decodeToUnicodeCodePoints)
    {
        $data = file_get_contents($fileName/*, "utf8"*/);

        parent::__construct($data, $decodeToUnicodeCodePoints);

        $this->fileName = $fileName;
    }
}
