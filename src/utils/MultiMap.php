<?php

namespace Antlr4\Utils;

class MultiMap extends Map
{
    function put($key, $value) : void
    {
		$elementsForKey = $this->get($key);
		if ($elementsForKey === null)
		{
			$elementsForKey = new \ArrayObject();
			parent::put($key, $elementsForKey);
		}
		$elementsForKey->append($value);
	}

    /**
     * @return Pair[]
     */
	function getPairs() : array
    {
		$pairs = [];
		foreach ($this->keys() as $key => $values)
		{
			foreach ($values as $value)
			{
				$pairs[] = new Pair($key, $value);
			}
		}
		return $pairs;
	}
}