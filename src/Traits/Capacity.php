<?php
namespace Ds\Traits;

/**
 * Common to structures that deal with an internal capacity. While none of the
 * PHP implementations actually make use of a capacity, it's important to keep
 * consistent with the extension.
 */
trait Capacity
{
    /**
     * @var int internal capacity
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
     * Called when capacity should be increased to accommodate new values.
     */
    abstract protected function increaseCapacity();

    /**
     * Adjusts the structure's capacity according to its current size.
     */
    private function adjustCapacity()
    {
        $size = count($this);

        // Automatically truncate the allocated buffer when the size of the
        // structure drops low enough.
        if ($size < $this->capacity / 4) {
            $this->capacity = max(self::MIN_CAPACITY, $this->capacity / 2);
        } else {

            // Also check if we should increase capacity when the size changes.
            if ($size >= $this->capacity) {
                $this->increaseCapacity();
            }
        }
    }
}
