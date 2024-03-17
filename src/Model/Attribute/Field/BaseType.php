<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Model\Attribute\Field;
use Stringable;

abstract class BaseType implements Stringable
{
    /**
     * Construct from array
     *
     * @see Type::fromArray()
     */
    public static function fromArray(array $data): static
    {
        if (isset($data['type'])) {
            unset($data['type']);
        }
        return new static(...$data);
    }

    /**
     * Construct from string
     *
     * @see Type::fromString()
     */
    public static function fromString(array $arguments): static
    {
        return new static(...$arguments);
    }

    public function __construct(
        public readonly Type $type,
        public readonly int|float|null $min = null,
        public readonly int|float|null $max = null,
        public readonly int|null $precision = null,
        public readonly int|null $scale = null,
        public readonly ?string $format = null,
        public readonly bool $isNull = false,
        public readonly ?Field $field = null
    ) {
    }

    public function __toString()
    {
        return $this->type->value;
    }

    /**
     * Cast value
     */
    abstract public function cast(mixed $value): mixed;

    public function decast(mixed $value): mixed
    {
        return $value;
    }
}
