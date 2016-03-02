<?php
namespace Ds;

/**
 * Vector
 *
 * @package Ds
 */
final class Vector implements \IteratorAggregate, \ArrayAccess, Sequence
{
    use Traits\Sequence;

    const MIN_CAPACITY = 10;

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity)
    {
        $this->capacity = max($this->capacity, $capacity);
    }

    /**
     * Increase capacity
     */
    protected function increaseCapacity()
    {
        $size = count($this);

        if ($size > $this->capacity) {
            $this->capacity = max(intval($this->capacity * 1.5), $size);
        }
    }
}
