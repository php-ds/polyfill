<?php
namespace Ds;

use UnderflowException;

final class PriorityNode
{
    public $value;
    public $priority;
    public $stamp;

    public function __construct($value, $priority, $stamp)
    {
        $this->value    = $value;
        $this->priority = $priority;
        $this->stamp    = $stamp;
    }
}

final class PriorityQueue implements \IteratorAggregate, Collection
{
    use Traits\Collection;
    use Traits\SquaredCapacity;

    const MIN_CAPACITY = 8;

    /**
     *
     */
    private $heap;

    /**
     *
     */
    private $stamp;

    /**
     * Initializes a new priority queue.
     */
    public function __construct()
    {
        $this->heap  = [];
        $this->stamp = 0;
    }

    /**
     *
     */
    protected function increaseCapacity()
    {
        $this->capacity = max(
            $this->square($this->capacity),
            $this->square(count($this))
        );
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->heap = [];
        $this->stamp = 0;
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * @inheritDoc
     */
    public function copy()
    {
        $copy = new PriorityQueue();
        $copy->heap = $this->heap;
        $copy->capacity = $this->capacity;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->heap);
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return count($this->heap) === 0;
    }

    /**
     *
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Returns the value with the highest priority in the priority queue.
     *
     * @return mixed
     *
     * @throw UnderflowException
     */
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->heap[0]->value;
    }

    private function left(int $index): int
    {
        return ($index * 2) + 1;
    }

    private function right(int $index): int
    {
        return ($index * 2) + 2;
    }

    private function parent(int $index): int
    {
        return ($index - 1) / 2;
    }

    private function compare(int $a, int $b)
    {
        $x = $this->heap[$a];
        $y = $this->heap[$b];

        //
        return ($x->priority <=> $y->priority) ?: ($y->stamp <=> $x->stamp);
    }

    private function swap(int $a, int $b)
    {
        $node = $this->heap[$a];
        $this->heap[$a] = $this->heap[$b];
        $this->heap[$b] = $node;
    }

    /**
     * Returns and removes the value with the highest priority in the queue.
     *
     * @return mixed
     */
    public function pop()
    {
        $value = $this->peek();

        $last = array_pop($this->heap);

        $size = count($this->heap);

        if ($size === 0) {
            return $value;
        }

        $this->heap[0] = $last;

        $node = 0;

        while ($node < floor($size / 2)) {

            $left  = $this->left($node);
            $right = $this->right($node);

            // Determine which leaf is the largest
            if ($right < $size && $this->compare($right, $left) > 0) {
                $leaf = $right;
            } else {
                $leaf = $left;
            }

            // Check if we should swap the leaf with the current node
            if ($this->compare($leaf, $node) < 0) {
                break;
            }

            $this->swap($leaf, $node);
            $node = $leaf;
        }

        $this->adjustCapacity();
        return $value;
    }

    /**
     * Pushes a value into the queue, with a specified priority.
     *
     * @param mixed $value
     * @param int   $priority
     */
    public function push($value, int $priority)
    {
        $leaf = count($this->heap);

        $this->heap[] = new PriorityNode($value, $priority, $this->stamp++);

        while ($leaf > 0) {
            $parent = $this->parent($leaf);

            if ($this->compare($leaf, $parent) < 0) {
                break;
            }

            $this->swap($parent, $leaf);
            $leaf = $parent;
        }

        $this->adjustCapacity();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $heap = $this->heap;
        $array = [];

        while ( ! $this->isEmpty()) {
            $array[] = $this->pop();
        }

        $this->heap = $heap;
        return $array;
    }

    /**
     *
     */
    public function getIterator()
    {
        while ( ! $this->isEmpty()) {
            yield $this->pop();
        }
    }
}
