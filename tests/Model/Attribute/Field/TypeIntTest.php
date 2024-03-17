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
    public function testTypeFromArray()
    {
        $type = Type::fromArray([
            'type'      => 'int',
            'min'       => 20,
            'max'       => 40,
            'isNull'    => true
        ]);
        $this->assertSame(20, $type->min);
        $this->assertSame(40, $type->max);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::INT, $type->type);
    }

    public function testTypeFromStringNamedArguments()
    {
        $type = Type::fromString('int', min: 30, max: 40, isNull: true);
        $this->assertSame(30, $type->min);
        $this->assertSame(40, $type->max);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::INT, $type->type);
    }

    public function testTypeFromStringArrayArgument()
    {
        $type = Type::fromString('int', ['min' => 30, 'max' => 40, 'isNull' => true]);
        $this->assertSame(30, $type->min);
        $this->assertSame(40, $type->max);
        $this->assertTrue($type->isNull);
        $this->assertSame(Type::INT, $type->type);
    }
    public function testDefaultConstruct()
    {
        $type = new TypeInt();
        $this->assertSame(0, $type->min);
        $this->assertSame(0xffffffff, $type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::INT, $type->type);
    }

    public function testCast()
    {
        $type = new TypeInt();
        $this->assertSame(2, $type->cast('2'));
    }
}
