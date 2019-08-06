<?php


namespace Antlr4\Utils;


class DoubleKeyMap
{
    /**
     * Map<Key1, Map<Key2, Value>>
     * @var Map
     */
    private $data;

	function __construct()
    {
	    $this->data = new Map();
    }

	public function set($k1, $k2, $v) : void
	{
		/** @var Map $data2 */
		$data2 = $this->data->get($k1);
		if ($data2 === null)
		{
			$data2 = new Map();
			$this->data->put($k1, $data2);
		}
		$data2->put($k2, $v);
	}

	public function getByTwoKeys($k1, $k2)
	{
		/** @var Map $data2 */
		$data2 = $this->data->get($k1);
		if ($data2 === null) return null;
		return $data2->get($k2);
	}

	public function getByOneKey($k1) : Map { return $this->data->get($k1); }

	public function values($k1) : array
	{
		/** @var Map $data2 */
		$data2 = $this->data->get($k1);
		if ($data2===null) return null;
		return $data2->values();
	}

	public function keysPrimary() : array
	{
		return $this->data->keys();
	}

	public function keysSecondary($k1) : array
	{
		/** @var Map $data2 */
		$data2 = $this->data->get($k1);
		if ($data2===null) return null;
		return $data2->keys();
	}
}
