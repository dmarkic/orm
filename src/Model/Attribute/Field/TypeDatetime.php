<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;
use DateTimeInterface;
use DateTimeImmutable;
use ValueError;

class TypeDatetime extends BaseType
{
    public function __construct(
        string $format = 'Y-m-d H:i:s',
        bool $isNull = false,
        Type $type = Type::DATETIME,
        public readonly string $datetimeClass = DateTimeImmutable::class
    ) {
        parent::__construct(
            type: Type::DATETIME,
            format: $format,
            isNull: $isNull
        );
    }

    public function cast(mixed $value): \DateTimeInterface
    {
        if ($value instanceof DateTimeInterfac) {
            return $value;
        }
        $ret = $this->datetimeClass::createFromFormat($this->format, $value);
        if ($ret === false) {
            throw new ValueError(
                'Value: ' . $value . ' cannot be converted to date-time ' .
                'from format: ' . $this->format
            );
        }
        return $ret;
    }

    public function decast(mixed $value): string
    {
        return $value->format(ltrim($this->format, '!'));
    }
}
