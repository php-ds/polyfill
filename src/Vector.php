<?php
namespace Ds;

/**
 * Vector
 *
 * @package Ds
 */
final class Vector implements \IteratorAggregate, \ArrayAccess, Sequence
{
    use Traits\Collection;
    use Traits\Sequence;
    use Traits\Capacity;

    const MIN_CAPACITY = 10;

    /**
     * Increase capacity
     */
    protected function increaseCapacityWhenFull()
    {
        $size = count($this);

        if ($size > $this->capacity) {
            $this->capacity = max(intval($this->capacity * 1.5), $size);
        }
    }
}
