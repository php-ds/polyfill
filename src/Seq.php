<?php
namespace Ds;

use OutOfRangeException;
use UnderflowException;

/**
 * A Seq is an indexed sequence of values, with O(1) push/pop at both ends.
 * Replaces Vector and Deque from v1.
 */
final class Seq implements \IteratorAggregate, \Countable, \JsonSerializable, \ArrayAccess
{
    use Traits\GenericCollection;
    use Traits\SquaredCapacity;

    public const MIN_CAPACITY = 8;

    private array $array = [];

    public function __construct(iterable $values = [])
    {
        foreach ($values as $value) {
            $this->array[] = $value;
        }

        $this->capacity = max(count($this->array), self::MIN_CAPACITY);
    }

    // -- Accessors -----------------------------------------------------------

    public function get(int $index): mixed
    {
        if (!$this->validIndex($index)) {
            throw new OutOfRangeException();
        }
        return $this->array[$index];
    }

    public function first(): mixed
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        return $this->array[0];
    }

    public function last(): mixed
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        return $this->array[count($this->array) - 1];
    }

    public function find(mixed $value): int|false
    {
        return array_search($value, $this->array, true);
    }

    public function contains(mixed ...$values): bool
    {
        foreach ($values as $value) {
            if ($this->find($value) === false) {
                return false;
            }
        }
        return true;
    }

    // -- Mutators ------------------------------------------------------------

    public function set(int $index, mixed $value): void
    {
        if (!$this->validIndex($index)) {
            throw new OutOfRangeException();
        }
        $this->array[$index] = $value;
    }

    public function push(mixed ...$values): void
    {
        $this->ensureCapacity($this->count() + count($values));
        foreach ($values as $value) {
            $this->array[] = $value;
        }
    }

    public function pop(): mixed
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        $value = array_pop($this->array);
        $this->checkCapacity();
        return $value;
    }

    public function unshift(mixed ...$values): void
    {
        if ($values) {
            $this->array = array_merge($values, $this->array);
            $this->checkCapacity();
        }
    }

    public function shift(): mixed
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        $value = array_shift($this->array);
        $this->checkCapacity();
        return $value;
    }

    public function insert(int $index, mixed ...$values): void
    {
        if (!$this->validIndex($index) && $index !== count($this)) {
            throw new OutOfRangeException();
        }
        array_splice($this->array, $index, 0, $values);
        $this->checkCapacity();
    }

    public function remove(int $index): mixed
    {
        if (!$this->validIndex($index)) {
            throw new OutOfRangeException();
        }
        $value = array_splice($this->array, $index, 1, null)[0];
        $this->checkCapacity();
        return $value;
    }

    public function apply(callable $callback): void
    {
        foreach ($this->array as &$value) {
            $value = $callback($value);
        }
    }

    public function clear(): void
    {
        $this->array = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    public function reverse(): void
    {
        $this->array = array_reverse($this->array);
    }

    public function rotate(int $rotations): void
    {
        $n = count($this);
        if ($n < 2) return;
        $r = $rotations < 0 ? $n - (abs($rotations) % $n) : $rotations % $n;
        for (; $r > 0; $r--) {
            array_push($this->array, array_shift($this->array));
        }
    }

    public function sort(?callable $comparator = null): void
    {
        if ($comparator) {
            usort($this->array, $comparator);
        } else {
            sort($this->array);
        }
    }

    // -- Derived sequences ---------------------------------------------------

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->array));
    }

    public function filter(?callable $callback = null): self
    {
        return new self(array_filter($this->array, $callback ?: 'boolval'));
    }

    public function merge(iterable $values): self
    {
        $copy = clone $this;
        $copy->push(...$values);
        return $copy;
    }

    public function reversed(): self
    {
        return new self(array_reverse($this->array));
    }

    public function sorted(?callable $comparator = null): self
    {
        $copy = clone $this;
        $copy->sort($comparator);
        return $copy;
    }

    public function slice(int $offset, ?int $length = null): self
    {
        if ($length === null) {
            $length = count($this);
        }
        return new self(array_slice($this->array, $offset, $length));
    }

    // -- Reduce / aggregate --------------------------------------------------

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->array, $callback, $initial);
    }

    public function sum(): int|float
    {
        return @array_sum($this->array);
    }

    public function join(?string $glue = null): string
    {
        return implode($glue ?? '', $this->array);
    }

    // -- Collection ----------------------------------------------------------

    public function count(): int
    {
        return count($this->array);
    }

    public function toArray(): array
    {
        return $this->array;
    }

    // -- Iteration -----------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        foreach ($this->array as $value) {
            yield $value;
        }
    }

    // -- ArrayAccess ---------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->push($value);
        } else {
            $this->set($offset, $value);
        }
    }

    #[\ReturnTypeWillChange]
    public function &offsetGet(mixed $offset): mixed
    {
        if (!$this->validIndex($offset)) {
            throw new OutOfRangeException();
        }
        return $this->array[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset(mixed $offset): void
    {
        if (is_integer($offset) && $this->validIndex($offset)) {
            $this->remove($offset);
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetExists(mixed $offset): bool
    {
        return is_integer($offset)
            && $this->validIndex($offset)
            && $this->get($offset) !== null;
    }

    // -- Serialization -------------------------------------------------------

    public function __serialize(): array
    {
        return $this->array;
    }

    public function __unserialize(array $data): void
    {
        $this->array = array_values($data);
        $this->capacity = max(count($this->array), self::MIN_CAPACITY);
    }

    // -- Internal ------------------------------------------------------------

    private function validIndex(int $index): bool
    {
        return $index >= 0 && $index < count($this->array);
    }
}
