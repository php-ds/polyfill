<?php
namespace Ds;

/**
 * Hashable is an interface which allows objects to be used as keys.
 *
 * It’s an alternative to spl_object_hash(), which determines an object’s hash
 * based on its handle: this means that two objects that are considered equal
 * by an implicit definition would not treated as equal because they are not
 * the same instance.
 *
 * @package Ds
 */
interface Hashable extends Equatable
{
    /**
     * Produces a scalar value to be used as the object's hash, which determines
     * where it goes in the hash table. While this value does not have to be
     * unique, objects which are equal must have the same hash value.
     *
     * @return mixed
     */
    function hash();
}
