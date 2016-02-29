<?php
namespace Ds;

use UnderflowException;
use OutOfRangeException;

final class Map implements \IteratorAggregate, \ArrayAccess, Collection
{
    use Traits\Collection;
    use Traits\SquaredCapacity;

    /**
     *
     */
    const MIN_CAPACITY = 8;

    /**
     * @var array
     */
    private $pairs = [];

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
                $this->putAll($values);
            }
        }
	}

    /**
     * @inheritDoc
     */
    public function clear()
	{
        $this->pairs  = [];
        $this->capacity = self::MIN_CAPACITY;
	}

    /**
     *
     */
    public function removeAll($keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    /**
     *
     */
    public function first(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->pairs[0]->copy();
    }

    /**
     *
     */
    public function last(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return end($this->pairs)->copy();
    }

    /**
     *
     */
    public function skip(int $position): Pair
    {
        if ($position < 0 || $position >= count($this->pairs)) {
            throw new OutOfRangeException();
        }

        return $this->pairs[$position]->copy();
    }

    /**
     *
     */
    public function merge(Map $map): Map
    {
        $merged = new Map();

        foreach ($this as $key => $value) {
            $merged->put($key, $value);
        }

        foreach ($map as $key => $value) {
            $merged->put($key, $value);
        }

        return $merged;
    }

    /**
     *
     */
    public function intersect(Map $map): Map
    {
        $intersect = new Map();

        foreach ($this as $key => $value) {
            if ($map->containsKey($key)) {
                $intersect->put($key, $value);
            }
        }

        return $intersect;
    }

    /**
     *
     */
    public function diff(Map $map): Map
    {
        $diff = new Map();

        foreach ($this as $key => $value) {
            if ($map->containsKey($key)) {
                continue;
            }

            $diff->put($key, $value);
        }

        return $diff;
    }

    /**
     *
     */
    public function xor(Map $map): Map
    {
        $xor = new Map();

        foreach ($this as $key => $value) {
            if ( ! $map->containsKey($key)) {
                $xor->put($key, $value);
            }
        }

        foreach ($map as $key => $value) {
            if ( ! $this->containsKey($key)) {
                $xor->put($key, $value);
            }
        }

        return $xor;
    }

    /**
     *
     */
    private function identical($a, $b): bool
    {
        if (is_object($a) && $a instanceof Hashable){
            return $a->equals($b);
        }

        return $a === $b;
    }

    /**
     *
     */
    private function &lookup($key)
    {
        foreach ($this->pairs as $pair) {
            if ($this->identical($pair->key, $key)) {
                return $pair;
            }
        }

        $pair = null;
        return $pair;
    }

    /**
     * Returns whether an association for all of zero or more keys exist.
     *
     * @param mixed ...$keys
     *
     * @return bool true if at least one value was provided and the map
     *              contains all given keys, false otherwise.
     */
    public function containsKey(...$keys): bool
	{
        if ( ! $keys) {
            return false;
        }

        foreach ($keys as $key) {
            if ( ! $this->lookup($key)) {
                return false;
            }
        }

        return true;
	}

    /**
     * Returns whether an association for all of zero or more values exist.
     *
     * @param mixed ...$values
     *
     * @return bool true if at least one value was provided and the map
     *              contains all given values, false otherwise.
     */
    public function containsValue(...$values): bool
	{
        if ( ! $values) {
            return false;
        }

        foreach ($values as $value) {
            foreach ($this->pairs as $pair) {
                if ($pair->value === $value) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
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
     * Returns a new map containing only the values for which a callback
     * returns true. A boolean test will be used if a callback is not provided.
     *
     * @param callable|null $callback Accepts a key and a value, and returns:
     *                                true : include the value,
     *                                false: skip the value.
     *
     * @return Map
     */
    public function filter(callable $callback = null): Map
	{
        $filtered = new self();

        foreach ($this->pairs as $pair) {
            $k = $pair->key;
            $v = $pair->value;

            if ($callback ? $callback($k, $v) : $v) {
                $filtered->put($k, $v);
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
     * @throws \OutOfBoundsException if no default was provided and the key is
     *                               not associated with a value.
     */
    public function get($key, $default = null)
	{
        if (($pair = $this->lookup($key))) {
            return $pair->value;
        }

        if (func_num_args() === 1) {
            throw new \OutOfBoundsException();
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
     * The keys will be identical in both maps.
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
        $pair = $this->lookup($key);

        if ($pair) {
            $pair->value = $value;
            return;
        }

        $this->adjustCapacity();
        $this->pairs[] = new Pair($key, $value);
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

            // Check if the pair is the one we're looking for
            if ($this->identical($pair->key, $key)) {

                // Delete pair, return its value
                $value = $pair->value;

                array_splice($this->pairs, $position, 1, null);
                $this->adjustCapacity();

                return $value;
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

        foreach (array_reverse($this->pairs) as $pair) {
            $reversed[$pair->key] = $pair->value;
        }

        return $reversed;
	}

    /**
     * Returns a sub-sequence of a given length starting at a specified offset.
     *
     * @param int $offset If the offset is non-negative, the map will start
     *                    at that offset in the map. If offset is negative,
     *                    the map will start that far from the end.
     *
     * @param int $length If a length is given and is positive, the resulting
     *                    set will have up to that many pairs in it.
     *                    If the requested length results in an overflow, only
     *                    pairs up to the end of the map will be included.
     *
     *                    If a length is given and is negative, the map
     *                    will stop that many pairs from the end.
     *
     *                    If a length is not provided, the resulting map
     *                    will contains all pairs between the offset and the
     *                    end of the map.
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
     * comparator. The map will be sorted by key if a comparator is not given.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     *
     * @return Map
     */
    public function sort(callable $comparator = null): Map
	{
        $copy = $this->copy();

        if ($comparator) {
            usort($copy->pairs, $comparator);
        } else {
            usort($copy->pairs, function($a, $b) {
                return $a->key <=> $b->key;
            });
        }

        return $copy;
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
            $sequence[] = $pair->value;
        }

        return $sequence;
	}

    /**
     *
     */
    public function getIterator()
    {
        foreach ($this->pairs as $pair) {
            yield $pair->key => $pair->value;
        }
    }

    /**
     *
     */
    public function __debugInfo()
    {
        $debug = [];

        foreach ($this->pairs as $pair) {
            $debug[] = [$pair->key, $pair->value];
        }

        return $debug;
    }

    /**
     *
     */
    public function offsetSet($offset, $value)
    {
        $this->put($offset, $value);
    }

    /**
     *
     */
    public function &offsetGet($offset)
    {
        $pair = $this->lookup($offset);

        if ($pair) {
            return $pair->value;
        }

        throw new OutOfBoundsException();
    }

    /**
     *
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset, null);
    }

    /**
     *
     */
    public function offsetExists($offset)
    {
        return $this->get($offset, null) !== null;
    }
}
