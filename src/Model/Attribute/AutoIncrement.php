<?php

namespace Blrf\Orm\Model\Attribute;

use Attribute;

/**
 * Mark field as autoincrement
 *
 * This is an alias for `GeneratedValue`
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoIncrement extends GeneratedValue
{
}
