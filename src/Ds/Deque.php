<?php
namespace Ds;

use \OutOfRangeException;
use \UnderflowException;

final class Deque implements Sequence, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var int
     */
    private $capacity = 8;

    /**
     * @var array
     */
    private $internal = [];

    /**
     * @inheritDoc
     */
    public function __construct($values = null)
    {
        if ($values) {
            if (is_integer($values)) {
                $this->allocate($values);
            } else {
                $this->pushAll($values);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function allocate(int $capacity)
    {
        $this->capacity = max($capacity, $this->capacity);
    }

    /**
     * @inheritDoc
     */
    public function capacity(): int
    {
        return $this->capacity;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->capacity = 8;
        $this->internal = [];
    }

    /**
     * @inheritDoc
     */
    public function contains(...$values): bool
    {
        if (!$values) {
            return false;
        }

        foreach ($values as $value) {
            if (!in_array($value, $this->internal, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function copy(): Deque
    {
        return clone $this;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $callback = null): Deque
    {
        if ($callback) {
            return new self(array_filter($this->internal, $callback));
        }

        return new self(array_filter($this->internal));
    }

    /**
     * @inheritDoc
     */
    public function find($value)
    {
        return array_search($value, $this->internal, true);
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->internal[0];
    }

    /**
     * @inheritDoc
     */
    public function get(int $index)
    {
        $this->checkRange($index);

        return $this->internal[$index];
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, ...$values)
    {
        if ($index < 0 || $index > $this->count()) {
            throw new OutOfRangeException();
        }

        array_splice($this->internal, $index, 0, $values);
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return count($this->internal) === 0;
    }

    /**
     * @inheritDoc
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->internal);
    }

    /**
     * Json Serialize
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->internal;
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return end($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function map(callable $callback): Deque
    {
        return new self(array_map($callback, $this->internal));
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return array_pop($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function push(...$values)
    {
        if (!$values) {
            return;
        }

        $required = count($this->internal) + count($values);

        if ($required > $this->capacity) {
            $this->capacity = max($required, floor($this->capacity * 2));
        }

        array_push($this->internal, ...$values);
    }

    /**
     * @inheritDoc
     */
    public function pushAll($values)
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new \Error();
        }

        $this->push(...$values);
    }

    /**
     * @inheritDoc
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->internal, $callback, $initial);
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index)
    {
        $this->checkRange($index);

        $value = $this->internal[$index];
        unset($this->internal[$index]);
        $this->rebase();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverse(): Deque
    {
        return new self(array_reverse($this->internal));

    }

    /**
     * @inheritDoc
     */
    public function rotate(int $rotations)
    {
        while ($rotations !== 0) {
            if ($rotations > 0) {
                $this->push($this->shift());
                $rotations--;
            } else {
                $this->unshift($this->pop());
                $rotations++;
            }
        }
     }

    /**
     * @inheritDoc
     */
    public function set(int $index, $value)
    {
        $this->checkRange($index);

        $this->internal[$index] = $value;
        $this->rebase();
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_shift($this->internal);
        $this->rebase();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): Deque
    {
        return new self(array_slice($this->internal, $offset, $length));
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null): Deque
    {
        $internal = $this->internal;

        if ($comparator) {
            usort($internal, $comparator);
        } else {
            sort($internal);
        }

        return new self($internal);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->internal;
    }

    /**
     * @inheritDoc
     */
    public function unshift(...$values)
    {
        $this->internal = array_merge($values, $this->internal);
    }

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
     * Rebase the array to ensure sequential keys
     */
    private function rebase()
    {
        $this->internal = array_values($this->internal);
    }

    /**
     * Check Range
     *
     * @param int $index
     */
    private function checkRange(int $index)
    {
        if ($index < 0 || $index >= count($this)) {
            throw new OutOfRangeException();
        }
    }
}
