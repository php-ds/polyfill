<?php
namespace Ds;

/**
 * Interface for objects that can be used as keys in Ds\Map and Ds\Set.
 */
interface Key
{
    /**
     * Returns a scalar hash value for this object.
     */
    public function hash(): mixed;

    /**
     * Determines if this object is equal to another.
     */
    public function equals(mixed $other): bool;
}
