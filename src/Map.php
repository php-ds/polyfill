<?php
namespace Ds;

use OutOfBoundsException;
use OutOfRangeException;
use Traversable;
use UnderflowException;

/**
 * Class Map
 *
 * @package Ds
 */
final class Map implements \IteratorAggregate, \ArrayAccess, Collection
{
    use Traits\Collection;
    use Traits\SquaredCapacity;

    const MIN_CAPACITY = 8;

    /**
     * @var Pair[]
     */
    private $pairs;

    /**
     * Creates an instance using the values of an array or Traversable object.
     *
     * Should an integer be provided the Map will allocate the memory capacity
     * to the size of $values.
     *
     * @param array|\Traversable|int|null $values
     */
    public function __construct($values = null)
    {
        $this->reset();

        if (is_array($values) || $values instanceof Traversable) {
            $this->putAll($values);

        } else if (is_integer($values)) {
            $this->allocate($values);
        }
    }

    private function reset()
    {
        $this->pairs = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->reset();
    }

    /**
     * Removes all Pairs from the Map
     *
     * @param mixed[] $keys
     */
    public function removeAll($keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    /**
     * Return the first Pair from the Map
     *
     * @return Pair
     *
     * @throws UnderflowException
     */
    public function first(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->pairs[0];
    }

    /**
     * Return the last Pair from the Map
     *
     * @return Pair
     *
     * @throws UnderflowException
     */
    public function last(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return end($this->pairs);
    }

    /**
     * Return the pair at a specified position in the Map
     *
     * @param int $position
     *
     * @return Pair
     *
     * @throws OutOfRangeException
     */
    public function skip(int $position): Pair
    {
        if ($position < 0 || $position >= count($this->pairs)) {
            throw new OutOfRangeException();
        }

        return $this->pairs[$position]->copy();
    }

    /**
     * Merge an array of values with the current Map
     *
     * @param array|\Traversable $values
     *
     * @return Map
     */
    public function merge($values): Map
    {
        $merged = $this->copy();
        $merged->putAll($values);

        return $merged;
    }

    /**
     * Intersect
     *
     * @param Map $map
     *
     * @return Map
     */
    public function intersect(Map $map): Map
    {
        return $this->filter(function($key) use ($map) {
            return $map->hasKey($key);
        });
    }

    /**
     * Diff
     *
     * @param Map $map
     *
     * @return Map
     */
    public function diff(Map $map): Map
    {
        return $this->filter(function($key) use ($map) {
            return ! $map->hasKey($key);
        });
    }

    /**
     * XOR
     *
     * @param Map $map
     *
     * @return Map
     */
    public function xor(Map $map): Map
    {
        return $this->merge($map)->filter(function($key) use ($map) {
            return $this->hasKey($key) ^ $map->hasKey($key);
        });
    }

    /**
     * Identical
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return bool
     */
    private function keysAreEqual($a, $b): bool
    {
        if (is_object($a) && $a instanceof Hashable) {
            return $a->equals($b);
        }

        return $a === $b;
    }

    /**
     * @param $key
     *
     * @return Pair|null
     */
    private function lookupKey($key)
    {
        foreach ($this->pairs as $pair) {
            if ($this->keysAreEqual($pair->key, $key)) {
                return $pair;
            }
        }
    }

    /**
     * @param $value
     *
     * @return Pair|null
     */
    private function lookupValue($value)
    {
        foreach ($this->pairs as $pair) {
            if ($pair->value === $value) {
                return $pair;
            }
        }
    }

    /**
     *
     */
    private function contains(string $lookup, array $values): bool
    {
        if (empty($values)) {
            return false;
        }

        foreach ($values as $value) {
            if ( ! $this->$lookup($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether an association for all of zero or more keys exist.
     *
     * @param mixed ...$keys
     *
     * @return bool true if at least one value was provided and the map
     *              contains all given keys, false otherwise.
     */
    public function hasKey(...$keys): bool
    {
        return $this->contains('lookupKey', $keys);
    }

    /**
     * Returns whether an association for all of zero or more values exist.
     *
     * @param mixed ...$values
     *
     * @return bool true if at least one value was provided and the map
     *              contains all given values, false otherwise.
     */
    public function hasValue(...$values): bool
    {
        return $this->contains('lookupValue', $values);
    }

    /**
     * @inheritDoc
     */
    public function copy()
    {
        return new self($this);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->pairs);
    }

    /**
     * Returns a new map containing only the values for which a predicate
     * returns true. A boolean test will be used if a predicate is not provided.
     *
     * @param callable|null $predicate Accepts a key and a value, and returns:
     *                                 true : include the value,
     *                                 false: skip the value.
     *
     * @return Map
     */
    public function filter(callable $predicate = null): Map
    {
        $filtered = new self();

        foreach ($this as $key => $value) {
            if ($predicate ? $predicate($key, $value) : $value) {
                $filtered->put($key, $value);
            }
        }

        return $filtered;
    }

    /**
     * Returns the value associated with a key, or an optional default if the
     * key is not associated with a value.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed The associated value or fallback default if provided.
     *
     * @throws OutOfBoundsException if no default was provided and the key is
     *                               not associated with a value.
     */
    public function get($key, $default = null)
    {
        if (($pair = $this->lookupKey($key))) {
            return $pair->value;
        }

        if (func_num_args() === 1) {
            throw new OutOfBoundsException();
        }

        return $default;
    }

    /**
     * Returns a set of all the keys in the map.
     *
     * @return Set
     */
    public function keys(): Set
    {
        $set = new Set();

        foreach ($this->pairs as $pair) {
            $set->add($pair->key);
        }

        return $set;
    }

    /**
     * Returns a new map using the results of applying a callback to each value.
     * The keys will be keysAreEqual in both maps.
     *
     * @param callable $callback Accepts two arguments: key and value, should
     *                           return what the updated value will be.
     *
     * @return Map
     */
    public function map(callable $callback): Map
    {
        $mapped = new self();

        foreach ($this->pairs as $pair) {
            $mapped[$pair->key] = $callback($pair->key, $pair->value);
        }

        return $mapped;
    }

    /**
     * Returns a sequence of pairs representing all associations.
     *
     * @return Sequence
     */
    public function pairs(): Sequence
    {
        $sequence = new Vector();

        foreach ($this->pairs as $pair) {
            $sequence[] = $pair->copy();
        }

        return $sequence;
    }

    /**
     * Associates a key with a value, replacing a previous association if there
     * was one.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function put($key, $value)
    {
        $pair = $this->lookupKey($key);

        if ($pair) {
            $pair->value = $value;

        } else {
            $this->adjustCapacity();
            $this->pairs[] = new Pair($key, $value);
        }
    }

    /**
     * Creates associations for all keys and corresponding values of either an
     * array or iterable object.
     *
     * @param array|\Traversable $values
     */
    public function putAll($values)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }

    /**
     * Iteratively reduces the map to a single value using a callback.
     *
     * @param callable $callback Accepts the carry, key, and value, and
     *                           returns an updated carry value.
     *
     * @param mixed|null $initial Optional initial carry value.
     *
     * @return mixed The carry value of the final iteration, or the initial
     *               value if the map was empty.
     */
    public function reduce(callable $callback, $initial = null)
    {
        $carry = $initial;

        foreach ($this->pairs as $pair) {
            $carry = $callback($carry, $pair->key, $pair->value);
        }

        return $carry;
    }

    private function delete(int $position)
    {
        $pair  = $this->pairs[$position];
        $value = $pair->value;

        array_splice($this->pairs, $position, 1, null);

        $this->adjustCapacity();
        return $value;
    }

    /**
     * Removes a key's association from the map and returns the associated value
     * or a provided default if provided.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed The associated value or fallback default if provided.
     *
     * @throws \OutOfBoundsException if no default was provided and the key is
     *                               not associated with a value.
     */
    public function remove($key, $default = null)
    {
        foreach ($this->pairs as $position => $pair) {
            if ($this->keysAreEqual($pair->key, $key)) {
                return $this->delete($position);
            }
        }

        // Check if a default was provided
        if (func_num_args() === 1) {
            throw new \OutOfBoundsException();
        }

        return $default;
    }

    /**
     * Returns a reversed copy of the map.
     */
    public function reverse(): Map
    {
        $reversed = new self();
        $reversed->pairs = array_reverse($this->pairs);

        return $reversed;
    }

    /**
     * Returns a sub-sequence of a given length starting at a specified offset.
     *
     * @param int $offset      If the offset is non-negative, the map will
     *                         start at that offset in the map. If offset is
     *                         negative, the map will start that far from the
     *                         end.
     *
     * @param int|null $length If a length is given and is positive, the
     *                         resulting set will have up to that many pairs in
     *                         it. If the requested length results in an
     *                         overflow, only pairs up to the end of the map
     *                         will be included.
     *
     *                         If a length is given and is negative, the map
     *                         will stop that many pairs from the end.
     *
     *                        If a length is not provided, the resulting map
     *                        will contains all pairs between the offset and
     *                        the end of the map.
     *
     * @return Map
     */
    public function slice(int $offset, int $length = null): Map
    {
        $map = new Map();

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

    /**
     * Returns a sorted copy of the map, based on an optional callable
     * comparator. The map will be sorted by value.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *
     * @return Map
     */
    public function sort(callable $comparator = null): Map
    {
        $sorted = $this->copy();

        if ($comparator) {
            usort($sorted->pairs, function($a, $b) use ($comparator) {
                return $comparator($a->value, $b->value);
            });

        } else {
            usort($sorted->pairs, function($a, $b) {
                return $a->value <=> $b->value;
            });
        }

        return $sorted;
    }

    /**
     * Returns a sorted copy of the map, based on an optional callable
     * comparator. The map will be sorted by key.
     *
     * @param callable|null $comparator Accepts two keys to be compared.
     *
     * @return Map
     */
    public function ksort(callable $comparator = null): Map
    {
        $sorted = $this->copy();

        if ($comparator) {
            usort($sorted->pairs, function($a, $b) use ($comparator) {
                return $comparator($a->key, $b->key);
            });

        } else {
            usort($sorted->pairs, function($a, $b) {
                return $a->key <=> $b->key;
            });
        }

        return $sorted;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->pairs as $pair) {
            $array[$pair->key] = $pair->value;
        }

        return $array;
    }

    /**
     * Returns a sequence of all the associated values in the Map.
     *
     * @return Sequence
     */
    public function values(): Sequence
    {
        $sequence = new Vector();

        foreach ($this->pairs as $pair) {
            $sequence->push($pair->value);
        }

        return $sequence;
    }

    /**
     * Get iterator
     */
    public function getIterator()
    {
        foreach ($this->pairs as $pair) {
            yield $pair->key => $pair->value;
        }
    }

    /**
     * Debug Info
     */
    public function __debugInfo()
    {
        return $this->pairs()->toArray();
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->put($offset, $value);
    }

    /**
     * @inheritdoc
     *
     * @throws OutOfBoundsException
     */
    public function &offsetGet($offset)
    {
        $pair = $this->lookupKey($offset);

        if ($pair) {
            return $pair->value;
        }

        throw new OutOfBoundsException();
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset, null);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->get($offset, null) !== null;
    }
}
