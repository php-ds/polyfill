<?php
namespace Ds;

final class Bucket {
    public $key;
    public $value;

    public function __construct($key, $value) {
        $this->key   = $key;
        $this->value = $value;
    }
}

final class Map implements \IteratorAggregate, \ArrayAccess, Collection
{
    /**
     *
     */
    const MIN_CAPACITY = 8;

    /**
     * @var array
     */
    private $buckets = [];

    /**
     * @var int
     */
    private $capacity = self::MIN_CAPACITY;

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

    private function fixedCapacity(int $size)
    {
        return max(self::MIN_CAPACITY, pow(2, ceil(log($size, 2))));
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
        $this->capacity = max($this->capacity, $this->fixedCapacity($capacity));
	}

    /**
     * Returns the current capacity of the map.
     *
     * @return int
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
        $this->buckets  = [];
        $this->capacity = self::MIN_CAPACITY;
	}

    private function getHash($value)
    {
        return 0;
    }

    private function identical($a, $b): bool
    {
        if (is_object($a) && $a instanceof Hashable){
            return $a->equals($b);
        }

        return $a === $b;
    }

    private function &lookup($key)
    {
        foreach ($this->buckets as $bucket) {
            if ($this->identical($bucket->key, $key)) {
                return $bucket;
            }
        }

        $bucket = null;
        return $bucket;
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
            foreach ($this->buckets as $bucket) {
                if ($bucket->value === $value) {
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
        return count($this->buckets);
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

        foreach ($this->buckets as $bucket) {
            $k = $bucket->key;
            $v = $bucket->value;

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
        if (($bucket = $this->lookup($key))) {
            return $bucket->value;
        }

        if (func_num_args() === 1) {
            throw new \OutOfBoundsException();
        }

        return $default;
	}

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
	{
        return count($this->buckets) === 0;
	}

    /**
     *
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Returns a set of all the keys in the map.
     *
     * @return Set
     */
    public function keys(): Set
	{
        $set = new Set();

        foreach ($this->buckets as $bucket) {
            $set[] = $bucket->key;
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

        foreach ($this->buckets as $bucket) {
            $mapped[$bucket->key] = $callback($bucket->key, $bucket->value);
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

        foreach ($this->buckets as $bucket) {
            $sequence[] = new Pair($bucket->key, $bucket->value);
        }

        return $sequence;
	}

    private function adjustCapacity()
    {
        $size = count($this);

        if ($size > $this->capacity) {
            $this->capacity *= 2;
        }

        if ($size <= $this->capacity / 4) {
            $this->capacity = max(self::MIN_CAPACITY, $this->capacity / 2);
        }
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
        $bucket = $this->lookup($key);

        if ($bucket) {
            $bucket->value = $value;
            return;
        }

        $this->buckets[] = new Bucket($key, $value);
        $this->adjustCapacity();
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

        foreach ($this->buckets as $bucket) {
            $carry = $callback($carry, $bucket->key, $bucket->value);
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
        foreach ($this->buckets as $position => $bucket) {

            // Check if the bucket is the one we're looking for
            if ($this->identical($bucket->key, $key)) {

                // Delete bucket, return its value
                $value = $bucket->value;
                unset($this->buckets[$position]);
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

        foreach (array_reverse($this->buckets) as $bucket) {
            $reversed[$bucket->key] = $bucket->value;
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
        $slice = new self();

        foreach (array_slice($this->buckets, $offset, $length) as $bucket) {
            $slice[$bucket->key] = $bucket->value;
        }

        return $slice;
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
            usort($copy->buckets, function($a, $b) use ($comparator) {
                return $comparator(
                    new Pair($a->key, $a->value),
                    new Pair($b->key, $b->value)
                );
            });

        } else {
            usort($copy->buckets, function($a, $b) {
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

        foreach ($this->buckets as $bucket) {
            $array[$bucket->key] = $bucket->value;
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

        foreach ($this->buckets as $bucket) {
            $sequence[] = $bucket->value;
        }

        return $sequence;
	}


    /**
     *
     */
    public function getIterator()
    {
        foreach ($this->buckets as $bucket) {
            yield $bucket->key => $bucket->value;
        }
    }

    /**
     *
     */
    public function __debugInfo()
    {
        $debug = [];

        foreach ($this->buckets as $bucket) {
            $debug[] = [$bucket->key, $bucket->value];
        }

        return $debug;
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
        $this->put($offset, $value);
    }

    /**
     *
     */
    public function &offsetGet($offset)
    {
        $bucket = $this->lookup($offset);

        if ($bucket) {
            return $bucket->value;
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
