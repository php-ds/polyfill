<?php

namespace Ds;

use Ds\Traits\CollectionTrait;
use Ds\Traits\SequenceTrait;

/**
 * Deque
 *
 * @package Ds
 */
final class Deque implements \IteratorAggregate, \ArrayAccess, Sequence
{
    use SequenceTrait;
    use CollectionTrait;

    /**
     * @var int
     */
    private $defaultCapacity = 8;

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
     * To String
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
     * Offset Get
     */
    public function &offsetGet($offset)
    {
        $this->checkRange($offset);
        return $this->internal[$offset];
    }

    /**
     * Offset Unset
     */
    public function offsetUnset($offset)
    {
        // Unset should be quiet, so we shouldn't allow 'remove' to throw.
        if (is_integer($offset) && $offset >= 0 && $offset < count($this)) {
            $this->remove($offset);
        }
    }

    /**
     * Offset Exists
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
            if ($required >= $this->capacity) {
                $this->capacity = max($required, floor($this->capacity * 2));
            }
        } elseif (count($this->internal) < $this->capacity / 2) {
            $this->capacity /= 2;
        }
    }
}
