<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Model\Attribute\Field;
use BackedEnum;
use Stringable;

abstract class BaseType implements Stringable
{
    abstract public static function factory(): self;

    /** @param string[]|BackedEnum[] $options */
    public function __construct(
        public readonly Type $type,
        /**
         * min-max for strings and ints
         */
        public readonly int|float|null $min = null,
        public readonly int|float|null $max = null,
        /**
         * precision and scale for decimal
         */
        public readonly int|null $precision = null,
        public readonly int|null $scale = null,
        /**
         * is null for all types
         */
        public readonly bool $isNull = false,
        /**
         * options for enums
         */
        public readonly array|null $options = null,
        /**
         * format for date
         */
        public readonly ?string $format = null,
        /**
         * field for relations
         */
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
    public function cast(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Decast value to database value
     */
    public function decast(mixed $value): mixed
    {
        return $value;
    }
}
