<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute as BaseAttribute;
use Attribute;

/**
 * Mark object as Model
 *
 * This or Source attribute is required to enable Attribute metadata driver.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Model extends BaseAttribute
{
}
