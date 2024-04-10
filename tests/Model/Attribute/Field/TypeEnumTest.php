<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Tests\Orm\TestBackedEnum;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeEnum::class)]
#[CoversClass(Type::class)]
class TypeEnumTest extends TestCase
{
    public function testFactory()
    {
        $type = TypeEnum::factory(['options'], true);
        $this->assertSame(['options'], $type->options);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::ENUM, $type->type);
    }

    public function testDefaultFactoryWithThrowValueError()
    {
        $this->expectException(ValueError::class);
        $type = TypeEnum::factory();
    }

    public function testCastNull()
    {
        $type = TypeEnum::factory(['options']);
        $this->assertNull($type->cast(null));
    }

    public function testCastNotBackedEnum()
    {
        $type = TypeEnum::factory(['options']);
        $this->assertSame('Y', $type->cast('Y'));
    }

    public function testCaseBackedEnum()
    {
        $type = TypeEnum::factory(TestBackedEnum::cases());
        $this->assertSame(TestBackedEnum::YES, $type->cast('Y'));
    }

    public function testCaseBackedEnumNotFound()
    {
        $this->expectException(ValueError::class);
        $type = TypeEnum::factory(TestBackedEnum::cases());
        $type->cast('NOT_FOUND');
    }

    public function testDecastNull()
    {
        $type = TypeEnum::factory(['options']);
        $this->assertNull($type->decast(null));
    }

    public function testDecastNotBackedEnum()
    {
        $type = TypeEnum::factory(['options']);
        $this->assertSame('Y', $type->decast('Y'));
    }

    public function testDecastBackedEnum()
    {
        $type = TypeEnum::factory(['options']);
        $this->assertSame('Y', $type->decast(TestBackedEnum::YES));
    }
}
