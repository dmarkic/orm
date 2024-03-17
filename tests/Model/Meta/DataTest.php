<?php

namespace Blrf\Tests\Orm\Model\Meta;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Meta\Data;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\GeneratedValue;
use Blrf\Orm\Model\Attribute\Index;
use Blrf\Orm\Model\Attribute\Relation;
use Blrf\Orm\Model\Attribute\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(Data::class)]
class DataTest extends TestCase
{
    public function testSetSourceWithString()
    {
        $d = new Data($this->getMockedMeta());
        $json = json_encode($d);
        $this->assertSame('{"source":null,"fields":[],"indexes":[]}', $json);
        $this->assertFalse($d->hasSource());
        $d->setSource('name', 'schema');
        $this->assertTrue($d->hasSource());
        $this->assertSame('schema.name', (string)$d->getSource());
        $this->assertEmpty($d->getFields());
    }

    public function testSetSourceWithSource()
    {
        $s = new Source('name');
        $d = new Data($this->getMockedMeta());
        $this->assertFalse($d->hasSource());
        $d->setSource($s);
        $this->assertTrue($d->hasSource());
        $this->assertSame('name', (string)$d->getSource());
        $this->assertEmpty($d->getFields());
    }

    public function testAddFieldWithoutAttributes()
    {
        $field = new Field('name', 'int');
        $d = new Data($this->getMockedMeta());
        $d->addField($field);
        $this->assertSame($field, $d->getField($field->name));
        $this->assertNull($d->getGeneratedValueField());
        $this->assertCount(1, $d->getFields());
    }

    public function testAddFieldWithGeneratedValueAttributeOfTypeIdentity()
    {
        $gvAttr = new GeneratedValue();
        $field = new Field('name', 'int', 'name', $gvAttr);
        $d = new Data($this->getMockedMeta());
        $d->addField($field);
        $this->assertSame($field, $d->getField($field->name));
        $this->assertSame($field, $d->getGeneratedValueField());
        $this->assertCount(1, $d->getFields());
    }

    public function testAddFieldWithOneToManyRelationAttribute()
    {
        $relation = new Relation('ONETOMANY', 'model', 'field', 'alias');
        $field = new Field('name', 'int', 'name', $relation);
        $d = new Data($this->getMockedMeta());
        $d->addField($field);
        $this->assertSame($field, $d->getField($field->name));
        $this->assertSame('alias', $d->getField('alias')->name);
        $this->assertCount(2, $d->getFields());
    }

    public function testCreateField()
    {
        $d = new Data($this->getMockedMeta());
        $d->createField('name', 'int', 'column', new GeneratedValue());
        $this->assertSame('name', $d->getField('name')->name);
        $this->assertSame('name', $d->getGeneratedValueField()->name);
        $this->assertCount(1, $d->getFields());
    }

    public function testAddIndexFieldNotAFieldObject()
    {
        $this->expectException(RuntimeException::class);
        $d = new Data($this->getMockedMeta());
        $idx = new Index('KEY', ['name'], 'indexName');
        $d->addIndex($idx);
    }

    public function testAddIndexFieldNotFound()
    {
        $this->expectException(RuntimeException::class);
        $d = new Data($this->getMockedMeta());
        $idx = new Index('KEY', [new Field('name', 'int')], 'indexName');
        $d->addIndex($idx);
    }

    public function testAddKeyIndex()
    {
        $f = new Field('name', 'int');
        $d = new Data($this->getMockedMeta());
        $d->addField($f);
        $idx = new Index('KEY', [$f], 'idxName');
        $d->addIndex($idx);
        $this->assertNull($d->getPrimaryIndex());
        $this->assertEmpty($d->getUniqueIndexes());
    }

    public function testAddIndexTwice()
    {
        $this->expectException(RuntimeException::class);
        $f = new Field('name', 'int');
        $d = new Data($this->getMockedMeta());
        $d->addField($f);
        $idx = new Index('KEY', [$f], 'idxName');
        $d->addIndex($idx);
        $this->assertNull($d->getPrimaryIndex());
        $this->assertEmpty($d->getUniqueIndexes());
        $d->addIndex($idx);
    }

    public function testAddPrimaryIndex()
    {
        $f = new Field('name', 'int');
        $d = new Data($this->getMockedMeta());
        $d->addField($f);
        $idx = new Index('PRIMARY', [$f], 'PRIMARY');
        $d->addIndex($idx);
        $this->assertSame($idx, $d->getPrimaryIndex());
        $this->assertEmpty($d->getUniqueIndexes());
    }

    public function testAddPrimaryIndexTwice()
    {
        $f = new Field('name', 'int');
        $d = new Data($this->getMockedMeta());
        $d->addField($f);
        $idx = new Index('PRIMARY', [$f], 'PRIMARY');
        $d->addIndex($idx);
        $this->assertSame($idx, $d->getPrimaryIndex());
        $this->assertEmpty($d->getUniqueIndexes());
        $idx2 = new Index('PRIMARY', [$f], 'ANOTHER PRIMARY');
    }

    public function testAddUniqueIndex()
    {
        $f = new Field('name', 'int');
        $d = new Data($this->getMockedMeta());
        $d->addField($f);
        $idx = new Index('UNIQUE', [$f], 'UNIQUE');
        $d->addIndex($idx);
        $this->assertNull($d->getPrimaryIndex());
        $this->assertCount(1, $d->getUniqueIndexes());
    }

    public function testCreateIndexWithStringFieldThatDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $d = new Data($this->getMockedMeta());
        $d->createIndex('KEY', ['f']);
    }

    public function testCreateIndexWithInvalidFieldObject()
    {
        $this->expectException(RuntimeException::class);
        $d = new Data($this->getMockedMeta());
        $d->createIndex('KEY', [new \StdClass()]);
    }

    public function testCreateFieldWithStringField()
    {
        $d = new Data($this->getMockedMeta());
        $d->createField('name', 'int');
        $d->createIndex('PRIMARY', ['name']);
        $this->assertNotNull($d->getPrimaryIndex());
    }
}
