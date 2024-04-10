<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeDecimal;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeDecimal::class)]
#[CoversClass(Type::class)]
class TypeDecimalTest extends TestCase
{
    public function testFactory()
    {
        $type = TypeDecimal::factory(14, 4, 1, 5, true);
        $this->assertSame(14, $type->precision);
        $this->assertSame(4, $type->scale);
        $this->assertSame(1., $type->min);
        $this->assertSame(5., $type->max);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::DECIMAL, $type->type);
    }

    public function testDefaultFactory()
    {
        $type = TypeDecimal::factory();
        $this->assertSame(12, $type->precision);
        $this->assertSame(2, $type->scale);
        $this->assertSame(0., $type->min);
        $this->assertSame(4294967295., $type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::DECIMAL, $type->type);
    }

    public function testCast()
    {
        $type = TypeDecimal::factory();
        $this->assertSame('foo', $type->cast('foo'));
    }

    public function testDecast()
    {
        $type = TypeDecimal::factory();
        $this->assertSame('foo', $type->decast('foo'));
    }
}
