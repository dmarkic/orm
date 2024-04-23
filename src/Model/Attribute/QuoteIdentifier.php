<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute as BaseAttribute;
use Attribute;

/**
 * Quote identifier
 *
 * When using database reserved words for column, we need to add this attribute to Field so
 * Orm will quote the identifier.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class QuoteIdentifier extends BaseAttribute
{
    public function __construct()
    {
    }
}
