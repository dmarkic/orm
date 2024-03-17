<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

class TypeString extends BaseType
{
    public function __construct(
        ?int $min = null,
        ?int $max = null,
        bool $isNull = false
    ) {
        parent::__construct(
            type: Type::STRING,
            min: $min,
            max: $max,
            isNull: $isNull
        );
    }

    public function cast(mixed $value): string
    {
        return (string)$value;
    }
}
