<?php

namespace Antlr4;

class Interval
{
	const INTERVAL_POOL_MAX_VALUE = 1000;

	private static $_INVALID;
	static function INVALID() : self { return self::$_INVALID ?? (self::$_INVALID = new Interval(-1, -2)); }

    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $stop;

    /* stop is included! */
    function __construct(int $start, int $stop)
    {
        $this->start = $start;
        $this->stop = $stop;
    }

    function contains(int $item) : bool
    {
        return $item >= $this->start && $item <= $this->stop;
    }

    function __toString() : string
    {
        if ($this->start === $this->stop) {
            return (string)$this->start;
        }
        return $this->start . ".." . $this->stop;
    }

    function getLength() : int
    {
        return $this->stop - $this->start + 1;
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof self)) return false;
        return $this->start === $other->start
            && $this->stop === $other->stop;
    }

    /** Does this start completely before other? Disjoint
	 * @param Interval $other
	 * @return bool
	 */
	public function startsBeforeDisjoint(Interval $other) : bool
	{
		return $this->start < $other->start && $this->stop < $other->start;
	}
	
	/** Does this start at or before other? Nondisjoint
	 * @param Interval $other
	 * @return bool
	 */
	public function startsBeforeNonDisjoint(Interval $other) : bool
	{
		return $this->start <= $other->start && $this->stop >= $other->start;
	}

	/** Does this.a start after other.b? May or may not be disjoint
	 * @param Interval $other
	 * @return bool
	 */
	public function startsAfter(Interval $other) : bool { return $this->start > $other->start; }

	/**
	 * @param Interval $other
	 * @return bool
	 */
	public function startsAfterDisjoint(Interval $other) : bool
    {
		return $this->start > $other->stop;
	}

	/** Does this start after other? NonDisjoint
	 * @param Interval $other
	 * @return bool
	 */
	public function startsAfterNonDisjoint(Interval $other) : bool
	{
		return $this->start > $other->start && $this->start <= $other->stop; // this.b>=other.b implied
	}

	function disjoint(Interval $other) : bool
    {
		return $this->startsBeforeDisjoint($other) || $this->startsAfterDisjoint($other);
	}

	function adjacent(Interval $other) : bool
    {
		return $this->start === $other->stop + 1 || $this->stop === $other->start - 1;
	}

	function union(Interval $other) : Interval
    {
		return new Interval(min($this->start, $other->start), max($this->stop, $other->stop));
	}

	function intersection(Interval $other) : Interval
    {
		return new Interval(max($this->start, $other->start), min($this->stop, $other->stop));
	}
}
