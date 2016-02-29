<?php

/**
 * ds
 */
class ds
{
    /**
     * @param array|\Traversable $values
     *
     * @return Ds\Vector
     */
    public static function vector($values = null): Ds\Vector
    {
        return new Ds\Vector($values);
    }

    /**
     * @param array|\Traversable $values
     *
     * @return Ds\Deque
     */
    public static function deque($values = null): Ds\Deque
    {
        return new Ds\Deque($values);
    }

    /**
     * @param array|\Traversable $values
     *
     * @return Ds\Set
     */
    public static function set($values = null): Ds\Set
    {
        return new Ds\Set($values);
    }

    /**
     * @param array|\Traversable $values
     *
     * @return Ds\Map
     */
    public static function map($values = null): Ds\Map
    {
        return new Ds\Map($values);
    }

    /**
     * @param array|\Traversable $values
     *
     * @return Ds\Stack
     */
    public static function stack($values = null): Ds\Stack
    {
        return new Ds\Stack($values);
    }

    /**
     * @param array|\Traversable $values
     *
     * @return Ds\Queue
     */
    public static function queue($values = null): Ds\Queue
    {
        return new Ds\Queue($values);
    }

    /**
     * @return Ds\PriorityQueue
     */
    public static function priority_queue(): Ds\PriorityQueue
    {
        return new Ds\PriorityQueue();
    }

    /**
     * @param mixed|null $key
     * @param mixed|null $value
     *
     * @return Ds\Pair
     */
    public static function pair($key = null, $value = null): Ds\Pair
    {
        return new Ds\Pair($key, $value);
    }
}
