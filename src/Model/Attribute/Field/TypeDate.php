<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;
use DateTimeInterface;
use DateTimeImmutable;
use ValueError;

class TypeDate extends TypeDatetime
{
    public function __construct(
        string $format = '!Y-m-d',
        bool $isNull = false,
        string $datetimeClass = DateTimeImmutable::class
    ) {
        parent::__construct(
            type: Type::DATE,
            format: $format,
            isNull: $isNull,
            datetimeClass: $datetimeClass
        );
    }
}
