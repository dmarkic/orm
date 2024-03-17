<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

/**
 * DECIMAL type
 *
 * Currently, no casting is performed.
 *
 * Uses $precision and $scale.
 * @see https://dev.mysql.com/doc/refman/8.0/en/precision-math-decimal-characteristics.html
 */
class TypeDecimal extends BaseType
{
    public function __construct(
        int $precision = 12,
        int $scale = 2,
        ?float $min = 0,
        ?float $max = 0xffffffff,
        bool $isNull = false
    ) {
        parent::__construct(
            type: Type::DECIMAL,
            precision: $precision,
            scale: $scale,
            min: $min,
            max: $max,
            isNull: $isNull
        );
    }

    /**
     * No casting is performed
     */
    public function cast(mixed $value): mixed
    {
        return $value;
    }
}
