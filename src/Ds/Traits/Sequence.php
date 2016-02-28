<?php
namespace Ds\Traits;

use OutOfRangeException;
use UnderflowException;
use Traversable;
use Error;

/**
 * Sequence
 *
 * @package Ds\Traits
 */
trait Sequence
{
    /**
     * @var array
     */
    private $internal = [];

    /**
     * @var int
     */
    private $capacity;

    /**
     * @inheritDoc
     */
    public function __construct($values = null)
    {
        $this->capacity = self::MIN_CAPACITY;

        if ($values) {
            if (is_integer($values)) {
                $this->allocate($values);
            } else {
                $this->pushAll($values);
            }
        }
    }

    /**
     *
     */
    public function toArray(): array
    {
        return $this->internal;
    }

    /**
     *
     */
    public function count(): int
    {
        return count($this->internal);
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
    public function contains(...$values): bool
    {
        if ( ! $values) {
            return false;
        }

        foreach ($values as $value) {
            if ($this->find($value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $callback = null): self
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
        if (empty($this->internal)) {
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
        if ($index < 0 || $index > count($this->internal)) {
            throw new OutOfRangeException();
        }

        array_splice($this->internal, $index, 0, $values);
    }

    /**
     * @inheritDoc
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->internal);
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
    public function map(callable $callback): self
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

        $value = array_pop($this->internal);
        $this->adjustCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function push(...$values)
    {
        if ($values) {
            array_push($this->internal, ...$values);
            $this->adjustCapacity();
        }
    }

    /**
     * @inheritDoc
     */
    public function pushAll($values)
    {
        if ( ! is_array($values) && ! $values instanceof Traversable) {
            throw new Error();
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

        $value = array_splice($this->internal, $index, 1, null)[0];
        $this->adjustCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->internal));

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

        // Reverses a range within the internal array
        $reverse = function (int $a, int $b) use ($swap) {
            while (--$b > $a) {
                $swap($this->internal[$a++], $this->internal[$b--]);
            }
        };

        $n = count($this);
        $r = $rotations;

        // Normalize the number of rotations
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
        $this->checkRange($index);
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

        $value = array_shift($this->internal);
        $this->adjustCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): self
    {
        if (func_num_args() === 1) {
            return new self(array_slice($this->internal, $offset));
        }

        return new self(array_slice($this->internal, $offset, $length));
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null): self
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
    public function unshift(...$values)
    {
        if ($values) {
            array_unshift($this->internal, ...$values);
            $this->adjustCapacity();
        }
    }

    /**
     * Check Range
     *
     * @param int $index
     */
    private function checkRange(int $index)
    {
        if ($index < 0 || $index >= count($this->internal)) {
            throw new OutOfRangeException();
        }
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
    public function clear()
    {
        $this->internal = [];
        $this->capacity = self::MIN_CAPACITY;
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
}
