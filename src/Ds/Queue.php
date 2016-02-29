<?php
namespace Ds;

use \Error;

/**
 * Queue
 * @package Ds
 */
final class Queue implements \IteratorAggregate, \ArrayAccess, Collection
{
    use Traits\Collection;

    /**
     * @var Deque
     */
    private $internal;

    /**
     * Creates an instance using the values of an array or Traversable object.
     *
     * @param array|\Traversable $values
     */
    public function __construct($values = null)
    {
        $this->internal = new Deque($values);
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
        $this->internal->allocate($capacity);
    }

    /**
     * Returns the current capacity of the queue.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->internal->capacity();
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->internal->clear();
    }

    /**
     * @inheritDoc
     */
    public function copy()
    {
        return new self($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->internal);
    }

    /**
     * Returns the value at the front of the queue without removing it.
     *
     * @return
     */
    public function peek()
    {
        return $this->internal->first();
    }

    /**
     * Returns and removes the value at the front of the Queue.
     *
     * @return mixed
     */
    public function pop()
    {
        return $this->internal->shift();
    }

    /**
     * Pushes zero or more values into the front of the queue.
     *
     * @param mixed ...$values
     */
    public function push(...$values)
    {
        $this->internal->push(...$values);
    }

    /**
     * Adds all values in an array or iterable object to the sequence.
     *
     * @param array|\Traversable $values
     *
     * @throws Error
     */
    public function pushAll($values)
    {
        $this->internal->pushAll($values);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->internal->toArray();
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
     * @throws Error
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->push($value);
        } else {
            throw new Error();
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
