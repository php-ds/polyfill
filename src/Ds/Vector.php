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

    const MIN_CAPACITY = 10;

    /**
     *
     */
    public function allocate(int $capacity)
    {
        $this->capacity = max($this->capacity, $capacity);
    }

    /**
     *
     */
    protected function increaseCapacity()
    {
        $size = count($this);

        if ($size > $this->capacity) {
            $this->capacity = max(intval($this->capacity * 1.5), $size);
        }
    }
}
