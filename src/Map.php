<?php
namespace Ds;

use Error;
use OutOfBoundsException;
use OutOfRangeException;
use Traversable;
use UnderflowException;

/**
 * A Map is a sequential collection of key-value pairs, almost identical to an
 * array used in a similar context. Keys can be any type, but must be unique.
 *
 * @package Ds
 *
 * @template TKey
 * @template TValue
 * @implements Collection<TKey, TValue>
 */
final class Map implements Collection, \ArrayAccess
{
    use Traits\GenericCollection;
    use Traits\SquaredCapacity;

    const MIN_CAPACITY = 8;

    /**
     * @var int internal ordered index for the next pair ref.
     */
    private $nextPairIndex = 0;

    /**
     * @var array internal array to store ordered references to pairs. Although
     * the array has ascending integer indices, they aren't necessarily
     * contiguous.
     *
     * @psalm-var array<int, PairRef>
     */
    private $pairRefs = [];

    /**
     * @var array internal lookup table to quickly find pairs by hash key.
     *
     * @psalm-var array<array-key, list<PairRef>>
     */
    private $table = [];

    /**
     * Creates a new instance.
     *
     * @param iterable<mixed, mixed> $values
     *
     * @psalm-param iterable<TKey, TValue> $values
     */
    public function __construct(iterable $values = [])
    {
        if (func_num_args()) {
            $this->putAll($values);
        }
    }

