<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

use Antlr4\Utils\Utils;

class IntervalSet
{
    /**
     * @var Interval[]
     */
    private $intervals = [];

    /**
     * @var bool
     */
    public $readOnly = false;

    function first() : int
    {
        if (!$this->intervals) return Token::INVALID_TYPE;
        return $this->intervals[0]->start;
    }

    function addOne(int $v) : void
    {
        $this->addInterval(new Interval($v, $v));
    }

    function addRange(int $l, int $h) : void
    {
        $this->addInterval(new Interval($l, $h));
    }

    protected function addInterval(Interval $addition) : void
    {
        if ($this->readOnly) throw new \RuntimeException("can't alter readonly IntervalSet");

        //echo "IntervalSet: add $addition to " . implode(", ", $this->intervals) . "\n";

        if ($addition->stop < $addition->start) return;

		// find position in list
		// Use iterators as we modify list in place
        /** @noinspection ForeachInvariantsInspection */
        /** @noinspection CallableInLoopTerminationConditionInspection */
        for ($i = 0; $i < count($this->intervals); $i++)
		{
			/** @var Interval $r */
			$r = $this->intervals[$i];

			if ($addition->equals($r)) return;

			if ($addition->adjacent($r) || !$addition->disjoint($r))
			{
				// next to each other, make a single larger interval
				$bigger = $addition->union($r);
				$this->intervals[$i] = $bigger;
				// make sure we didn't just create an interval that
				// should be merged with next interval in list
                $i++;
				while ($i < count($this->intervals))
				{
					$next = $this->intervals[$i];
					if (!$bigger->adjacent($next) && $bigger->disjoint($next)) break;

					// if we bump up against or overlap next, merge
                    array_splice($this->intervals, $i, 1); // remove this one
                    $i--; // move backwards to what we just set
					$this->intervals[$i] = $bigger->union($next); // set to 3 merged ones
					$i++; // first call to next after previous duplicates the result
				}
				return;
			}

			if ($addition->startsBeforeDisjoint($r))
			{
				// insert before r
				array_splice($this->intervals, $i, 0, [$addition]);
				return;
			}

			// if disjoint and after r, a future iteration will handle it
		}
		// ok, must be after last interval (and disjoint from last interval) just add it
		$this->intervals[] = $addition;
    }

    function addSet(IntervalSet $other)
    {
        if ($other->intervals !== null)
        {
            foreach ($other->intervals as $i)
            {
                $this->addInterval(new Interval($i->start, $i->stop));
            }
        }
        return $this;
    }

    /*function reduce($k)
    {
        // only need to reduce if k is not the last
        if ($k < $this->intervalslength - 1)
        {
            $l = $this->intervals[$k];
            $r = $this->intervals[$k + 1];
            // if r contained in l
            if ($l->stop >= $r->stop)
            {
                array_pop($this->intervals, $k + 1);
                $this->reduce($k);
            }
            else if ($l->stop >= $r->start)
            {
                $this->intervals[$k] = new Interval($l->start, $r->stop);
                array_pop($this->intervals, $k + 1);
            }
        }
    }*/

    function complement(int $start, int $stop) : self
    {
        $result = new self();
        $result->addInterval(new Interval($start, $stop));
        foreach ($this->intervals as $interval) {
            $result->removeRange($interval);
        }
        return $result;
    }

    function contains(int $item) : bool
    {
		$l = 0;
		$r = count($this->intervals) - 1;
		// Binary search for the element in the (sorted, disjoint) array of intervals.
		while ($l <= $r) {
            $m = intval($l + $r, 2);
			$interval = $this->intervals[$m];
			$start = $interval->start;
			$stop = $interval->stop;
			if ($stop < $item) {
                $l = $m + 1;
            } else if ($start > $item) {
                $r = $m - 1;
            } else { // item >= start && item <= stop
                return true;
            }
		}
		return false;
    }

    function size() : int
    {
        $len = 0;
        foreach ($this->intervals as $i) $len += $i->getLength();
        return $len;
    }

