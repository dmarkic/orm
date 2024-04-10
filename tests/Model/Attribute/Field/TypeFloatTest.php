<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeFloat;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeFloat::class)]
#[CoversClass(Type::class)]
class TypeFloatTest extends TestCase
{
    public function testFactory()
    {
        $type = TypeFloat::factory(20, 40, true);
        $this->assertSame(20., $type->min);
        $this->assertSame(40., $type->max);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::FLOAT, $type->type);
    }

    public function testDefaultFactory()
    {
        $type = TypeFloat::factory();
        $this->assertSame(0., $type->min);
        $this->assertSame(4294967295., $type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::FLOAT, $type->type);
    }

    public function testCast()
    {
        $type = TypeFloat::factory();
        $this->assertSame(2., $type->cast('2'));
    }

    public function testCastNull()
    {
        $type = TypeFloat::factory();
        $this->assertNull($type->cast(null));
    }

    public function testDecast()
    {
        $type = TypeFloat::factory();
        $this->assertSame(2., $type->decast('2'));
    }

    public function testDecastNull()
    {
        $type = TypeFloat::factory();
        $this->assertNull($type->decast(null));
    }
}
