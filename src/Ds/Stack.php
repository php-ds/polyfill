<?php
namespace Ds;

use UnderflowException;

final class Stack implements Collection, \IteratorAggregate, \ArrayAccess
{
    /**
     *
     */
    private $capacity;

    /**
     *
     */
    private $internal = [];

    /**
     * Creates an instance using the values of an array or Traversable object.
     *
     * @param array|\Traversable $values
     */
    public function __construct($values = null) {
        if ($values === null) {
            $this->capacity = 10;

        } else {
            if (is_integer($values)) {
                $this->capacity = $values;
            } else {
                $this->pushAll($values);
            }
        }
    }

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity) {
        $this->capacity = max($this->capacity, $capacity) ?? 0;
    }

    /**
     * Returns the current capacity of the stack.
     *
     * @return int
     */
    public function capacity(): int {
        return $this->capacity;
    }

    /**
     * @inheritDoc
     */
    public function clear() {
        $this->internal = [];
        $this->capacity = 10;
    }

    /**
     * @inheritDoc
     */
    public function copy() {
        return new Stack($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function count(): int {
        return count($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool {
        return count($this->internal) === 0;
    }

    /**
     *
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Returns the value at the top of the stack without removing it.
     *
     * @return mixed
     *
     * @throws \UnderflowException if the stack is empty.
     */
    public function peek() {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return end($this->internal);
    }

    /**
     * Returns and removes the value at the top of the stack
     *
     * @return mixed
     *
     * @throws \UnderflowException if the stack is empty.
     */
    public function pop() {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return array_pop($this->internal);
    }

    /**
     * Pushes zero or more values onto the top of the stack.
     *
     * @param mixed ...$values
     */
    public function push(...$values) {
        if ($values) {
            if (count($this->internal) + count($values) > $this->capacity) {
                $this->capacity = floor($this->capacity * 1.5);
            }

            array_push($this->internal, ...$values);
        }
    }

    /**
     *
     */
    public function pushAll($values)
    {
        if ( ! is_array($values) && ! $values instanceof \Traversable) {
            throw new \Error();
        }

        $this->push(...$values);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array {
        return array_reverse($this->internal);
    }

    /**
     *
     */
    public function getIterator()
    {
        while ( ! $this->isEmpty()) {
            yield $this->pop();
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
            throw new \Error();
        }
    }

    /**
     *
     */
    public function offsetGet($offset)
    {
        throw new \Error();
    }

    /**
     *
     */
    public function offsetUnset($offset)
    {
        throw new \Error();
    }

    /**
     *
     */
    public function offsetExists($offset)
    {
        throw new \Error();
    }
}
