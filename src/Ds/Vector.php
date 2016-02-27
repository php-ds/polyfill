<?php

namespace Ds;

use Ds\Traits\CollectionTrait;
use Ds\Traits\SequenceTrait;

/**
 * Vector
 *
 * @package Ds
 */
final class Vector implements \IteratorAggregate, \ArrayAccess, Sequence
{
    use CollectionTrait;
    use SequenceTrait;

    /**
     * @var int
     *
     * The minimum capacity of a stack is 10
     */
    private $defaultCapacity = 10;

    /**
     *
     */
    public function getIterator()
    {
        foreach ($this->internal as $value) {
            yield $value;
        }
    }

    /**
     *
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     *
     */
    public function __toString()
    {
        return 'object(' . get_class($this) . ')';
    }

    /**
     *
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->push($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     *
     */
    public function &offsetGet($offset)
    {
        $this->checkRange($offset);
        return $this->internal[$offset];
    }

    /**
     *
     */
    public function offsetUnset($offset)
    {
        // Unset should be quiet, so we shouldn't allow 'remove' to throw.
        if (is_integer($offset) && $offset >= 0 && $offset < count($this)) {
            $this->remove($offset);
        }
    }

    /**
     *
     */
    public function offsetExists($offset)
    {
        if ($offset < 0 || $offset >= count($this)) {
            return false;
        }

        return $this->get($offset) !== null;
    }

    /**
     * Default Capacity
     *
     * @return int
     */
    private function defaultCapacity(): int
    {
        return $this->defaultCapacity;
    }

    /**
     * Check Capacity
     *
     * @param int|null $required
     */
    private function checkCapacity(int $required = null)
    {
        if ($required !== null) {
            if ($required > $this->capacity) {
                $this->capacity = max($required, floor($this->capacity * 1.5));
            }
        } elseif (count($this->internal) < $this->capacity / 4) {
            $this->capacity /= 2;
        }
    }
}
