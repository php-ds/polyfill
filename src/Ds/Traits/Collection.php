<?php
namespace Ds\Traits;

/**
 * Collection
 *
 * @package Ds\Traits
 */
trait Collection
{
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
        return count($this) === 0;
    }

    /**
     * Json Serialize
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Creates a copy of the collection, equivalent to 'clone'.
     *
     * @return $this
     */
    public function copy()
    {
        return new self($this);
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
    abstract public function toArray(): array;

    /**
     * Debug Info
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     * To String
     */
    public function __toString()
    {
        return 'object(' . get_class($this) . ')';
    }
}
