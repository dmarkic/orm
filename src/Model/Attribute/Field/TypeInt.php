<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

class TypeInt extends BaseType
{
    public function __construct(
        ?int $min = 0,
        ?int $max = 0xffffffff,
        bool $isNull = false
    ) {
        parent::__construct(
            type: Type::INT,
            min: $min,
            max: $max,
            isNull: $isNull
        );
    }

    public function cast(mixed $value): int
    {
        return (int)$value;
    }
}
