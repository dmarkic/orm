<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Tests\Orm\TestModel;
use Blrf\Orm\Factory;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Hydrator;
use Blrf\Orm\Model\RelatedProxyInterface;
use Blrf\Orm\Model\Attribute;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Field\TypeInt;
use Blrf\Orm\Model\Attribute\Relation;
use Blrf\Orm\Model\Attribute\AutoIncrement;
use Blrf\Orm\Model\Attribute\GeneratedValue;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(Attribute::class)]
#[CoversClass(Field::class)]
class FieldTest extends TestCase
{
    public function testConstructWithFieldTypeObject()
    {
        $type = TypeInt::factory();
        $field = new Field('test', $type);
        $this->assertSame($type, $field->type);
    }

    public function testConstructWithFieldTypeString()
    {
        $field = new Field('test', 'int');
        $this->assertInstanceOf(TypeInt::class, $field->type);
    }

    public function testConstructWithFieldTypeArray()
    {
        $field = new Field('test', ['type' => 'int']);
        $this->assertInstanceOf(TypeInt::class, $field->type);
    }

    public function testToStringWillReturnColumnName()
    {
        $field = new Field('test', 'int', 'column');
        $this->assertSame('column', (string)$field);
    }

    public function testConstructWithoutAttributes()
    {
        $field = new Field('test', 'int', '');
        $this->assertEquals('test', $field->column);
        $this->assertNull($field->getRelation());
        $this->assertFalse($field->isGeneratedValue());
        $this->assertEquals('test', (string)$field);
    }
    public function testConstructWithAttributes()
    {
        $relationAttr = new Relation(Relation\Type::ONETOONE, 'model', 'field');
        $generatedValueAttr = new AutoIncrement();
        $field = new Field('test', 'int', null, $relationAttr, $generatedValueAttr);
        $this->assertSame($relationAttr, $field->getRelation());
        $this->assertTrue($field->isGeneratedValue());
    }

    public function testCastWithNullValue()
    {
        $this->expectException(ValueError::class);
        $field = new Field('test', 'int');
        $ret = $field->cast(null);
    }

    public function testCastWithNullValueTypeAllowsNull()
    {
        $field = new Field('test', TypeInt::factory(0, 0xff, true));
        $this->assertNull($field->cast(null));
    }

    public function testCastWithNullValueWithGeneratedValue()
    {
        $gvAttr = new GeneratedValue();
        $field = new Field('test', 'int', null, $gvAttr);
        $this->assertNull($field->cast(null));
    }

    public function testCastWithRelationValueIsModel()
    {
        $relationAttr = new Relation(Relation\Type::ONETOONE, 'model', 'field');
        $type = $this->createMock(TypeInt::class);
        $type->expects($this->never())->method('cast');
        $field = new Field('test', $type, null, $relationAttr);
        $model = new TestModel();
        $ret = $field->cast($model);
        $this->assertSame($ret, $model);
    }

    public function testCastWithRelationValueIsObjectThrowsValueErrorException()
    {
        $this->expectException(ValueError::class);
        $relationAttr = new Relation(Relation\Type::ONETOONE, 'model', 'field');
        $type = $this->createMock(TypeInt::class);
        $type->expects($this->never())->method('cast');
        $field = new Field('test', $type, null, $relationAttr);
        $ret = $field->cast(new \StdClass());
    }

    public function testCastWithRelationValueNotModelWillCallModelManagerGetRelatedProxy()
    {
        $value = 2;
        $relationAttr = new Relation(Relation\Type::ONETOONE, TestModel::class, 'field');
        $relationProxy = $this->createStub(RelatedProxyInterface::class);
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects($this->once())
            ->method('getRelatedProxy')
            ->with($relationAttr, $value)
            ->willReturn($relationProxy);
        $container = new \Blrf\Orm\Container();
        $container->set('blrf.orm.manager', $manager);
        Factory::setContainer($container);
        $type = $this->createMock(TypeInt::class);
        $type->expects($this->once())->method('cast')->with($value)->willReturn($value);
        $field = new Field('test', $type, null, $relationAttr);
        $ret = $field->cast(2);
        $this->assertInstanceOf(RelatedProxyInterface::class, $ret);
        Factory::setContainer(null);
    }

    public function testCastWithRelationOneToMany()
    {
        $relationAttr = new Relation(Relation\Type::ONETOMANY, 'model', 'field');
        $type = $this->createMock(TypeInt::class);
        $type->expects($this->once())->method('cast');
        $field = new Field('test', $type, null, $relationAttr);
        $value = 'anything';
        $field->cast($value);
    }

    public function testNormalCast()
    {
        $value = 2;
        $type = $this->createMock(TypeInt::class);
        $type->expects($this->once())->method('cast')->with($value)->willReturn($value);
        $field = new Field('test', $type);
        $this->assertSame($value, $field->cast($value));
    }

    public function testDecastWithoutRelation()
    {
        $field = new Field('name', 'int');
        $this->assertSame(2, $field->decast('2'));
    }

    public function testDecastWithRelationAndProxy()
    {
        $relation = $this->createMock(Relation::class);
        $field = new Field('name', 'int', 'name', $relation);
        $proxy = $this->createMock(RelatedProxyInterface::class);
        $proxy->expects($this->once())->method('getOrmProxyValue')->willReturn('2');
        $this->assertSame(2, $field->decast($proxy));
    }

    public function testDecastWithRelationAndModel()
    {
        $relField = new Field('rel', 'int');
        $relation = $this->createMock(Relation::class);
        $relation->expects($this->once())->method('getField')->willReturn($relField);
        $field = new Field('name', 'int', 'name', $relation);

        $model = $this->createStub(Model::class);

        $hydrator = $this->createMock(Hydrator::class);
        $hydrator->expects($this->once())->method('getFieldValue')->with($model, $relField)->willReturn('2');

        $manager = $this->createMock(Manager::class);
        $manager
            ->expects($this->once())
            ->method('getHydrator')
            ->with($model::class)
            ->willReturn($hydrator);
        $container = new \Blrf\Orm\Container();
        $container->set('blrf.orm.manager', $manager);
        Factory::setContainer($container);

        $this->assertSame(2, $field->decast($model));

        Factory::setContainer(null);
    }

    public function testJsonSerialize()
    {
        $relation = $this->createMock(Relation::class);
        $field = new Field('name', 'int', 'name', $relation);
        $exp = '{"attrName":"Blrf\\\\Orm\\\\Model\\\\Attribute\\\\Field"' .
               ',"name":"name","type":{"type":"int","min":0,"max":4294967295,' .
               '"precision":null,"scale":null,"isNull":false,"options":null,' .
               '"format":null,"field":null},"attributes":[null],"column":"name",' .
               '"relation":null,"generatedValue":null,"quoteIdentifier":false}';
        $this->assertSame($exp, json_encode($field));
    }
}
