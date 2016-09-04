<?php
namespace Ds;

use OutOfBoundsException;
use IteratorAggregate;
use ArrayAccess;

/**
 * A “first in, first out” or “FIFO” collection that only allows access to the
 * value at the front of the queue and iterates in that order, destructively.
 *
 * @package Ds
 */
final class Queue implements IteratorAggregate, ArrayAccess, Collection, Allocated
{
    use Traits\Collection;

    const MIN_CAPACITY = 8;

    /**
     * @var Sequence internal sequence to store values.
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
     * Returns the current capacity of the queue.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->sequence->capacity();
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->sequence->clear();
    }

    /**
     * @inheritDoc
     */
    public function copy(): \Ds\Collection
    {
        return new self($this->sequence);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->sequence);
    }

    /**
     * Returns the value at the front of the queue without removing it.
     *
     * @return
     */
    public function peek()
    {
        return $this->sequence->first();
    }

    /**
     * Returns and removes the value at the front of the Queue.
     *
     * @return mixed
     */
    public function pop()
    {
        return $this->sequence->shift();
    }

    /**
     * Pushes zero or more values into the front of the queue.
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
        return $this->sequence->toArray();
    }

    /**
     * Get iterator
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
