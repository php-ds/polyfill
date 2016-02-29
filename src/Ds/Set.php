<?php
namespace Ds;

use OutOfBoundsException;
use Error;

final class Set implements \IteratorAggregate, \ArrayAccess, Collection
{
    use Traits\Collection;

    /**
     *
     */
    const MIN_CAPACITY = Map::MIN_CAPACITY;

    /**
     *
     */
    private $internal;

    /**
     * Creates a new set using the values of an array or Traversable object.
     * The keys of either will not be preserved.
     *
     * @param array|\Traversable $values
     */
    public function __construct($values = null)
    {
        $this->internal = new Map();

        if ($values) {
            if (is_integer($values)) {
                $this->allocate($values);
            } else {
                $this->addAll($values);
            }
        }
    }

    /**
     * Adds zero or more values to the set.
     *
     * @param mixed ...$values
     */
    public function add(...$values)
    {
        foreach ($values as $value) {
            $this->internal[$value] = null;
        }
    }

    /**
     * Adds all values in an array or iterable object to the set.
     *
     * @param array|\Traversable $values
     */
    public function addAll($values)
    {
        if ($values) {
            foreach ($values as $value) {
                $this->internal[$value] = null;
            }
        }
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
     * Returns the current capacity of the set.
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
     * Determines whether the set contains all of zero or more values.
     *
     * @param mixed ...$values
     *
     * @return bool true if at least one value was provided and the set
     *              contains all given values, false otherwise.
     */
    public function contains(...$values): bool
    {
        if ( ! $values) {
            return false;
        }

        foreach ($values as $value) {
            if ( ! $this->internal->containsKey($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function copy()
    {
        return new Set($this);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->internal);
    }

    /**
     * Creates a new set using values from this set that aren't in another set.
     *
     * Formally: A \ B = {x ∈ A | x ∉ B}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function diff(Set $set): Set
    {
        $diff = new Set();

        foreach ($this as $value) {
            if ($set->contains($value)) {
                continue;
            }

            $diff->add($value);
        }

        return $diff;


    }

    /**
     * Creates a new set using values in either this set or in another set,
     * but not in both.
     *
     * Formally: A ⊖ B = {x : x ∈ (A \ B) ∪ (B \ A)}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function xor(Set $set): Set
    {
        $xor = new Set();

        foreach ($this as $value) {
            if ( ! $set->contains($value)) {
                $xor->add($value);
            }
        }

        foreach ($set as $value) {
            if ( ! $this->contains($value)) {
                $xor->add($value);
            }
        }

        return $xor;
    }

    /**
     * Returns a new set containing only the values for which a callback
     * returns true. A boolean test will be used if a callback is not provided.
     *
     * @param callable|null $callback Accepts a value, returns a boolean result:
     *                                true : include the value,
     *                                false: skip the value.
     *
     * @return Set
     */
    public function filter(callable $callback = null): Set
    {
        $filtered = new Set();

        if ($callback) {

            //
            foreach ($this as $value) {
                if ($callback($value)) {
                    $filtered->add($value);
                }
            }
        } else {

            //
            foreach ($this as $value) {
                if ($value) {
                    $filtered->add($value);
                }
            }
        }

        return $filtered;
    }

    /**
     * Returns the first value in the set.
     *
     * @return mixed the first value in the set.
     */
    public function first()
    {
        return $this->internal->first()->key;
    }

    /**
     * Returns the value at a specified position in the set.
     *
     * @throws \OutOfRangeException
     */
    public function get(int $position)
    {
        return $this->internal->skip($position)->key;
    }

    /**
     * Creates a new set using values common to both this set and another set.
     * In other words, returns a copy of this set with all values removed that
     * aren't in the other set.
     *
     * Formally: A ∩ B = {x : x ∈ A ∧ x ∈ B}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function intersect(Set $set): Set
    {
        $intersect = new Set();

        foreach ($this as $value) {
            if ($set->contains($value)) {
                $intersect->add($value);
            }
        }

        return $intersect;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return $this->internal->isEmpty();
    }

    /**
     * Joins all values of the set into a string, adding an optional 'glue'
     * between them. Returns an empty string if the set is empty.
     *
     * @param string $glue
     *
     * @return string
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->toArray());
    }

    /**
     * Returns the last value in the set.
     *
     * @return mixed the last value in the set.
     */
    public function last()
    {
        return $this->internal->last()->key;
    }

    /**
     * Iteratively reduces the set to a single value using a callback.
     *
     * @param callable $callback Accepts the carry and current value, and
     *                           returns an updated carry value.
     *
     * @param mixed|null $initial Optional initial carry value.
     *
     * @return mixed The carry value of the final iteration, or the initial
     *               value if the set was empty.
     */
    public function reduce(callable $callback, $initial = null)
    {
        $carry = $initial;

        foreach ($this as $value) {
            $carry = $callback($carry, $value);
        }

        return $carry;
    }

    /**
     * Removes zero or more values from the set.
     *
     * @param mixed ...$values
     */
    public function remove(...$values)
    {
        foreach ($values as $value) {
            $this->internal->remove($value, null);
        }
    }

    /**
     * Returns a reversed copy of the set.
     */
    public function reverse(): Set
    {
        $reversed = new Set();
        $reversed->internal = $this->internal->reverse();
        return $reversed;
    }

    /**
     * Returns a subset of a given length starting at a specified offset.
     *
     * @param int $offset If the offset is non-negative, the set will start
     *                    at that offset in the set. If offset is negative,
     *                    the set will start that far from the end.
     *
     * @param int $length If a length is given and is positive, the resulting
     *                    set will have up to that many values in it.
     *                    If the requested length results in an overflow, only
     *                    values up to the end of the set will be included.
     *
     *                    If a length is given and is negative, the set
     *                    will stop that many values from the end.
     *
     *                    If a length is not provided, the resulting set
     *                    will contains all values between the offset and the
     *                    end of the set.
     *
     * @return Set
     */
    public function slice(int $offset, int $length = null): Set
    {
        $sliced = new Set();
        $sliced->internal = $this->internal->slice($offset, $length);
        return $sliced;
    }

    /**
     * Returns a sorted copy of the set, based on an optional callable
     * comparator. Natural ordering will be used if a comparator is not given.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     *
     * @return Set
     */
    public function sort(callable $comparator = null): Set
    {
        $set = new Set();
        $set->internal = $this->internal->sort($comparator);
        return $set;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * Creates a new set that contains the values of this set as well as the
     * values of another set.
     *
     * Formally: A ∪ B = {x: x ∈ A ∨ x ∈ B}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function union(Set $set): Set
    {
        $union = new Set();

        foreach ($this as $value) {
            $union->add($value);
        }

        foreach ($set as $value) {
            $union->add($value);
        }

        return $union;
    }

    /**
     *
     */
    public function getIterator()
    {
        foreach ($this->internal as $key => $value) {
            yield $key;
        }
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->add($value);
            return;
        }

        throw new OutOfBoundsException();
    }

    public function offsetGet($offset)
    {
        return $this->internal->skip($offset)->key;
    }

    public function offsetExists($offset)
    {
        throw new Error();
    }

    public function offsetUnset($offset)
    {
        throw new Error();
    }
}
