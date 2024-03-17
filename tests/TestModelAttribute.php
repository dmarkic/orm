<?php

namespace Blrf\Tests\Orm;

use Blrf\Orm\Model;
use Blrf\Orm\Model\Attribute as Attr;
use Blrf\Orm\Model\Meta\Data as MetaData;

/**
 * Model used for testing
 *
 * This model uses attributes.
 */
#[Attr\Model]
class TestModelAttribute extends Model
{
}
