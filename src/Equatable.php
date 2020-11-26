<?php
namespace Ds;

/**
 * Equatable is an interface which allows objects to compare to each other for
 * equality.
 *
 * @package Ds
 */
interface Equatable
{

    /**
     * Determines if two objects should be considered equal. Both objects will
     * be instances of the same class but may not be the same instance.
     *
     * @param $obj An instance of the same class to compare to.
     *
     * @return bool
     */
    function equals($obj): bool;
}
