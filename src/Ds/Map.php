<?php
namespace Ds;

use OutOfBoundsException;
use OutOfRangeException;
use Traversable;
use UnderflowException;

final class MapNode
{
    public $pair;
    public $prev;
    public $next;
    public $hash;
}

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

    private $head;
    private $tail;
    private $size;

    private $buckets;

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
        } elseif (is_integer($values)) {
            $this->allocate($values);
        }
    }

    private function reset()
    {
        $this->head = null;
        $this->tail = null;
        $this->size = 0;

        $this->buckets = [];
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

        return $this->head->pair->copy();
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

        return $this->tail->pair->copy();
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
        if ($position < 0 || $position > ($this->size - 1)) {
            throw new OutOfRangeException();
        }

        return $this->seek($position)->pair->copy();
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
            return $map->containsKey($key);
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
            return ! $map->containsKey($key);
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
            return $this->containsKey($key) ^ $map->containsKey($key);
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
     * Lookup
     *
     * @param $key
     *
     * @return Pair|null
     */
    private function lookupKey($key)
    {
        for ($node = $this->head; $node; $node = $node->next) {
            if ($this->keysAreEqual($node->pair->key, $key)) {
                return $node->pair;
            }
        }
    }

    /**
     * Lookup
     *
     * @param $key
     *
     * @return Pair|null
     */
    private function lookupValue($value)
    {
        for ($node = $this->head; $node; $node = $node->next) {
            if ($node->pair->value === $value) {
                return $node->pair;
            }
        }
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
        if (empty($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            if ( ! $this->lookupKey($key)) {
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
        if (empty($values)) {
            return false;
        }

        foreach ($values as $value) {
            if ( ! $this->lookupValue($value)) {
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
        return new self($this);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     *
     */
    private function filterUsingPredicate(callable $predicate)
    {
        $filtered = new self();

        foreach ($this as $key => $value) {
            if ($predicate($key, $value)) {
                $filtered->put($key, $value);
            }
        }

        return $filtered;
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
    public function filter(callable $predicate = null): Map
    {
        if ($predicate) {
            return $this->filterUsingPredicate($predicate);
        }

        $filtered = new self();

        foreach ($this as $key => $value) {
            if ($value) {
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
        $pair = $this->lookupKey($key);

        if ($pair) {
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

        foreach ($this as $key => $value) {
            $set->add($key);
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

        foreach ($this as $key => $value) {
            $mapped[$key] = $callback($key, $value);
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

        for ($node = $this->head; $node; $node = $node->next) {
            $sequence[] = $node->pair->copy();
        }

        return $sequence;
    }

    private function getHash($key)
    {
        if (is_object($key)) {
            if ($key instanceof Hashable) {
                return $key->hash();
            }

            return spl_object_hash($key);
        }

        if (is_array($key)) {
            return json_encode($key);
        }

        return $key;
    }

    private function initNode($key, $value, $hash): MapNode
    {
        $node = new MapNode();
        $node->pair = new Pair($key, $value);
        $node->next = null;
        $node->prev = $this->tail;
        $node->hash = $hash;

        return $node;
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
        // printf("-----\n");
        // printf("put $key => $value\n");
        //
        $hash = $this->getHash($key);

        // printf("hash = $hash\n");

        if ($this->isEmpty()) {
            // printf("empty, so head and tail are both new node\n");
            $node = $this->initNode($key, $value, $hash);
            $this->head = $node;
            $this->tail = $node;
            $this->buckets[$hash] = $node;

        } else {

            // printf("not empty\n");

            //
            $link = $this->buckets[$hash] ?? null;

            if ($link) {

                // printf("Collision chain exists\n");
                // Collision chain exists
                // Find the last node in the chain
                for (;;) {
                    if ($this->keysAreEqual($link->pair->key, $key)) {
                        // Already in map, just replace the value
                        $link->pair->value = $value;
                        // printf("Key found so replace value and return\n");
                        return;
                    }

                    //
                    if ( ! $link->next) {
                        // printf("Last node, bail\n");
                        break;
                    }

                    $link = $link->next;
                }

                // printf("Could not find key, so append to chain\n");
                // Could not find key, so append to chain
                $node = $this->initNode($key, $value, $hash);
                $node->prev = $link;
                $link->next = $node;
                $this->tail = $node;


            } else {
                // printf("Collision chain does not exist\n");

                // Collision chain does not exist
                $node = $this->initNode($key, $value, $hash);
                $this->buckets[$hash] = $node;

                $this->tail->next = $node;
                $node->prev = $this->tail;
                $this->tail = $node;
            }
        }

        $this->adjustCapacity();
        $this->size++;
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

    private function removeNode($node)
    {
        $value = $node->pair->value;

        if ($node->prev) {
            $node->prev->next = $node->next;
        }

        if ($node->next) {
            $node->next->prev = $node->prev;
        }

        if ($node === $this->head) {
            $this->head = null;
        }

        if ($node === $this->tail) {
            $this->tail = null;
        }

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
        $hash = $this->getHash($key);
        $node = $this->buckets[$hash] ?? null;

        if ($node) {

            // See if the head of the chain is actually what we're looking for
            if ($this->keysAreEqual($node->pair->key, $key)) {
                $value = $this->removeNode($node);
                unset($this->buckets[$hash]);
                return $value;
            }
        }

        // foreach ($this->buckets as $hash => $node) {




        //     //
        //     foreach ($nodes as $position => $node) {

        //         //
        //         if ($this->keysAreEqual($node->pair->key, $key)) {

        //             //
        //             $value = $node->pair->value;

        //             //
        //             if ($this->size === 1) {
        //                 $this->head = null;
        //                 $this->tail = null;

        //             } else {

        //                 //
        //                 if ($node === $this->tail) {
        //                     $this->tail = $this->tail->prev;
        //                 }

        //                 //
        //                 if ($node === $this->head) {
        //                     $this->head = $this->head->next;
        //                 }

        //                 //
        //                 if ($node->next) {
        //                     $node->next->prev = $node->prev;
        //                 }

        //                 //
        //                 if ($node->prev) {
        //                     $node->prev->next = $node->next;
        //                 }
        //             }

        //             // array_splice($nodes, $position, 1, null);
        //             unset($this->buckets[$hash][$position]);
        //             // unset($nodes[$position]); // Does this work? Maybe splice?
        //             $this->size--;

        //             $this->adjustCapacity();
        //             return;
        //         }
        //     }
        // }

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

        for ($node = $this->tail; $node; $node = $node->prev) {
            $reversed->put($node->pair->key, $node->pair->value);
        }

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
        $slice = new self();

        foreach ($this->pairs()->slice($offset, $length) as $pair) {
            $slice->put($pair->key, $pair->value);
        }

        return $slice;
    }

    private function seek(int $position)
    {
        //
        if ($position < $this->size / 2) {
            for ($node = $this->head; $position--; $node = $node->next);

        //
        } else {
            for ($node = $this->tail; $position--; $node = $node->prev);
        }

        return $node;
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
        $pairs = $this->pairs();

        if ($comparator) {
            usort($pairs, $comparator);
        } else {
            usort($pairs, function($a, $b) {
                return $a->key <=> $b->key;
            });
        }

        $sorted = new self();

        foreach ($pairs as $pair) {
            $sorted->put($pair->key, $pair->value);
        }

        return $sorted;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this as $key => $value) {
            $array[$key] = $value;
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
        return new Vector($this);
    }

    /**
     * Get iterator
     */
    public function getIterator()
    {
        for ($node = $this->head; $node; $node = $node->next) {
            yield $node->pair->key => $node->pair->value;
        }
    }

    /**
     * Debug Info
     */
    public function __debugInfo()
    {
        $debug = [];

        foreach ($this as $key => $value) {
            $debug[] = [$key, $value];
        }

        return $debug;
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
