<?php
namespace Ds;

use ArrayAccess;
use Ds\Map;
use IteratorAggregate;
use OutOfRangeException;
use UnderflowException;

/**
 * A Sequence is an arrangement of values in a contiguous buffer that grows and
 * shrinks automatically. It’s the most efficient sequential structure because
 * a value’s index is a direct mapping to its index in the buffer, and the
 * growth factor isn't bound to a specific multiple or exponent.
 *
 * @package Ds
 */
final class Sequence implements IteratorAggregate, ArrayAccess, Collection, Allocated
{
    use Traits\Collection;
    use Traits\Allocated;

    /**
     *
     */
    const MIN_CAPACITY = 8;

    /**
     * @var array internal array used to store the values of the sequence.
     */
    private $array = [];

   /**
     * Creates a new sequence using the values of either an array or iterable
     * object as initial values.
     *
     * @param array|\Traversable|null $values
     */
    public function __construct($values = null)
    {
        if ($values) {
            $this->pushAll($values);
        }
    }

    /**
     *
     */
    public function all(callable $predicate = null): bool
    {
        $predicate = $predicate ?? 'boolval';

        foreach ($this->array as $value) {
            if ( ! $predicate($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     */
    private function allowsSquareBracketAccess($value)
    {
        return is_array($value)
            || is_string($value)
            || (is_object($value) && $value instanceof \ArrayAccess);
    }

    /**
     *
     */
    public function any(callable $predicate = null): bool
    {
        $predicate = $predicate ?? 'boolval';

        foreach ($this->array as $value) {
            if ($predicate($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates every value in the sequence by applying a callback, using the
     * return value as the new value.
     *
     * @param callable $callback Accepts the value, returns the new value.
     */
    public function apply(callable $callback)
    {
        foreach ($this->array as &$value) {
            $value = $callback($value);
        }
    }

    /**
     * @param mixed $value
     * @param null|int $num
     */
    public function fill($value, int $num = null)
    {
        if (null === $num) {
            \array_walk($this->array, function (&$v) use ($value) {
                $v = $value;
            });
        } else {
            if ([] === $this->array) {
                $this->array = \array_fill(0, $num, $value);
            } else {
                for ($i = 0; $i < $num; $i++) {
                    $this->array[$i] = $value;
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->array = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * Determines whether the sequence contains all of zero or more values.
     *
     * @param mixed ...$values
     *
     * @return bool true if at least one value was provided and the sequence
     *              contains all given values, false otherwise.
     */
    public function contains(...$values): bool
    {
        foreach ($values as $value) {
            if (is_null($this->find($value))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->array);
    }

    /**
     * Iterates through the sequence, invoking a given callback for each value.
     *
     * @param callable $callback Callback public function to invoke for each value.
     *
     * @return bool false to break, anything else including null to continue.
     */
    public function each(callable $callback): bool
    {
        foreach ($this->array as $value) {
            if ($callback($value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a new sequence containing only the values for which a callback
     * returns true. A boolean test will be used if a callback is not provided.
     *
     * @param callable|null $predicate Accepts a value, returns a boolean:
     *                                 - true : include the value,
     *                                 - false: skip the value.
     *
     * @return Sequence
     */
    public function filter(callable $callback = null): Sequence
    {
        return new self(array_filter($this->array, $callback ?: 'boolval'));
    }

    /**
     * Returns the index of a given value, or null if it could not be found.
     *
     * @param mixed $value
     *
     * @return int|null
     */
    public function find($value)
    {
        $index = array_search($value, $this->array, true);
        return is_integer($index) ? $index : null;
    }

    /**
     * Returns the first value in the sequence.
     *
     * @return mixed
     *
     * @throws \UnderflowException if the sequence is empty.
     */
    public function first()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->array[0];
    }

    /**
     * Returns the value at a given index (position) in the sequence.
     *
     * @param int $index
     *
     * @return mixed
     *
     * @throws \OutOfRangeException if the index is not in the range [0, size-1]
     */
    public function get(int $index)
    {
        if ( ! $this->validIndex($index)) {
            throw new OutOfRangeException();
        }

        return $this->array[$index];
    }

    /**
     * Determines which group a value belongs to.
     */
    private function getGroup($value, $key)
    {
        if (is_callable($key)) {
            return $key($value);
        }

        if ($this->allowsSquareBracketAccess($value)) {
            return $value[$key];
        }

        return $value->$key;
    }

    /**
     * Creates a map associating all values in sequence with a determined group,
     * where the keys are the groups and the values are each a new sequence of
     * the values that are in that group.
     *
     * @param mixed $group An offset or callable to determine which group a
     *                     value belongs to. If a callable is passed, the value
     *                     will be passed as the only argument, and the return
     *                     value will be the group.
     *
     * @return Map
     */
    public function groupBy($key): Map
    {
        $groups = new Map();

        foreach ($this->array as $value) {

            // Determine what this value's group key is.
            $group = $this->getGroup($value, $key);

            $groups->update($group, function($values) use ($value) {

                // Use previous group or create a new one.
                $values = $values ?? new self();
                $values->push($value);
                return $values;
            });
        }

        return $groups;
    }

    /**
     * Inserts zero or more values at a given index.
     *
     * Each value after the index will be moved one position to the right.
     * Values may be inserted at an index equal to the size of the sequence.
     *
     * @param int   $index
     * @param mixed ...$values
     *
     * @throws \OutOfRangeException if the index is not in the range [0, n]
     */
    public function insert(int $index, ...$values)
    {
        if ( ! $this->validIndex($index) && $index !== count($this)) {
            throw new OutOfRangeException();
        }

        array_splice($this->array, $index, 0, $values);
    }

    /**
     * Joins all values of the sequence into a string, adding an optional 'glue'
     * between them. Returns an empty string if the sequence is empty.
     *
     * @param string $glue
     *
     * @return string
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->array);
    }

    /**
     * Returns the last value in the sequence.
     *
     * @return mixed
     *
     * @throws \UnderflowException if the sequence is empty.
     */
    public function last()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->array[count($this) - 1];
    }

    /**
     * Returns a new sequence using the results of applying a callback to each
     * value.
     *
     * @param callable $callback
     *
     * @return Sequence
     */
    public function map(callable $callback): Sequence
    {
        return new self(array_map($callback, $this->array));
    }

    /**
     * Returns the result of adding all given values to the sequence.
     *
     * @param array|\Traversable $values
     *
     * @return Sequence
     */
    public function merge($values): Sequence
    {
        $copy = $this->copy();
        $copy->pushAll($values);
        return $copy;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
        if ( ! $this->validIndex($offset)) {
            throw new OutOfRangeException();
        }

        return $this->array[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        if (is_integer($offset) && $this->validIndex($offset)) {
            $this->remove($offset);
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return is_integer($offset)
            && $this->validIndex($offset)
            && $this->get($offset) !== null;
    }

    /**
     * Moves all the values for which a given predicate returns true to the
     * front of the sequence, and all those that don't to the back.
     *
     * Relative order is preserved.
     *
     * @param callable $predicate Accepts a single value, returns true if the
     *                            value should be moved to the front, or false
     *                            if it should be moved to the back.
     *
     * @return int The position where the second part of the partition begins,
     *             also the number of values that passed the predicate.
     */
    public function partition(callable $predicate = null): int
    {
        $pass = [];
        $fail = [];

        // Default predicate simply tests for true or false.
        $predicate = $predicate ?? 'boolval';

        foreach ($this->array as $value) {
            $predicate($value)
                ? $pass[] = $value
                : $fail[] = $value;
        }

        $this->array = array_merge($pass, $fail);
        return count($pass);
    }

    /**
     * Creates a new sequence containing the values associated with a given key
     * or index of each value in the sequence.
     *
     * Array access takes priority over public properties of an object.
     *
     * @param mixed $key They key used to retrieve a value.
     *
     * @return Sequence
     */
    public function pluck($key): Sequence
    {
        $sequence = new self();

        foreach ($this->array as $value) {
            $this->allowsSquareBracketAccess($value)
                ? $sequence[] = $value[$key]
                : $sequence[] = $value->$key;
        }

        return $sequence;
    }

    /**
     * Removes the last value in the sequence, and returns it.
     *
     * @param int $count
     *
     * @return mixed|Sequence what was the last value in the sequence.
     *
     * @throws \UnderflowException if the sequence is empty.
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_pop($this->array);
        $this->adjustCapacity();
        return $value;
    }

    /**
     * Adds a value to the end of the sequence.
     *
     * @param mixed $value
     */
    public function push(...$values)
    {
        $this->array = array_merge($this->array, $values);
        $this->adjustCapacity();
    }

    /**
     * Pushes all values of either an array or traversable object.
     */
    private function pushAll($values)
    {
        foreach ($values as $value) {
            $this->push($value);
        }

        $this->adjustCapacity();
    }

    /**
     * Iteratively reduces the sequence to a single value using a callback.
     *
     * @param callable $callback Accepts the carry and current value, and
     *                           returns an updated carry value.
     *
     * @param mixed|null $initial Optional initial carry value.
     *
     * @return mixed The carry value of the final iteration, or the initial
     *               value if the sequence was empty.
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->array, $callback, $initial);
    }

    /**
     * Removes and returns the value at a given index in the sequence.
     *
     * @param int $index this index to remove.
     *
     * @return mixed the removed value.
     *
     * @throws \OutOfRangeException if the index is not in the range [0, size-1]
     */
    public function remove(int $index)
    {
        if ( ! $this->validIndex($index)) {
            throw new OutOfRangeException();
        }

        $value = array_splice($this->array, $index, 1, null)[0];
        $this->adjustCapacity();
        return $value;
    }

    /**
     * Reverses the sequence in-place.
     */
    public function reverse()
    {
        $this->array = array_reverse($this->array);
    }

    /**
     * Returns a reversed copy of the sequence.
     *
     * @return Sequence
     */
    public function reversed(): Sequence
    {
        return new self(array_reverse($this->array));
    }

    /**
     * Converts negative or large rotations into the minimum positive number
     * of rotations required to rotate the sequence by a given $r.
     */
    private function normalizeRotations(int $r)
    {
        $n = count($this);

        if ($n < 2) return 0;
        if ($r < 0) return $n - (abs($r) % $n);

        return $r % $n;
    }

    /**
     * Swaps two values by index.
     */
    private function _swap(int $a, int $b)
    {
        $temp = $this->array[$a];
        $this->array[$a] = $this->array[$b];
        $this->array[$b] = $temp;
    }

    /**
     * Swaps two values by index.
     */
    public function swap(int $a, int $b)
    {
        if ( ! $this->validIndex($a) || ! $this->validIndex($b)) {
            throw new OutOfRangeException();
        }

        if ($a === $b) {
            return;
        }

        $this->_swap($a, $b);
    }

    /**
     * Reverses a range within this sequence.
     */
    private function reverseRange(int $a, int $b)
    {
        while ($a < $b) {
            $this->_swap($a++, --$b);
        }
    }

    /**
     * Rotates the sequence by a given number of rotations, which is equivalent
     * to successive calls to 'shift' and 'push' if the number of rotations is
     * positive, or 'pop' and 'unshift' if negative.
     *
     * @param int $rotations The number of rotations (can be negative).
     */
    public function rotate(int $rotations)
    {
        $r = $this->normalizeRotations($rotations);
        $n = $this->count();

        $this->reverseRange(0,  $r);
        $this->reverseRange($r, $n);
        $this->reverseRange(0,  $n);
    }

    /**
     * Replaces the value at a given index in the sequence with a new value.
     *
     * @param int   $index
     * @param mixed $value
     *
     * @throws \OutOfRangeException if the index is not in the range [0, size-1]
     */
    public function set(int $index, $value)
    {
        if ( ! $this->validIndex($index)) {
            throw new OutOfRangeException();
        }

        $this->array[$index] = $value;
    }

    /**
     * Removes and returns the first value in the sequence.
     *
     * @return mixed|Sequence what was the first value in the sequence.
     *
     * @throws \UnderflowException if the sequence was empty.
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_shift($this->array);
        $this->adjustCapacity();
        return $value;
    }

    /**
     *
     */
    public function shuffle()
    {
        shuffle($this->array);
    }

    /**
     * Returns a sub-sequence of a given length starting at a specified index.
     *
     * @param int $index  If the index is positive, the sequence will start
     *                    at that index in the sequence. If index is negative,
     *                    the sequence will start that far from the end.
     *
     * @param int $length If a length is given and is positive, the resulting
     *                    sequence will have up to that many values in it.
     *                    If the length results in an overflow, only values
     *                    up to the end of the sequence will be included.
     *
     *                    If a length is given and is negative, the sequence
     *                    will stop that many values from the end.
     *
     *                    If a length is not provided, the resulting sequence
     *                    will contain all values between the index and the
     *                    end of the sequence.
     *
     * @return Sequence
     */
    public function slice(int $offset, int $length = null): Sequence
    {
        if (func_num_args() === 1) {
            $length = count($this);
        }

        return new self(array_slice($this->array, $offset, $length));
    }

    /**
     *
     *
     * @param int $index  If the index is positive, the sequence will start
     *                    at that index in the sequence. If index is negative,
     *                    the sequence will start that far from the end.
     *
     * @param int $length If a length is given and is positive, the resulting
     *                    sequence will have up to that many values in it.
     *                    If the length results in an overflow, only values
     *                    up to the end of the sequence will be included.
     *
     *                    If a length is given and is negative, the sequence
     *                    will stop that many values from the end.
     *
     *                    If a length is not provided, the resulting sequence
     *                    will contain all values between the index and the
     *                    end of the sequence.
     *
     * @param mixed $replacement
     *
     * @return Sequence
     */
    public function splice(
        int $index,
        int $length  = null,
        $replacement = null): Sequence
    {
        if (func_num_args() === 1) {
            $length = count($this);
        }

        if ($replacement === null) {
            $replacement = [];
        }

        // Check if we can use the replacement as an iterable object.
        else if (is_object($replacement) && $replacement instanceof \Traversable) {
            $replacement = iterator_to_array($replacement);
        }

        // Fallback cast to array, which is consistent with array_splice.
        else {
            $replacement = (array) $replacement;
        }

        return new self(
            array_splice($this->array, $index, $length, $replacement)
        );
    }

    /**
     * Sorts the sequence in-place, based on an optional callable comparator.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     */
    public function sort(callable $comparator = null)
    {
        if ($comparator) {
            usort($this->array, $comparator);
        } else {
            sort($this->array);
        }
    }

    /**
     * Returns a sorted copy of the sequence, based on an optional callable
     * comparator. Natural ordering will be used if a comparator is not given.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     *
     * @return Sequence
     */
    public function sorted(callable $comparator = null): Sequence
    {
        $copy = $this->copy();
        $copy->sort($comparator);
        return $copy;
    }

    /**
     * Returns the sum of all values in the sequence.
     *
     * @return int|float The sum of all the values in the sequence.
     */
    public function sum()
    {
        return array_sum($this->array);
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return $this->array;
    }

    /**
     * Adds a value to the front of the sequence.
     *
     * @param mixed ...$values
     */
    public function unshift(...$values)
    {
        if ($values) {
            $this->array = array_merge($values, $this->array);
            $this->adjustCapacity();
        }
    }

    /**
     *
     */
    private function validIndex(int $index)
    {
        return $index >= 0 && $index < count($this);
    }
}

