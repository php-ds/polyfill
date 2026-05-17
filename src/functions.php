<?php
namespace Ds;

if (!function_exists('Ds\\seq')) {
    /**
     * Create a new Seq from an iterable.
     */
    function seq(iterable $values = []): Seq
    {
        return new Seq($values);
    }
}

if (!function_exists('Ds\\map')) {
    /**
     * Create a new Map from an iterable of key => value pairs.
     */
    function map(iterable $values = []): Map
    {
        return new Map($values);
    }
}

if (!function_exists('Ds\\set')) {
    /**
     * Create a new Set from an iterable.
     */
    function set(iterable $values = []): Set
    {
        return new Set($values);
    }
}

if (!function_exists('Ds\\heap')) {
    /**
     * Create a new Heap from an iterable with optional comparator.
     */
    function heap(iterable $values = [], ?callable $comparator = null): Heap
    {
        return new Heap($values, $comparator);
    }
}
