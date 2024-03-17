<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeRelated;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BaseType::class)]
#[CoversClass(TypeRelated::class)]
class TypeRelatedTest extends TestCase
{
    public function testDefaultConstruct()
    {
        $field = $this->createMock(Field::class);
        $type = new TypeRelated($field);
        $this->assertNull($type->min);
        $this->assertNull($type->max);
        $this->assertFalse($type->isNull);
        $this->assertSame(Type::RELATED, $type->type);
    }

    public function testCast()
    {
        $field = $this->createMock(Field::class);
        $type = new TypeRelated($field);
        $this->assertSame('2', $type->cast('2'));
    }
}
