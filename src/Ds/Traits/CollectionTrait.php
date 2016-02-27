<?php

namespace Ds\Traits;

/**
 * CollectionTrait
 *
 * @package Ds\Traits
 */
trait CollectionTrait
{
    /**
     * Clears all elements in the Collection.
     */
    public function clear()
    {
        $this->internal = [];
        $this->capacity = $this->defaultCapacity();
    }

    /**
     * Creates a copy of the collection, equivalent to 'clone'.
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * Returns the size of the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->internal);
    }

    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->internal) === 0;
    }

    /**
     * Json Serialize
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->internal;
    }

    /**
     * Returns an array representation of the collection.
     *
     * The format of the returned array is implementation-dependent.
     * Some implementations may throw an exception if an array representation
     * could not be created.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->internal;
    }
}