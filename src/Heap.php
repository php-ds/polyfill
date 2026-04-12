<?php
namespace Ds;

use UnderflowException;

/**
 * A Heap is a tree-based structure that always yields the largest value
 * (max-heap by default). Accepts an optional callable comparator.
 *
 * Iterator is destructive: pops values from a clone.
 */
final class Heap implements \IteratorAggregate, \Countable, \JsonSerializable
{
    use Traits\GenericCollection;
    use Traits\SquaredCapacity;

    public const MIN_CAPACITY = 8;

    private array $nodes = [];
    private mixed $comparator;

    public function __construct(iterable $values = [], ?callable $comparator = null)
    {
        $this->comparator = $comparator;

        foreach ($values as $value) {
            $this->push($value);
        }
    }

    // -- Heap operations -----------------------------------------------------

    public function push(mixed ...$values): void
    {
        foreach ($values as $value) {
            $this->nodes[] = $value;
            $this->siftUp(count($this->nodes) - 1);
            $this->checkCapacity();
        }
    }

    public function pop(): mixed
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $root = $this->nodes[0];
        $last = array_pop($this->nodes);

        if (count($this->nodes) > 0) {
            $this->nodes[0] = $last;
            $this->siftDown(0);
        }

        $this->checkCapacity();
        return $root;
    }

    public function peek(): mixed
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }
        return $this->nodes[0];
    }

    public function clear(): void
    {
        $this->nodes = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    // -- Collection ----------------------------------------------------------

    public function count(): int
    {
        return count($this->nodes);
    }

    public function copy(): self
    {
        $copy = new self([], $this->comparator);
        $copy->nodes = $this->nodes;
        $copy->capacity = $this->capacity;
        return $copy;
    }

    public function toArray(): array
    {
        $clone = clone $this;
        $array = [];
        while ($clone->count() > 0) {
            $array[] = $clone->pop();
        }
        return $array;
    }

    // -- Iteration (destructive) ---------------------------------------------

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        $clone = clone $this;
        while ($clone->count() > 0) {
            yield $clone->pop();
        }
    }

    // -- Serialization -------------------------------------------------------

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        $this->nodes = [];
        $this->comparator = null;
        foreach ($data as $value) {
            $this->push($value);
        }
    }

    public function __clone()
    {
        // Shallow clone of nodes array is sufficient.
        // The comparator reference is shared, which is correct.
    }

    // -- Internal heap logic -------------------------------------------------

    private function compare(mixed $a, mixed $b): int
    {
        if ($this->comparator) {
            return ($this->comparator)($a, $b);
        }
        return $a <=> $b;
    }

    private function siftUp(int $index): void
    {
        while ($index > 0) {
            $parent = intdiv($index - 1, 2);
            if ($this->compare($this->nodes[$index], $this->nodes[$parent]) <= 0) {
                break;
            }
            $this->swap($index, $parent);
            $index = $parent;
        }
    }

    private function siftDown(int $index): void
    {
        $size = count($this->nodes);
        $half = intdiv($size, 2);

        while ($index < $half) {
            $left  = 2 * $index + 1;
            $right = $left + 1;
            $swap  = $left;

            if ($right < $size && $this->compare($this->nodes[$left], $this->nodes[$right]) < 0) {
                $swap = $right;
            }

            if ($this->compare($this->nodes[$index], $this->nodes[$swap]) >= 0) {
                break;
            }

            $this->swap($index, $swap);
            $index = $swap;
        }
    }

    private function swap(int $a, int $b): void
    {
        $temp = $this->nodes[$a];
        $this->nodes[$a] = $this->nodes[$b];
        $this->nodes[$b] = $temp;
    }
}
