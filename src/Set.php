<?php
namespace Ds;

use Error;
use OutOfRangeException;

/**
 * A Set is a sequence of unique values.
 */
final class Set implements \IteratorAggregate, \Countable, \JsonSerializable, \ArrayAccess
{
    use Traits\GenericCollection;

    public const MIN_CAPACITY = Map::MIN_CAPACITY;

    private Map $table;

    public function __construct(iterable $values = [])
    {
        $this->table = new Map();

        foreach ($values as $value) {
            $this->add($value);
        }
    }

    public function add(mixed ...$values): void
    {
        foreach ($values as $value) {
            $this->table->put($value, null);
        }
    }

    public function allocate(int $capacity): void
    {
        $this->table->allocate($capacity);
    }

    public function capacity(): int
    {
        return $this->table->capacity();
    }

    public function clear(): void
    {
        $this->table->clear();
    }

    public function contains(mixed ...$values): bool
    {
        foreach ($values as $value) {
            if (!$this->table->hasKey($value)) {
                return false;
            }
        }
        return true;
    }

    public function copy(): self
    {
        return new self($this);
    }

    public function count(): int
    {
        return count($this->table);
    }

    public function diff(self $set): self
    {
        return $this->table->diff($set->table)->keys();
    }

    public function xor(self $set): self
    {
        return $this->table->xor($set->table)->keys();
    }

    public function filter(?callable $callback = null): self
    {
        return new self(array_filter($this->toArray(), $callback ?: 'boolval'));
    }

    public function first(): mixed
    {
        return $this->table->first()->key;
    }

    public function get(int $position): mixed
    {
        return $this->table->skip($position)->key;
    }

    public function intersect(self $set): self
    {
        return $this->table->intersect($set->table)->keys();
    }

    public function isEmpty(): bool
    {
        return $this->table->isEmpty();
    }

    public function join(?string $glue = null): string
    {
        return implode($glue ?? '', $this->toArray());
    }

    public function last(): mixed
    {
        return $this->table->last()->key;
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->toArray()));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this as $value) {
            $carry = $callback($carry, $value);
        }
        return $carry;
    }

    public function remove(mixed ...$values): void
    {
        foreach ($values as $value) {
            $this->table->remove($value, null);
        }
    }

    public function reverse(): void
    {
        $this->table->reverse();
    }

    public function reversed(): self
    {
        $reversed = $this->copy();
        $reversed->table->reverse();
        return $reversed;
    }

    public function slice(int $offset, ?int $length = null): self
    {
        $sliced = new self();
        $sliced->table = $this->table->slice($offset, $length);
        return $sliced;
    }

    public function sort(?callable $comparator = null): void
    {
        $this->table->ksort($comparator);
    }

    public function sorted(?callable $comparator = null): self
    {
        $sorted = $this->copy();
        $sorted->table->ksort($comparator);
        return $sorted;
    }

    public function merge(iterable $values): self
    {
        $merged = $this->copy();
        foreach ($values as $value) {
            $merged->add($value);
        }
        return $merged;
    }

    public function union(self $set): self
    {
        $union = new self();
        foreach ($this as $value) {
            $union->add($value);
        }
        foreach ($set as $value) {
            $union->add($value);
        }
        return $union;
    }

    public function sum(): int|float
    {
        return @array_sum($this->toArray());
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    // -- Iteration -----------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        foreach ($this->table as $key => $value) {
            yield $key;
        }
    }

    // -- ArrayAccess ---------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
            return;
        }
        throw new Error("Array access by key is not supported");
    }

    #[\ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->table->skip($offset)->key;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists(mixed $offset): bool
    {
        throw new Error("Array access by key is not supported");
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset(mixed $offset): void
    {
        throw new Error("Array access by key is not supported");
    }

    // -- Serialization -------------------------------------------------------

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        $this->table = new Map();
        foreach ($data as $value) {
            $this->add($value);
        }
    }

    public function __clone()
    {
        $this->table = clone $this->table;
    }
}
