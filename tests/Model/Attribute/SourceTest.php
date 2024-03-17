<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Source;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Source::class)]
class SourceTest extends TestCase
{
    public function testSourceWithoutSchema()
    {
        $source = new Source('name');
        $this->assertEquals('name', $source->name);
        $this->assertNull($source->schema);
        $this->assertEquals('name', (string)$source);
    }

    public function testSourceWithSchema()
    {
        $source = new Source('name', 'schema');
        $this->assertEquals('name', $source->name);
        $this->assertEquals('schema', $source->schema);
        $this->assertEquals('schema.name', (string)$source);
    }
}
