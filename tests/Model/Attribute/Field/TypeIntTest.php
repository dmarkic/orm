<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeInt;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeInt::class)]
#[CoversClass(Type::class)]
class TypeIntTest extends TestCase
{
    public function testFactory()
    {
        $type = TypeInt::factory(20, 40, true);
        $this->assertSame(20, $type->min);
        $this->assertSame(40, $type->max);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::INT, $type->type);
    }

    public function testDefaultFactory()
    {
        $type = TypeInt::factory();
        $this->assertSame(0, $type->min);
        $this->assertSame(0xffffffff, $type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::INT, $type->type);
    }

    public function testCast()
    {
        $type = TypeInt::factory();
        $this->assertSame(2, $type->cast('2'));
    }

    public function testCastNull()
    {
        $type = TypeInt::factory();
        $this->assertNull($type->cast(null));
    }

    public function testDecast()
    {
        $type = TypeInt::factory();
        $this->assertSame(2, $type->decast('2'));
    }

    public function testDecastNull()
    {
        $type = TypeInt::factory();
        $this->assertNull($type->decast(null));
    }
}
