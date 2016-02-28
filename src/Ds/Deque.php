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
    use Traits\SquaredCapacity;

    const MIN_CAPACITY = 8;

    /**
     *
     */
    protected function increaseCapacity()
    {
        $this->capacity = $this->square(max(count($this), $this->capacity) + 1);
    }
}
