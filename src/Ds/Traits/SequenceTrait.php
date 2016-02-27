<?php

namespace Ds\Traits;

use \OutOfRangeException;
use \UnderflowException;

/**
 * SequenceTrait
 *
 * @package Ds\Traits
 */
trait SequenceTrait
{
    /**
     * @var int
     */
    private $capacity;

    /**
     * @var array
     */
    private $internal = [];

    /**
     * @inheritDoc
     */
    public function __construct($values = null)
    {
        $this->capacity = $this->defaultCapacity();
        if ($values) {
            if (is_integer($values)) {
                $this->allocate($values);
            } else {
                $this->pushAll($values);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function allocate(int $capacity)
    {
        $this->capacity = max($capacity, $this->capacity);
    }

    /**
     * @inheritDoc
     */
    public function capacity(): int
    {
        return $this->capacity;
    }

    /**
     * @inheritDoc
     */
    public function contains(...$values): bool
    {
        if (!$values) {
            return false;
        }

        foreach ($values as $value) {
            if (!in_array($value, $this->internal, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $callback = null): self
    {
        if ($callback) {
            return new self(array_filter($this->internal, $callback));
        }

        return new self(array_filter($this->internal));
    }

    /**
     * @inheritDoc
     */
    public function find($value)
    {
        return array_search($value, $this->internal, true);
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        if (empty($this->internal)) {
            throw new UnderflowException();
        }

        return $this->internal[0];
    }

    /**
     * @inheritDoc
     */
    public function get(int $index)
    {
        $this->checkRange($index);

        return $this->internal[$index];
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, ...$values)
    {
        if ($index < 0 || $index > count($this->internal)) {
            throw new OutOfRangeException();
        }

        array_splice($this->internal, $index, 0, $values);
    }

    /**
     * @inheritDoc
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->internal);
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
        if (empty($this->internal)) {
            throw new UnderflowException();
        }

        return end($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->internal));
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
        if (empty($this->internal)) {
            throw new UnderflowException();
        }

        $value = array_pop($this->internal);
        $this->checkCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function push(...$values)
    {
        if ( ! $values) {
            return;
        }

        $required = count($this->internal) + count($values);
        $this->checkCapacity($required);

        array_push($this->internal, ...$values);
    }

    /**
     * @inheritDoc
     */
    public function pushAll($values)
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new \Error();
        }

        $this->push(...$values);
    }

    /**
     * @inheritDoc
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->internal, $callback, $initial);
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index)
    {
        $this->checkRange($index);

        $value = array_splice($this->internal, $index, 1, null)[0];
        $this->checkCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->internal));

    }

    /**
     * @inheritDoc
     */
    public function rotate(int $rotations)
    {
        while ($rotations !== 0) {
            if ($rotations > 0) {
                $this->push($this->shift());
                $rotations--;
            } else {
                $this->unshift($this->pop());
                $rotations++;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function set(int $index, $value)
    {
        $this->checkRange($index);

        $this->internal[$index] = $value;
//        $this->rebase();
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_shift($this->internal);
        $this->checkCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): self
    {
        return new self(array_slice($this->internal, $offset, $length));
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null): self
    {
        $internal = $this->internal;

        if ($comparator) {
            usort($internal, $comparator);
        } else {
            sort($internal);
        }

        return new self($internal);
    }

    /**
     * @inheritDoc
     */
    public function unshift(...$values)
    {
        $this->internal = array_merge($values, $this->internal);
    }

    /**
     * Check Range
     *
     * @param int $index
     */
    private function checkRange(int $index)
    {
        if ($index < 0 || $index >= count($this->internal)) {
            throw new OutOfRangeException();
        }
    }

    /**
     * Default Capacity
     *
     * @return int
     */
    private function defaultCapacity(): int
    {
        return 0;
    }

    /**
     * Check Capacity
     *
     * @param int|null $required
     */
    private function checkCapacity(int $required = null) {}
}