<?php
namespace Ds;

use \OutOfBoundsException;

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
        if (is_null($this->key) === false) {
            throw new \Error();
        }

        $this->key = $key;
        $this->value = $value;
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
        if ($offset === 0 || $offset === 'key') {
            $target = 'key';
        } elseif ($offset === 1 || $offset === 'value') {
            $target = 'value';
        }

        if (!is_null($this->$target)) {
            throw new \Error();
        }

        $this->$target = $value;
    }

    /**
     * Offset Get
     */
    public function offsetGet($offset)
    {
        if ($offset === 0 || $offset === 'key') {
            return $this->key;
        }

        if ($offset === 1 || $offset === 'value') {
            return $this->value;
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
        if ($offset === 0 || $offset === 'key') {
            return !is_null($this->key);
        }

        if ($offset === 1 || $offset === 'value') {
            return !is_null($this->value);
        }

        return false;
    }

    public function __isset($name)
    {
        try {
            return $this->__get($name) ? true : false;
        } catch (OutOfBoundsException $e) {
            return false;
        }
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
        if ($name === 'key') {
            return $this->key;
        } elseif ($name ==='value') {
            return $this->value;
        }

        throw new OutOfBoundsException();
    }

    /**
     * To String
     */
    public function __toString()
    {
        return 'object(' . get_class($this) . ')';
    }
}
