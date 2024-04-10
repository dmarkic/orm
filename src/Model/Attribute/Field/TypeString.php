<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use ValueError;

class TypeString extends BaseType
{
    public static function factory(?int $min = null, ?int $max = null, bool $isNull = false): self
    {
        return new self(type: Type::STRING, min: $min, max: $max, isNull: $isNull);
    }

    public function cast(mixed $value): ?string
    {
        if ($value === null) {
            return $value;
        }
        return (string)$value;
    }

    public function decast(mixed $value): ?string
    {
        if ($value === null) {
            return $value;
        }
        return (string)$value;
    }
}
