<?php
namespace Ds;

use OutOfBoundsException;
use IteratorAggregate;
use ArrayAccess

/**
 * A “last in, first out” or “LIFO” collection that only allows access to the
 * value at the top of the structure and iterates in that order, destructively.
 *
 * @package Ds
 */
final class Stack implements IteratorAggregate, ArrayAccess, Collection, Allocated
{
    use Traits\Collection;

    /**
     * @var Sequence internal sequence to store values of the stack.
     */
    private $sequence;

    /**
     * Creates an instance using the values of an array or Traversable object.
     *
     * @param array|\Traversable $values
     */
    public function __construct($values = null)
    {
        $this->sequence = new Sequence($values ?: []);
    }

    /**
     * Clear all elements in the Stack
     */
    public function clear()
    {
        $this->sequence->clear();
    }

    /**
     * @inheritdoc
     */
    public function copy(): Collection
    {
        return new self($this->sequence);
    }

    /**
     * Returns the number of elements in the Stack
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->sequence);
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
        $this->sequence->allocate($capacity);
    }

    /**
     * Returns the current capacity of the stack.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->sequence->capacity();
    }

    /**
     * Returns the value at the top of the stack without removing it.
     *
     * @return mixed
     *
     * @throws \UnderflowException if the stack is empty.
     */
    public function peek()
    {
        return $this->sequence->last();
    }

    /**
     * Returns and removes the value at the top of the stack.
     *
     * @return mixed
     *
     * @throws \UnderflowException if the stack is empty.
     */
    public function pop()
    {
        return $this->sequence->pop();
    }

    /**
     * Pushes zero or more values onto the top of the stack.
     *
     * @param mixed ...$values
     */
    public function push(...$values)
    {
        $this->sequence->push(...$values);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_reverse($this->sequence->toArray());
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
     * @inheritdoc
     *
     * @throws OutOfBoundsException
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->push($value);
        } else {
            throw new OutOfBoundsException();
        }
    }

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function offsetGet($offset)
    {
        throw new Error();
    }

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function offsetUnset($offset)
    {
        throw new Error();
    }

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function offsetExists($offset)
    {
        throw new Error();
    }
}
