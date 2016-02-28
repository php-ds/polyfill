<?php
namespace Ds\Traits;

/**
 *
 */
trait SquaredCapacity
{
    /**
     *
     */
    private function square(int $capacity): int
    {
        return pow(2, ceil(log($capacity, 2)));
    }
}
