<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Model\Attribute\Field;
use ValueError;

/**
 * Relation field
 */
class TypeRelated extends BaseType
{
    /**
     * Factory
     *
     * Field is nullable, because isNull has default false value.
     */
    public static function factory(?Field $field = null, bool $isNull = false): self
    {
        if ($field === null) {
            throw new ValueError('Field must be set');
        }
        return new self(type: Type::RELATED, field: $field, isNull: $isNull);
    }
}
