<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute;
use Blrf\Orm\Model\Attribute\Index;
use Blrf\Orm\Model\Attribute\Index\Type as IndexType;
use ValueError;

#[CoversClass(Attribute::class)]
#[CoversClass(Index::class)]
#[CoversClass(IndexType::class)]
class IndexTest extends TestCase
{
    public function testIndexInvalidType()
    {
        $this->expectException(ValueError::class);
        $index = new Index('INVALID', ['field']);
    }

    public function testIndexEmptyFields()
    {
        $this->expectException(ValueError::class);
        $index = new Index(IndexType::PRIMARY, []);
    }

    public function testIndexDefault()
    {
        $index = new Index(IndexType::UNIQUE, ['unique']);
        $this->assertEquals(IndexType::UNIQUE, $index->type);
        $this->assertEquals(['unique'], $index->fields);
        $this->assertEquals('UNIQUE', $index->name);
    }

    public function testIndexPrimaryDefault()
    {
        $index = new Index(IndexType::PRIMARY, ['unique']);
        $this->assertEquals(IndexType::PRIMARY, $index->type);
        $this->assertEquals(['unique'], $index->fields);
        $this->assertEquals('PRIMARY KEY', $index->name);
    }

    public function testJsonSerialize()
    {
        $exp = '{"attrName":"Blrf\\\\Orm\\\\Model\\\\Attribute\\\\Index",' .
               '"type":"PRIMARY","fields":["field"],"name":"indexName"}';
        $index = new Index(IndexType::PRIMARY, ['field'], 'indexName');
        $this->assertSame($exp, json_encode($index));
    }
}
