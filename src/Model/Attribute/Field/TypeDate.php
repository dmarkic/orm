<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Factory;
use DateTimeInterface;
use DateTimeImmutable;
use ValueError;

class TypeDate extends TypeDatetime
{
    public static function factory(
        string $format = '',
        bool $isNull = false
    ): self {
        if (empty($format)) {
            $format = Factory::getDateTimeDateFormat();
        }
        return new self(
            type: Type::DATE,
            format: $format,
            isNull: $isNull
        );
    }
}
