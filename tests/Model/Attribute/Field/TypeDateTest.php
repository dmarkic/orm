<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeDate;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeDate::class)]
#[CoversClass(Type::class)]
class TypeDateTest extends TestCase
{
    public function testFactory()
    {
        $type = TypeDate::factory('!Y-m-d', true);
        $this->assertSame('!Y-m-d', $type->format);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::DATE, $type->type);
    }

    public function testDefaultFactory()
    {
        $type = TypeDate::factory();
        $this->assertSame('!Y-m-d', $type->format);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::DATE, $type->type);
    }

    public function testCastNull()
    {
        $type = TypeDate::factory();
        $this->assertNull($type->cast(null));
    }

    public function testCastDateTime()
    {
        $type = TypeDate::factory();
        $dt = new \DateTime();
        $this->assertSame($dt, $type->cast($dt));
    }

    public function testCastString()
    {
        $type = TypeDate::factory('!Y-m-d', true);
        $value = '2024-04-07';
        $ret = $type->cast($value);
        $this->assertInstanceOf(\DateTimeImmutable::class, $ret);
        $this->assertSame('2024-04-07 00:00:00', $ret->format('Y-m-d H:i:s'));
    }

    public function testCastInvalidString()
    {
        $this->expectException(ValueError::class);
        $type = TypeDate::factory('!Y-m-d', true);
        $value = 'InvalidString';
        $ret = $type->cast($value);
    }

    public function testDecastNull()
    {
        $type = TypeDate::factory();
        $this->assertNull($type->decast(null));
    }

    public function testDecastDateTime()
    {
        $type = TypeDate::factory('!Y-m-d', true);
        $dt = new \DateTime('2024-04-07 11:12:13');
        $ret = $type->decast($dt);
        $this->assertSame('2024-04-07', $ret);
    }
}
