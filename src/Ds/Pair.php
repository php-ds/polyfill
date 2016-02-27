<?php
namespace Ds;

use OutOfBoundsException;

/**
 * A pair which represents a key, and an associated value.
 *
 * The key can be accessed using $p[0], $p['key'], or $p->key.
 * The value can be accessed using $p[1], $p['value'], or $p->value.
 */
final class Pair implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param mixed $key The pair's key
     */
    private $key;

    /**
     * @param mixed $value The pair's value
     */
    private $value;

    private $init = false;

    /**
     * Constructor
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @throws \Error
     */
    public function __construct($key, $value)
    {
        if ($this->init) {
            throw new \Error();
        }

        $this->key   = $key;
        $this->value = $value;
        $this->init  = true;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [$this->key, $this->value];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     *
     */
    public function offsetSet($offset, $value)
    {
        switch ($offset) {
            case 0:
            case 'key':
                $this->key = $value;
                break;
            case 1:
            case 'value':
                $this->value = $value;
                break;

            default:
                throw new \Error();
        }
    }

    /**
     * Offset Get
     */
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 0:
            case 'key':
                return $this->key;

            case 1:
            case 'value':
                return $this->value;

            default:
                throw new OutOfBoundsException();
        }
    }

    /**
     * Offset Unset
     */
    public function offsetUnset($offset)
    {
        throw new \Error();
    }

    /**
     * Offset Exists
     */
    public function offsetExists($offset)
    {
        switch ($offset) {
            case 0:
            case 'key':
                return isset($this->key);

            case 1:
            case 'value':
                return isset($this->value);

            default:
                return false;
        }
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Get
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'key':
                return $this->key;

            case 'value':
                return $this->value;

            default:
                throw new OutOfBoundsException();
        }
    }

    /**
     * To String
     */
    public function __toString()
    {
        return 'object(' . get_class($this) . ')';
    }
}
