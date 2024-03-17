<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Index;
use ValueError;

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
        $index = new Index(Index\Type::PRIMARY, []);
    }

    public function testIndexDefault()
    {
        $index = new Index(Index\Type::UNIQUE, ['unique']);
        $this->assertEquals(Index\Type::UNIQUE, $index->type);
        $this->assertEquals(['unique'], $index->fields);
        $this->assertEquals('UNIQUE', $index->name);
    }

    public function testIndexPrimaryDefault()
    {
        $index = new Index(Index\Type::PRIMARY, ['unique']);
        $this->assertEquals(Index\Type::PRIMARY, $index->type);
        $this->assertEquals(['unique'], $index->fields);
        $this->assertEquals('PRIMARY KEY', $index->name);
    }
}
