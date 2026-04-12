<?php
namespace Ds;

use OutOfBoundsException;
use OutOfRangeException;
use UnderflowException;

/**
 * A Map is a sequential collection of key-value pairs.
 * Keys can be any type, but must be unique.
 */
final class Map implements \IteratorAggregate, \Countable, \JsonSerializable, \ArrayAccess
{
    use Traits\GenericCollection;
    use Traits\SquaredCapacity;

    public const MIN_CAPACITY = 8;

    private array $pairs = [];

    public function __construct(iterable $values = [])
    {
        if (func_num_args()) {
            $this->putAll($values);
        }
    }

    // -- Key equality --------------------------------------------------------

    private function keysAreEqual(mixed $a, mixed $b): bool
    {
        if (is_object($a) && $a instanceof Key) {
            return get_class($a) === get_class($b) && $a->equals($b);
        }
        return $a === $b;
    }

    private function lookupKey(mixed $key): ?Pair
    {
        foreach ($this->pairs as $pair) {
            if ($this->keysAreEqual($pair->key, $key)) {
                return $pair;
            }
        }
        return null;
    }

    private function lookupValue(mixed $value): ?Pair
    {
        foreach ($this->pairs as $pair) {
            if ($pair->value === $value) {
                return $pair;
            }
        }
        return null;
    }

    // -- Basic operations ----------------------------------------------------

    public function put(mixed $key, mixed $value): void
    {
        $pair = $this->lookupKey($key);

        if ($pair) {
            // Readonly pair — need to replace
            foreach ($this->pairs as $i => $p) {
                if ($p === $pair) {
                    $this->pairs[$i] = new Pair($key, $value);
                    return;
                }
            }
        } else {
            $this->checkCapacity();
            $this->pairs[] = new Pair($key, $value);
        }
    }

