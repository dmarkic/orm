<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

class TypeFloat extends BaseType
{
    public function __construct(
        ?float $min = 0,
        ?float $max = 0xffffffff,
        bool $isNull = false
    ) {
        parent::__construct(
            type: Type::FLOAT,
            min: $min,
            max: $max,
            isNull: $isNull
        );
    }

    public function cast(mixed $value): float
    {
        return (float)$value;
    }
}
