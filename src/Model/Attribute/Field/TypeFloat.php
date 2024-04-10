<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

class TypeFloat extends BaseType
{
    public static function factory(?float $min = 0, ?float $max = 0xffffffff, bool $isNull = false): self
    {
        return new self(type: Type::FLOAT, min: $min, max: $max, isNull: $isNull);
    }

    public function cast(mixed $value): ?float
    {
        if ($value === null) {
            return $value;
        }
        return (float)$value;
    }

    public function decast(mixed $value): ?float
    {
        if ($value === null) {
            return $value;
        }
        return (float)$value;
    }
}
