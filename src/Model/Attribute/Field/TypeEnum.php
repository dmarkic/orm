<?php

// strict_types=1 removed as value is int or string
//declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use BackedEnum;
use ValueError;

class TypeEnum extends BaseType
{
    /** @param string[]|BackedEnum[] $options */
    public static function factory(?array $options = null, bool $isNull = false): self
    {
        if (empty($options)) {
            throw new ValueError('Options cannot be empty');
        }
        return new self(type: Type::ENUM, options: $options, isNull: $isNull);
    }

    /**
     * @throws ValueError if BackedEnum value is not valid
     */
    public function cast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (!empty($this->options)) {
            $test = $this->options[key($this->options)] ?? null;
            if ($test !== null) {
                if ($test instanceof BackedEnum) {
                    return $test::from($value);
                }
            }
        }
        return $value;
    }

    public function decast(mixed $value): mixed
    {
        if ($value === null) {
            return $value;
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        return $value;
    }
}
