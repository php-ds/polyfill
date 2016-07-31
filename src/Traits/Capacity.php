<?php
namespace Ds\Traits;

/**
 * Capacity
 *
 * @package Ds\Traits
 */
trait Capacity
{
    /**
     * @var int
     */
    private $capacity = self::MIN_CAPACITY;

    /**
     * Returns the current capacity.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->capacity;
    }

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
        $this->capacity = max($capacity, $this->capacity);
    }

    /**
     * Increase Capacity
     */
    abstract protected function increaseCapacity();

    /**
     * Adjust Capacity
     */
    private function adjustCapacity()
    {
        $size = count($this);

        if ($size >= $this->capacity) {
            $this->increaseCapacity();

        } else {
            if ($size < $this->capacity / 4) {
                $this->capacity = max(self::MIN_CAPACITY, $this->capacity / 2);
            }
        }
    }
}
