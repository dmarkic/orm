<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeString;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeString::class)]
class TypeStringTest extends TestCase
{
    public function testDefaultConstruct()
    {
        $type = new TypeString();
        $this->assertNull($type->min);
        $this->assertNull($type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::STRING, $type->type);
    }

    public function testCast()
    {
        $type = new TypeString();
        $this->assertSame('2', $type->cast(2));
    }
}
