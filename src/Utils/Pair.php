<?php

namespace Antlr4\Utils;

class Pair
{
    public $a;
    public $b;

    function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

	function equals($obj) : bool
	{
		if ($obj === $this) return true;
		if (!($obj instanceof self)) return false;

		/** @var Pair $obj */
		return Utils::standardEqualsFunction($this->a, $obj->a)
			&& Utils::standardEqualsFunction($this->b, $obj->b);
	}

	function __toString()
    {
		return ((string)$this->a) . ", " . ((string)$this->b);
	}
}