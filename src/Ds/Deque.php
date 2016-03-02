<?php
namespace Ds;

/**
 * Deque
 *
 * @package Ds
 */
final class Deque implements \IteratorAggregate, \ArrayAccess, Sequence
{
    use Traits\Sequence;

    const MIN_CAPACITY = 8;

    /**
     * @inheritdoc
     */
    protected function increaseCapacity()
    {
        $this->capacity = $this->square(max(count($this), $this->capacity) + 1);
    }
}
