<?php
namespace Ds;

/**
 * Create a new Seq from an iterable.
 */
function seq(iterable $values = []): Seq
{
    return new Seq($values);
}

/**
 * Create a new Map from an iterable of key => value pairs.
 */
function map(iterable $values = []): Map
{
    return new Map($values);
}

/**
 * Create a new Set from an iterable.
 */
function set(iterable $values = []): Set
{
    return new Set($values);
}

/**
 * Create a new Heap from an iterable with optional comparator.
 */
function heap(iterable $values = [], ?callable $comparator = null): Heap
{
    return new Heap($values, $comparator);
}