    /**
     * Updates all values by applying a callback function to each value.
     *
     * @param callable $callback Accepts two arguments: key and value, should
     *                           return what the updated value will be.
     *
     * @psalm-param callable(TKey, TValue): TValue $callback
     */
    public function apply(callable $callback)
    {
        foreach ($this->pairRefs as $pairRef) {
            $pair = $pairRef->pair;
            $pair->value = $callback($pair->key, $pair->value);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->nextPairIndex = 0;
        $this->pairRefs = [];
        $this->table = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * Return the first Pair from the Map
     *
     * @return Pair
     *
     * @throws UnderflowException
     *
     * @psalm-return Pair<TKey, TValue>
     */
    public function first(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $pairRef = reset($this->pairRefs);
        return $pairRef->pair;
    }

    /**
     * Return the last Pair from the Map
     *
     * @return Pair
     *
     * @throws UnderflowException
     *
     * @psalm-return Pair<TKey, TValue>
     */
    public function last(): Pair
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $pairRef = end($this->pairRefs);
        return $pairRef->pair;
    }

    /**
     * Return the pair at a specified position in the Map
     *
     * @return Pair
     *
     * @throws OutOfRangeException
     *
     * @psalm-return Pair<TKey, TValue>
     */
    public function skip(int $position): Pair
    {
        if ($position < 0 || $position >= count($this->pairRefs)) {
            throw new OutOfRangeException();
        }
        $pairRef = array_slice($this->pairRefs, $position, 1)[0];
        return $pairRef->pair->copy();
    }

    /**
     * Returns the result of associating all keys of a given traversable object
     * or array with their corresponding values, as well as those of this map.
     *
     * @param array|\Traversable $values
     *
     * @return Map
     *
     * @template TKey2
     * @template TValue2
     * @psalm-param iterable<TKey2, TValue2> $values
     * @psalm-return Map<TKey|TKey2, TValue|TValue2>
     */
    public function merge($values): Map
    {
        $merged = new self($this->getIterator());
        $merged->putAll($values);
        return $merged;
    }

    /**
     * Creates a new map containing the pairs of the current instance whose keys
     * are also present in the given map. In other words, returns a copy of the
     * current map with all keys removed that are not also in the other map.
     *
     * @param Map $map The other map.
     *
     * @return Map A new map containing the pairs of the current instance
     *                 whose keys are also present in the given map. In other
     *                 words, returns a copy of the current map with all keys
     *                 removed that are not also in the other map.
     *
     * @template TKey2
     * @template TValue2
     * @psalm-param Map<TKey2, TValue2> $map
     * @psalm-return Map<TKey&TKey2, TValue>
     */
    public function intersect(Map $map): Map
    {
        return $this->filter(function($key) use ($map) {
            return $map->hasKey($key);
        });
    }

    /**
     * Returns the result of removing all keys from the current instance that
     * are present in a given map.
     *
     * @param Map $map The map containing the keys to exclude.
     *
     * @return Map The result of removing all keys from the current instance
     *                 that are present in a given map.
     *
     * @template TValue2
     * @psalm-param Map<TKey, TValue2> $map
     * @psalm-return Map<TKey, TValue>
     */
    public function diff(Map $map): Map
    {
        return $this->filter(function($key) use ($map) {
            return ! $map->hasKey($key);
        });
    }

    /**
     * Returns a hashed value suitable for using as an index in an array
     * (string or integer), trying to match the high-level behavior of the
     * get_hash function in the extension's ds_hashtable.c. Where the extension
     * uses the ZSTR_HASH macro to hash a string, we just use the string
     * itself as the hash value, trusting PHP's array to handle it
     * appropriately. Floats (doubles) are converted to an integer value by
     * interpreting their bits as those of a long long, rewriting -0.0 to 0.0,
     * as the extension does (but we don't xor the lower and upper 32 bits).
     *
     * See https://github.com/php-ds/ext-ds/blob/master/src/ds/ds_htable.c
     *
     * @param mixed $key Any value
     *
     * @return mixed The string or integer hash value.
     *
     * @psalm-return string|int
     */
    private function getHash($key)
    {
        if (is_object($key)) {
            if ($key instanceof Hashable) {
                // Use this same logic to hash whatever the hash() method gives
                // us because it's unconstrained.
                return $this->getHash($key->hash());
            } else {
                // Note that PHP aggressively reuses the hash values of
                // destroyed objects.
                return spl_object_hash($key);
            }
        } else if (is_string($key)) {
            return $key;
        } else if (is_array($key)) {
            // Take a hash of the serialized value to avoid PHP having to do a
            // long string comparison on each lookup (we already check the
            // actual array values for equality ourselves).
            return md5(serialize($key));
        } else if (is_float($key)) {
            if ($key === -0.0) {
                return 0;
            }
            /**
             * Mimic the extension's hack to treat a double's bits as an
             * unsigned long long.
             * @var false|array{int:int}
             */
            $data = unpack('Qint', pack('d', $key));
            return $data === false ? 0 : $data['int'];
        } else if (is_resource($key)) {
            return get_resource_id($key);
        }

        // We should only have valid scalar array indices left.
        return (int) $key;
    }

    /**
     * Determines whether two keys are equal.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @psalm-param TKey $a
     * @psalm-param TKey $b
     */
    private function keysAreEqual($a, $b): bool
    {
        if (is_object($a) && $a instanceof Hashable) {
            return is_object($b) && $a->equals($b);
        }

        return $a === $b;
    }

    /**
     * Attempts to look up a key in the table.
     *
     * @param $key
     * @param $hash The internal hash computed for the key.
     *
     * @psalm-param TKey $key
     * @psalm-param-out string|int $hash
     *
     * @return PairRef|null
     *
     * @psalm-return PairRef<TKey, TValue>|null
     */
    private function lookupKey($key, &$hash = null)
    {
        $hash = $this->getHash($key);
        if (!array_key_exists($hash, $this->table)) {
            return null;
        }

        $pairRefs = $this->table[$hash];
        foreach ($pairRefs as $pairRef) {
            if ($this->keysAreEqual($pairRef->pair->key, $key)) {
                return $pairRef;
            }
        }

        return null;
    }

    /**
     * Attempts to look up a value in the table.
     *
     * @param $value
     *
     * @return Pair|null
     *
     * @psalm-return Pair<TKey, TValue>|null
     */
    private function lookupValue($value)
    {
        foreach ($this->pairRefs as $pairRef) {
            $pair = $pairRef->pair;
            if ($pair->value === $value) {
                return $pair;
            }
        }
    }

    /**
     * Returns whether an association a given key exists.
     *
     * @param mixed $key
     *
     * @psalm-param TKey $key
     */
    public function hasKey($key): bool
    {
        return $this->lookupKey($key) !== null;
    }

    /**
     * Returns whether an association for a given value exists.
     *
     * @param mixed $value
     *
     * @psalm-param TValue $value
     */
    public function hasValue($value): bool
    {
        return $this->lookupValue($value) !== null;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->pairRefs);
    }

