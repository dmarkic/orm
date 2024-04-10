<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Relation;
use ValueError;

#[CoversClass(Attribute::class)]
#[CoversClass(Relation::class)]
class RelationTest extends TestCase
{
    public function testRelationInvalidType()
    {
        $this->expectException(ValueError::class);
        $relation = new Relation('INVALID', 'model', 'field');
    }

    public function testRelation()
    {
        $field = $this->createMock(Field::class);
        $relation = new Relation('ONETOONE', 'model', 'field');
        $this->assertEquals(Relation\Type::ONETOONE, $relation->type);
        $this->assertEquals('model', $relation->model);
        $this->assertEquals('field', $relation->field);
        $relation->setField($field);
        $this->assertSame($field, $relation->getField());
    }

    public function testJsonSerialize()
    {
        $exp = '{"attrName":"Blrf\\\\Orm\\\\Model\\\\Attribute\\\\Relation",' .
               '"type":"ONETOONE","model":"model","field":"field","rfield":null,"alias":""}';
        $relation = new Relation('ONETOONE', 'model', 'field');
        $this->assertSame($exp, json_encode($relation));
    }
}
