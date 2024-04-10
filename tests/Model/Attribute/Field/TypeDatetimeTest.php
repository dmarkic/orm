<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeDatetime;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeDatetime::class)]
#[CoversClass(Type::class)]
class TypeDatetimeTest extends TestCase
{
    public function testFactory()
    {
        $type = TypeDatetime::factory('Y-m-d\TH:i:s', true);
        $this->assertSame('Y-m-d\TH:i:s', $type->format);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::DATETIME, $type->type);
    }

    public function testDefaultFactory()
    {
        $type = TypeDatetime::factory();
        $this->assertSame('Y-m-d H:i:s', $type->format);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::DATETIME, $type->type);
    }

    public function testCastNull()
    {
        $type = TypeDatetime::factory();
        $this->assertNull($type->cast(null));
    }

    public function testCastDateTime()
    {
        $type = TypeDatetime::factory();
        $dt = new \DateTime();
        $this->assertSame($dt, $type->cast($dt));
    }

    public function testCastString()
    {
        $type = TypeDatetime::factory('Y-m-d\TH:i:s', true);
        $value = '2024-04-07T10:11:12';
        $ret = $type->cast($value);
        $this->assertInstanceOf(\DateTimeImmutable::class, $ret);
        $this->assertSame('2024-04-07 10:11:12', $ret->format('Y-m-d H:i:s'));
    }

    public function testCastInvalidString()
    {
        $this->expectException(ValueError::class);
        $type = TypeDatetime::factory('Y-m-d\TH:i:s', true);
        $value = 'InvalidString';
        $ret = $type->cast($value);
    }

    public function testDecastNull()
    {
        $type = TypeDatetime::factory();
        $this->assertNull($type->decast(null));
    }

    public function testDecastDateTime()
    {
        $type = TypeDatetime::factory('Y-m-d\TH:i:s', true);
        $dt = new \DateTime('2024-04-07 11:12:13');
        $ret = $type->decast($dt);
        $this->assertSame('2024-04-07T11:12:13', $ret);
    }
}
