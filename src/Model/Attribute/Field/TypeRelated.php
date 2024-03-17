<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Model\Attribute\Field;

/**
 * Relation field
 */
class TypeRelated extends BaseType
{
    public function __construct(
        Field $field,
        bool $isNull = false
    ) {
        parent::__construct(
            type: Type::RELATED,
            isNull: $isNull,
            field: $field
        );
    }

    /**
     * No casting at the moment
     */
    public function cast(mixed $value): mixed
    {
        return $value;
    }
}
