<?php
namespace Ds;

/**
 * A readonly pair representing a key and an associated value.
 */
final readonly class Pair implements \JsonSerializable
{
    public function __construct(
        public mixed $key = null,
        public mixed $value = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key'   => $this->key,
            'value' => $this->value,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        // readonly properties can only be set in constructor,
        // but __unserialize is called after construction.
        // Use reflection to bypass readonly.
        $ref = new \ReflectionClass($this);
        $ref->getProperty('key')->setValue($this, $data['key']);
        $ref->getProperty('value')->setValue($this, $data['value']);
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return 'object(' . get_class($this) . ')';
    }
}
