<?php

namespace Blrf\Tests\Orm;

use Blrf\Orm\Model;
use Blrf\Orm\Model\Meta\Data as MetaData;

/**
 * Model used for testing
 *
 * This model implements ormMetaData() method.
 */
class TestModel extends Model
{
    public static function ormMetaData(): MetaData
    {
        $data = new MetaData();
        return $data;
    }
}
