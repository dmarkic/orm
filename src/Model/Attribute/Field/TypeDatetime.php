<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Factory;
use DateTimeInterface;
use DateTimeImmutable;
use ValueError;

class TypeDatetime extends BaseType
{
    public static function factory(
        string $format = '',
        bool $isNull = false
    ): self {
        if (empty($format)) {
            $format = Factory::getDateTimeFormat();
        }
        return new self(
            type: Type::DATETIME,
            format: $format,
            isNull: $isNull
        );
    }

    public function cast(mixed $value): ?DateTimeInterface
    {
        if ($value === null) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value;
        }
        $dtClass = Factory::getDateTimeClass();
        $ret = $dtClass::createFromFormat($this->format, $value);
        if ($ret === false) {
            throw new ValueError(
                'Value: ' . $value . ' cannot be converted to date-time ' .
                'from format: ' . $this->format
            );
        }
        return $ret;
    }

    public function decast(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value->format(ltrim($this->format, '!'));
    }
}
