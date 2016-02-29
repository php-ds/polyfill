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
     * @return Collection
     */
    public function copy()
    {
        return new self($this);
    }

    /**
     *
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     *
     */
    public function __toString()
    {
        return 'object(' . get_class($this) . ')';
    }
}