    public function putAll(iterable $values): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }

    public function get(mixed $key, mixed $default = null): mixed
    {
        if (($pair = $this->lookupKey($key))) {
            return $pair->value;
        }
        if (func_num_args() === 1) {
            throw new OutOfBoundsException();
        }
        return $default;
    }

    public function remove(mixed $key, mixed $default = null): mixed
    {
        foreach ($this->pairs as $position => $pair) {
            if ($this->keysAreEqual($pair->key, $key)) {
                return $this->delete($position);
            }
        }
        if (func_num_args() === 1) {
            throw new OutOfBoundsException();
        }
        return $default;
    }

    private function delete(int $position): mixed
    {
        $pair = $this->pairs[$position];
        $value = $pair->value;
        array_splice($this->pairs, $position, 1, null);
        $this->checkCapacity();
        return $value;
    }

    public function hasKey(mixed $key): bool
    {
        return $this->lookupKey($key) !== null;
    }

    public function hasValue(mixed $value): bool
    {
        return $this->lookupValue($value) !== null;
    }

    // -- Accessors -----------------------------------------------------------

    public function first(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        return $this->pairs[0];
    }

    public function last(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        return $this->pairs[count($this->pairs) - 1];
    }

    public function skip(int $position): Pair
    {
        if ($position < 0 || $position >= count($this->pairs)) {
            throw new OutOfRangeException();
        }
        return clone $this->pairs[$position];
    }

    public function keys(): Set
    {
        return new Set(array_map(fn($pair) => $pair->key, $this->pairs));
    }

    public function values(): Seq
    {
        return new Seq(array_map(fn($pair) => $pair->value, $this->pairs));
    }

    public function pairs(): Seq
    {
        return new Seq(array_map(fn($pair) => clone $pair, $this->pairs));
    }

    // -- Mutators ------------------------------------------------------------

    public function apply(callable $callback): void
    {
        foreach ($this->pairs as $i => $pair) {
            $this->pairs[$i] = new Pair($pair->key, $callback($pair->key, $pair->value));
        }
    }

    public function clear(): void
    {
        $this->pairs = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    public function reverse(): void
    {
        $this->pairs = array_reverse($this->pairs);
    }

    public function sort(?callable $comparator = null): void
    {
        if ($comparator) {
            usort($this->pairs, fn($a, $b) => $comparator($a->value, $b->value));
        } else {
            usort($this->pairs, fn($a, $b) => $a->value <=> $b->value);
        }
    }

    public function ksort(?callable $comparator = null): void
    {
        if ($comparator) {
            usort($this->pairs, fn($a, $b) => $comparator($a->key, $b->key));
        } else {
            usort($this->pairs, fn($a, $b) => $a->key <=> $b->key);
        }
    }

    // -- Derived maps --------------------------------------------------------

    public function filter(?callable $callback = null): self
    {
        $filtered = new self();
        foreach ($this as $key => $value) {
            if ($callback ? $callback($key, $value) : $value) {
                $filtered->put($key, $value);
            }
        }
        return $filtered;
    }

    public function map(callable $callback): self
    {
        $mapped = new self();
        foreach ($this->pairs as $pair) {
            $mapped->put($pair->key, $callback($pair->key, $pair->value));
        }
        return $mapped;
    }

    public function merge(iterable $values): self
    {
        $merged = new self($this);
        $merged->putAll($values);
        return $merged;
    }

    public function reversed(): self
    {
        $reversed = new self();
        $reversed->pairs = array_reverse($this->pairs);
        return $reversed;
    }

    public function sorted(?callable $comparator = null): self
    {
        $copy = $this->copy();
        $copy->sort($comparator);
        return $copy;
    }

    public function ksorted(?callable $comparator = null): self
    {
        $copy = $this->copy();
        $copy->ksort($comparator);
        return $copy;
    }

    public function slice(int $offset, ?int $length = null): self
    {
        $map = new self();
        if (func_num_args() === 1) {
            $slice = array_slice($this->pairs, $offset);
        } else {
            $slice = array_slice($this->pairs, $offset, $length);
        }
        foreach ($slice as $pair) {
            $map->put($pair->key, $pair->value);
        }
        return $map;
    }

    // -- Set operations ------------------------------------------------------

    public function intersect(self $map): self
    {
        return $this->filter(fn($key) => $map->hasKey($key));
    }

    public function diff(self $map): self
    {
        return $this->filter(fn($key) => !$map->hasKey($key));
    }

    public function union(self $map): self
    {
        return $this->merge($map);
    }

    public function xor(self $map): self
    {
        return $this->merge($map)->filter(
            fn($key) => $this->hasKey($key) ^ $map->hasKey($key)
        );
    }

    // -- Reduce / aggregate --------------------------------------------------

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->pairs as $pair) {
            $carry = $callback($carry, $pair->key, $pair->value);
        }
        return $carry;
    }

    public function sum(): int|float
    {
        return $this->values()->sum();
    }

    // -- Collection ----------------------------------------------------------

    public function count(): int
    {
        return count($this->pairs);
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->pairs as $pair) {
            $array[$pair->key] = $pair->value;
        }
        return $array;
    }

    // -- Iteration -----------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        foreach ($this->pairs as $pair) {
            yield $pair->key => $pair->value;
        }
    }

    // -- ArrayAccess ---------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->put($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function &offsetGet(mixed $offset): mixed
    {
        $pair = $this->lookupKey($offset);
        if ($pair) {
            // readonly pair — return copy of value
            $v = $pair->value;
            return $v;
        }
        throw new OutOfBoundsException();
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset, null);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists(mixed $offset): bool
    {
        return $this->get($offset, null) !== null;
    }

    // -- JSON ----------------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return (object) $this->toArray();
    }

    // -- Serialization -------------------------------------------------------

    public function __serialize(): array
    {
        $data = [];
        foreach ($this->pairs as $pair) {
            $data[] = [$pair->key, $pair->value];
        }
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->pairs = [];
        foreach ($data as $entry) {
            $this->put($entry[0], $entry[1]);
        }
    }

    // -- Cloning -------------------------------------------------------------

    public function __clone()
    {
        $pairs = [];
        foreach ($this->pairs as $pair) {
            $pairs[] = clone $pair;
        }
        $this->pairs = $pairs;
    }

    public function __debugInfo(): array
    {
        return $this->pairs()->toArray();
    }
}
