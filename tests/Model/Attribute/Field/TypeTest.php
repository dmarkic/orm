<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeInt;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(Type::class)]
class TypeTest extends TestCase
{
    public function testFromArrayWithEnumType()
    {
        $type = Type::fromArray(['type' => Type::INT]);
        $this->assertInstanceOf(TypeInt::class, $type);
    }

    public function testFromArrayWithoutTypeKey()
    {
        $this->expectException(ValueError::class);
        Type::fromArray([]);
    }

    public function testFromArrayWithStringType()
    {
        $type = Type::fromArray(['type' => 'INT']);
        $this->assertInstanceOf(TypeInt::class, $type);
    }

    public function testFromStringWithEnumType()
    {
        $type = Type::fromString(Type::INT);
        $this->assertInstanceOf(TypeInt::class, $type);
    }

    public function testFromStringWithStringType()
    {
        $type = Type::fromString('INT');
        $this->assertInstanceOf(TypeInt::class, $type);
    }
}