    /**
     * Returns a new map containing only the values for which a predicate
     * returns true. A boolean test will be used if a predicate is not provided.
     *
     * @param callable|null $callback Accepts a key and a value, and returns:
     *                                true : include the value,
     *                                false: skip the value.
     *
     * @return Map
     *
     * @psalm-param (callable(TKey, TValue): bool)|null $callback
     * @psalm-return Map<TKey, TValue>
     */
    public function filter(callable $callback = null): Map
    {
        $filtered = new self();

        foreach ($this as $key => $value) {
            if ($callback ? $callback($key, $value) : $value) {
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
     *
     * @template TDefault
     * @psalm-param TKey $key
     * @psalm-param TDefault $default
     * @psalm-return TValue|TDefault
     */
    public function get($key, $default = null)
    {
        if (($pairRef = $this->lookupKey($key))) {
            return $pairRef->pair->value;
        }

        // Check if a default was provided.
        if (func_num_args() === 1) {
            throw new OutOfBoundsException();
        }

        return $default;
    }

    /**
     * Returns a set of all the keys in the map.
     *
     * @return Set
     *
     * @psalm-return Set<TKey>
     */
    public function keys(): Set
    {
        $key = function($pairRef) {
            return $pairRef->pair->key;
        };

        return new Set(array_map($key, $this->pairRefs));
    }

    /**
     * Returns a new map using the results of applying a callback to each value.
     *
     * The keys will be equal in both maps.
     *
     * @param callable $callback Accepts two arguments: key and value, should
     *                           return what the updated value will be.
     *
     * @return Map
     *
     * @template TNewValue
     * @psalm-param callable(TKey, TValue): TNewValue $callback
     * @psalm-return Map<TKey, TNewValue>
     */
    public function map(callable $callback): Map
    {
        $mapped = new self();
        foreach ($this->pairRefs as $pairRef) {
            $pair = $pairRef->pair;
            $mapped->put($pair->key, $callback($pair->key, $pair->value));
        }

        return $mapped;
    }

    /**
     * Returns a sequence of pairs representing all associations.
     *
     * @return Sequence
     *
     * @psalm-return Sequence<Pair<TKey, TValue>>
     */
    public function pairs(): Sequence
    {
        $copyPair = function($pairRef) {
            return $pairRef->pair->copy();
        };

        return new Vector(array_map($copyPair, $this->pairRefs));
    }

    /**
     * Associates a key with a value, replacing a previous association if there
     * was one.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @psalm-param TKey $key
     * @psalm-param TValue $value
     */
    public function put($key, $value)
    {
        $pairRef = $this->lookupKey($key, $hash);

        if ($pairRef) {
            $pairRef->pair->value = $value;
        } else {
            $this->checkCapacity();
            $pair = new Pair($key, $value);
            $pairIndex = $this->nextPairIndex++;
            $pairRef = new PairRef($pairIndex, $pair);
            $this->pairRefs[$pairIndex] = $pairRef;

            if (!array_key_exists($hash, $this->table)) {
                $this->table[$hash] = [$pairRef];
            } else {
                $this->table[$hash][] = $pairRef;
            }
        }
    }

    /**
     * Creates associations for all keys and corresponding values of either an
     * array or iterable object.
     *
     * @param iterable<mixed, mixed> $values
     *
     * @psalm-param iterable<TKey, TValue> $values
     */
    public function putAll(iterable $values)
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
     *
     * @template TCarry
     * @psalm-param callable(TCarry, TKey, TValue): TCarry $callback
     * @psalm-param TCarry $initial
     * @psalm-return TCarry
     */
    public function reduce(callable $callback, $initial = null)
    {
        $carry = $initial;

        foreach ($this->pairRefs as $pairRef) {
            $pair = $pairRef->pair;
            $carry = $callback($carry, $pair->key, $pair->value);
        }

        return $carry;
    }

    /**
     * Completely removes a pair from the internal data structures by its
     * PairRef and hash.
     *
     * @param mixed $hash the internal hash for the pair
     *
     * @psalm-param int|string $hash
     *
     * @return mixed
     *
     * @psalm-return TValue
     */
    private function delete(PairRef $pairRef, $hash)
    {
        // Remove from ordered list. Note that holes in the list are fine;
        // order is preserved.
        unset($this->pairRefs[$pairRef->pairIndex]);

        // Remove from lookup table.
        $key = $pairRef->pair->key;
        foreach ($this->table[$hash] as $position => $hashedPairRef) {
            if ($this->keysAreEqual($hashedPairRef->pair->key, $key)) {
                array_splice($this->table[$hash], $position, 1);
                break;
            }
        }

        $this->checkCapacity();

        return $pairRef->pair->value;
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
     *
     * @template TDefault
     * @psalm-param TKey $key
     * @psalm-param TDefault $default
     * @psalm-return TValue|TDefault
     */
    public function remove($key, $default = null)
    {
        // lookupKey() stores the computed hash in $hash.
        $pairRef = $this->lookupKey($key, $hash);

        if ($pairRef) {
            return $this->delete($pairRef, $hash);
        }

        // Check if a default was provided
        if (func_num_args() === 1) {
            throw new \OutOfBoundsException();
        }

        return $default;
    }

    /**
     * Re-indexes the internal pairRefs array from 0 ascending, updates the
     * PairRef objects with their new indices, and resets the "next index"
     * counter to the next available array index.
     */
    private function compactPairRefs()
    {
       // Renumber indices from 0 ascending.
       $this->pairRefs = array_slice($this->pairRefs, 0);
       $this->nextPairIndex = count($this->pairRefs);

       $updateIndex = function(PairRef $pairRef, int $position): void {
          $pairRef->pairIndex = $position;
       };

       array_walk($this->pairRefs, $updateIndex);
    }

    /**
     * Reverses the map in-place
     */
    public function reverse()
    {
        $this->pairRefs = array_reverse($this->pairRefs);

        $this->compactPairRefs();
    }

    /**
     * Returns a reversed copy of the map.
     *
     * @return Map
     *
     * @psalm-return Map<TKey, TValue>
     */
    public function reversed(): Map
    {
        $reversed = new self();

        foreach (array_reverse($this->pairRefs) as $pairRef) {
            $pair = $pairRef->pair;
            $reversed->put($pair->key, $pair->value);
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
     *
     * @psalm-return Map<TKey, TValue>
     */
    public function slice(int $offset, int $length = null): Map
    {
        $map = new self();

        $pairRefs = array_slice($this->pairRefs, $offset, $length);

        foreach ($pairRefs as $pairRef) {
            $pair = $pairRef->pair;
            $map->put($pair->key, $pair->value);
        }

        return $map;
    }

    /**
     * Sorts the map in-place, based on an optional callable comparator.
     *
     * The map will be sorted by value.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *
     * @psalm-param (callable(TValue, TValue): int)|null $comparator
     */
    public function sort(callable $comparator = null)
    {
        if ($comparator) {
            usort($this->pairRefs, function($a, $b) use ($comparator) {
                return $comparator($a->pair->value, $b->pair->value);
            });
        } else {
            usort($this->pairRefs, function($a, $b) {
                return $a->pair->value <=> $b->pair->value;
            });
        }

        $this->compactPairRefs();
    }

    /**
     * Returns a sorted copy of the map, based on an optional callable
     * comparator. The map will be sorted by value.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *
     * @return Map
     *
     * @psalm-param (callable(TValue, TValue): int)|null $comparator
     * @psalm-return Map<TKey, TValue>
     */
    public function sorted(callable $comparator = null): Map
    {
        $copy = $this->copy();
        $copy->sort($comparator);
        return $copy;
    }

    /**
     * Sorts the map in-place, based on an optional callable comparator.
     *
     * The map will be sorted by key.
     *
     * @param callable|null $comparator Accepts two keys to be compared.
     *
     * @psalm-param (callable(TKey, TKey): int)|null $comparator
     */
    public function ksort(callable $comparator = null)
    {
        if ($comparator) {
            usort($this->pairRefs, function($a, $b) use ($comparator) {
                return $comparator($a->pair->key, $b->pair->key);
            });
        } else {
            usort($this->pairRefs, function($a, $b) {
                return $a->pair->key <=> $b->pair->key;
            });
        }

        $this->compactPairRefs();
    }

    /**
     * Returns a sorted copy of the map, based on an optional callable
     * comparator. The map will be sorted by key.
     *
     * @param callable|null $comparator Accepts two keys to be compared.
     *
     * @return Map
     *
     * @psalm-param (callable(TKey, TKey): int)|null $comparator
     * @psalm-return Map<TKey, TValue>
     */
    public function ksorted(callable $comparator = null): Map
    {
        $copy = $this->copy();
        $copy->ksort($comparator);
        return $copy;
    }

    /**
     * Returns the sum of all values in the map.
     *
     * @return int|float The sum of all the values in the map.
     */
    public function sum()
    {
        return $this->values()->sum();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Returns a sequence of all the associated values in the Map.
     *
     * @return Sequence
     *
     * @psalm-return Sequence<TValue>
     */
    public function values(): Sequence
    {
        $value = function($pairRef) {
            return $pairRef->pair->value;
        };

        return new Vector(array_map($value, $this->pairRefs));
    }

    /**
     * Creates a new map that contains the pairs of the current instance as well
     * as the pairs of another map.
     *
     * @param Map $map The other map, to combine with the current instance.
     *
     * @return Map A new map containing all the pairs of the current
     *                 instance as well as another map.
     *
     * @template TKey2
     * @template TValue2
     * @psalm-param Map<TKey2, TValue2> $map
     * @psalm-return Map<TKey|TKey2, TValue|TValue2>
     */
    public function union(Map $map): Map
    {
        return $this->merge($map);
    }

    /**
     * Creates a new map using keys of either the current instance or of another
     * map, but not of both.
     *
     * @param Map $map
     *
     * @return Map A new map containing keys in the current instance as well
     *                 as another map, but not in both.
     *
     * @template TKey2
     * @template TValue2
     * @psalm-param Map<TKey2, TValue2> $map
     * @psalm-return Map<TKey|TKey2, TValue|TValue2>
     */
    public function xor(Map $map): Map
    {
        return $this->merge($map)->filter(function($key) use ($map) {
            return $this->hasKey($key) ^ $map->hasKey($key);
        });
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        foreach ($this->pairRefs as $pairRef) {
            $pair = $pairRef->pair;
            yield $pair->key => $pair->value;
        }
    }

    /**
     * Returns a representation to be used for var_dump and print_r.
     *
     * @psalm-return array<Pair<TKey, TValue>>
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
        $pairRef = $this->lookupKey($offset);

        if ($pairRef) {
            return $pairRef->pair->value;
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

    /**
     * Returns a representation that can be natively converted to JSON, which is
     * called when invoking json_encode.
     *
     * @return mixed
     *
     * @see \JsonSerializable
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) $this->toArray();
    }
}

/**
 * A reference to a pair that keeps track of its position in a parallel ordered
 * array.
 *
 * @property int $pairIndex
 * @property Pair $pair
 *
 * @package Ds
 */
final class PairRef {
   /**
    * @var int
    */
   public $pairIndex;

   /**
    * @var Pair
    * @psalm-readonly
    */
   public $pair;

   public function __construct(int $pairIndex, Pair $pair)
   {
      $this->pairIndex = $pairIndex;
      $this->pair = $pair;
   }
}
