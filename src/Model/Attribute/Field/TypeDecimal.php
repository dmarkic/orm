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
    public static function factory(
        int $precision = 12,
        int $scale = 2,
        ?float $min = 0,
        ?float $max = 0xffffffff,
        bool $isNull = false
    ): self {
        return new self(
            type: Type::DECIMAL,
            precision: $precision,
            scale: $scale,
            min: $min,
            max: $max,
            isNull: $isNull
        );
    }
}