    function removeRange(Interval $v) : void
    {
        if ($v->start === $v->stop - 1) { $this->removeOne($v->start); return; }

        $k = 0;
        /** @noinspection CallableInLoopTerminationConditionInspection */
        for ($n = 0; $n < count($this->intervals); $n++)
        {
            $i = $this->intervals[$k];

            if ($v->stop <= $i->start) return;

            // check for including range, split it
            if ($v->start > $i->start && $v->stop < $i->stop)
            {
                $this->intervals[$k] = new Interval($i->start, $v->start);
                $x = new Interval($v->stop, $i->stop);
                array_splice($this->intervals, $k, 0, [$x]);
                return;
            }

            // check for included range, remove it
            if ($v->start <= $i->start && $v->stop>=$i->stop)
            {
                array_splice($this->intervals, $k, 1);
                $k--;// need another pass
            }
            // check for lower boundary
            else if ($v->start < $i->stop)
            {
                $this->intervals[$k] = new Interval($i->start, $v->start);
            }
            // check for upper boundary
            else if ($v->stop < $i->stop)
            {
                $this->intervals[$k] = new Interval($v->stop, $i->stop);
            }

            $k++;
        }
    }

    function removeOne(int $v) : void
    {
        foreach ($this->intervals as $k => $i)
        {
            // intervals is ordered
            if ($v < $i->start) return;

            // check for single value range
            if ($v === $i->start && $v === $i->stop - 1)
            {
                array_splice($this->intervals, $k, 1);
                return;
            }

            // check for lower boundary
            if ($v === $i->start)
            {
                $this->intervals[$k] = new Interval($i->start + 1, $i->stop);
                return;
            }

            // check for upper boundary
            if ($v === $i->stop - 1)
            {
                $this->intervals[$k] = new Interval($i->start, $i->stop - 1);
                return;
            }

            // split existing range
            if ($v < $i->stop - 1)
            {
                $x = new Interval($i->start, $v);
                $i->start = $v + 1;
                array_splice($this->intervals, $k, 0, [$x]);
                return;
            }
        }
    }

    function __toString()
    {
        return $this->toStringChars(false);
    }

    function toStringChars(bool $elemAreChar) : string
    {
		if (!$this->intervals) return "{}";

        $buf = "";

		if ($this->size()>1)
		{
			$buf .= "{";
		}
		$iter = new \ArrayIterator($this->intervals);
		while ($iter->valid())
		{
		    /** @var Interval $I */
			$I = $iter->current(); $iter->next();
			$a = $I->start;
			$b = $I->stop;
			if ($a === $b)
			{
				if ($a === Token::EOF) $buf .= "<EOF>";
				else if ($elemAreChar) $buf .= "'" . Utils::fromCodePoint($a) . "'";
				else $buf .= $a;
			}
			else
			{
				if ($elemAreChar) $buf .= "'" . Utils::fromCodePoint($a) . "'..'" . Utils::fromCodePoint($b) . "'";
				else              $buf .= $a .  ".." . $b;
			}

			if ($iter->valid())
			{
				$buf .= ", ";
			}
		}
		if ($this->size() > 1)
		{
			$buf .= "}";
		}
		return $buf;
    }

    function toStringVocabulary(Vocabulary $vocabulary) : string
    {
		if (!$this->intervals) return "{}";

		$buf = "";
		if ($this->size() > 1)
		{
			$buf .= "{";
		}
		$iter = new \ArrayIterator($this->intervals);
		while ($iter->valid())
		{
			/** @var Interval $I */
			$I = $iter->current(); $iter->next();
			$a = $I->start;
			$b = $I->stop;
			if ($a===$b)
			{
				$buf .= $this->elementName($vocabulary, $a);
			}
			else
			{
				for ($i=$a; $i<=$b; $i++) {
					if ($i > $a) $buf .= ", ";
                    $buf .= $this->elementName($vocabulary, $i);
				}
			}
			if ($iter->valid()) $buf .= ", ";
		}
		if ($this->size() > 1)
		{
			$buf .= "}";
		}

        return $buf;
    }

    function elementName(Vocabulary $vocabulary, int $a) : string
    {
        if ($a === Token::EOF) return "<EOF>";
        if ($a === Token::EPSILON) return "<EPSILON>";
        return $vocabulary->getDisplayName($a);
    }

    static function fromInt(int $a) : self
    {
		$s = new self();
        $s->addOne($a);
        return $s;
    }

    static function fromRange(int $a, int $b) : self
    {
		$s = new self();
        $s->addRange($a, $b);
        return $s;
    }

    public function toArray() : array
    {
        $values = [];
		foreach ($this->intervals as $interval) {
            $start = $interval->start;
            $stop = $interval->stop;
            for ($value = $start; $value <= $stop; $value++) {
                $values[] = $value;
            }
		}

		return $values;
    }
}
