<?php
namespace Ds;

use UnderflowException;
use OutOfRangeException;

final class Vector implements Sequence, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var int
     *
     * The minimum capacity of a stack is 10
     */
    private $capacity = 10;

    /**
     * @var array
     */
    private $internal = [];

    /**
     * Creates an instance using the values of an array or Traversable object.
     *
     * @param array|\Traversable $values
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
        $this->internal = [];
        $this->capacity = 10;
    }

    /**
     * @inheritDoc
     */
    public function contains(...$values): bool
    {
        if ( ! $values) {
            return false;
        }

        foreach ($values as $value) {
            if ( ! in_array($value, $this->internal, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function copy(): Vector
    {
        $copy = new Vector();
        $copy->internal = $this->internal;
        $copy->capacity = $this->capacity;

        return $copy;
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
    public function filter(callable $callback = null): Vector
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
        if ($index < 0 || $index >= count($this)) {
            throw new OutOfRangeException();
        }

        return $this->internal[$index];
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, ...$values)
    {
        if ($index < 0 || $index > count($this)) {
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
        return join($glue, $this->internal);
    }

    /**
     *
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
    public function map(callable $callback): Vector
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
        if ( ! $values) {
            return;
        }

        $required = count($this->internal) + count($values);

        if ($required > $this->capacity) {
            $this->capacity = max($required, floor($this->capacity * 1.5));
        }

        array_push($this->internal, ...$values);
    }

    /**
     * @inheritDoc
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
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->internal, $callback, $initial);
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index)
    {
        if ($index < 0 || $index >= count($this)) {
            throw new OutOfRangeException();
        }

        return array_splice($this->internal, $index, 1, null)[0];
    }

    /**
     * @inheritDoc
     */
    public function reverse(): Vector
    {
        return new self(array_reverse($this->internal, false));
    }

    /**
     * @inheritDoc
     */
    public function rotate(int $rotations)
    {
        if ($this->isEmpty()) {
            return;
        }

        $swap = function (&$a, &$b) {
            $t = $a;
            $a = $b;
            $b = $t;
        };

        $reverse = function (int $a, int $b) use ($swap) {
            $b--;
            while ($a < $b) {
                $swap($this->internal[$a++], $this->internal[$b--]);
            }
        };

        $n = count($this);
        $r = $rotations;

        if ($r < 0) {
            $r = $n - (abs($r) % $n);
        } else {
            $r = $r % $n;
        }

        if ($r > 0) {
            $reverse(0,  $r);
            $reverse($r, $n);
            $reverse(0,  $n);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(int $index, $value)
    {
        if ($index < 0 || $index >= count($this)) {
            throw new OutOfRangeException();
        }

        $this->internal[$index] = $value;
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return array_shift($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): Vector
    {
        return new self(array_slice($this->internal, $offset, $length));
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null): Vector
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
        if ( ! $values) {
            return;
        }

        $required = count($this->internal) + count($values);

        if ($required > $this->capacity) {
            $this->capacity = max($required, floor($this->capacity * 1.5));
        }

        array_splice($this->internal, 0, 0, $values);
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
    public function offsetGet($offset)
    {
        return $this->get($offset);
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
        return $offset >= 0 && $offset < count($this) && $this->get($offset) !== null;
    }
}
