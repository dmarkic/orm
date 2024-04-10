<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeString;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Type::class)]
#[CoversClass(BaseType::class)]
#[CoversClass(TypeString::class)]
class TypeStringTest extends TestCase
{
    public function testDefaultFactory()
    {
        $type = TypeString::factory();
        $this->assertNull($type->min);
        $this->assertNull($type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::STRING, $type->type);
        $this->assertSame('string', (string)$type);
    }

    public function testCast()
    {
        $type = TypeString::factory();
        $this->assertSame('2', $type->cast(2));
    }

    public function testCastNull()
    {
        $type = TypeString::factory();
        $this->assertNull($type->cast(null));
    }

    public function testDecast()
    {
        $type = TypeString::factory();
        $this->assertSame('2', $type->decast(2));
    }

    public function testDecastNull()
    {
        $type = TypeString::factory();
        $this->assertNull($type->decast(null));
    }
}
