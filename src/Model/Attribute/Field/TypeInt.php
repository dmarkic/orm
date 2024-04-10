<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

class TypeInt extends BaseType
{
    public static function factory(?int $min = 0, ?int $max = 0xffffffff, bool $isNull = false): self
    {
        return new self(type: Type::INT, min: $min, max: $max, isNull: $isNull);
    }

    public function cast(mixed $value): ?int
    {
        if ($value === null) {
            return $value;
        }
        return (int)$value;
    }

    public function decast(mixed $value): ?int
    {
        if ($value === null) {
            return $value;
        }
        return (int)$value;
    }
}
