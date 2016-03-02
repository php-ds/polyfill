<?php
namespace Ds;

/**
 * PriorityNode
 *
 * @package Ds
 */
final class PriorityNode
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @var int
     */
    public $priority;

    /**
     * @var int
     */
    public $stamp;

    /**
     * @param mixed $value
     * @param int   $priority
     * @param int   $stamp
     */
    public function __construct($value, int $priority, int $stamp)
    {
        $this->value    = $value;
        $this->priority = $priority;
        $this->stamp    = $stamp;
    }
}
