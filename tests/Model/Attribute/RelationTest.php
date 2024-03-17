<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Relation;
use ValueError;

class RelationTest extends TestCase
{
    public function testRelationInvalidType()
    {
        $this->expectException(ValueError::class);
        $relation = new Relation('INVALID', 'model', 'field');
    }

    public function testRelation()
    {
        $relation = new Relation('ONETOONE', 'model', 'field');
        $this->assertEquals(Relation\Type::ONETOONE, $relation->type);
        $this->assertEquals('model', $relation->model);
        $this->assertEquals('field', $relation->field);
    }
}
