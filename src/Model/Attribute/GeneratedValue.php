<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute as BaseAttribute;

use Attribute;

/**
 * Mark field as generated
 *
 * This field will receive insertId
 *
 * There is only one strategy at the moment.
 * Later convert to GeneratedValue\Strategy enum type.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class GeneratedValue extends BaseAttribute
{
    public const IDENTITY = 'IDENTITY';

    public function __construct(
        public readonly string $strategy = self::IDENTITY
    ) {
    }
}
