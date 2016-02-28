<?php

namespace Ds;

/**
 * Deque
 *
 * @package Ds
 */
final class Deque implements \IteratorAggregate, \ArrayAccess, Sequence
{
    use Traits\Collection;
    use Traits\Sequence;
    use Traits\SquareCapacity;

    const MIN_CAPACITY = 8;

    /**
     *
     */
    public function allocate(int $capacity)
    {
        $this->capacity = max($this->square($capacity), $this->capacity);
    }

    /**
     *
     */
    protected function increaseCapacity()
    {
        $this->capacity = max(
            $this->square($this->capacity),
            $this->square(count($this) + 1)
        );
    }
}
