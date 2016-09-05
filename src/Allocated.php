<?php
namespace Ds;

interface Allocated
{
    /**
     *
     */
    public function allocate(int $capacity);

    /**
     * Returns the current capacity.
     *
     * @return int
     */
    public function capacity(): int;
}
