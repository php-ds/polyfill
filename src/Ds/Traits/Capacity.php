<?php
namespace Ds\Traits;

/**
 *
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
     *
     */
    abstract protected function increaseCapacity();

    /**
     *
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
