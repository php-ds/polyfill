<?php
namespace Ds;

use ArrayAccess;
use IteratorAggregate;

/**
 * @package Ds
 */
final class Tuple implements IteratorAggregate, ArrayAccess, Collection
{
    use Traits\Collection;

    /**
     *
     */
    private $array;

    /**
     *
     */
    public function __construct($values)
    {
        $this->array = is_array($values) ? $values : iterator_to_array($values);
    }

    /**
     *
     */
    public function toArray(): array
    {
        return $this->array;
    }

    /**
     *
     */
    public function count(): int
    {
        return count($this->array);
    }

    /**
     *
     */
    public function clear()
    {
        // Can't clear a tuple.
        // Or set everything to null?
        $this->array = array_fill(0, $this->count(), null);
    }

    /**
     *
     */
    public function get(int $index)
    {
        return $this->offsetGet($index);
    }

    /**
     *
     */
    public function set(int $index, $value)
    {
        return $this->offsetSet($index, $value);
    }

    /**
     * @inheritDoc
     */
    public function &offsetGet($offset)
    {
        return $this->array[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        return $this->array[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return is_integer($offset)
            && $offset >= 0
            && $offset < $this->count();
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }
}
