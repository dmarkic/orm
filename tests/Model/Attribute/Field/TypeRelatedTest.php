<?php

namespace Blrf\Tests\Orm\Model\Attribute\Field;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Field\BaseType;
use Blrf\Orm\Model\Attribute\Field\Type;
use Blrf\Orm\Model\Attribute\Field\TypeRelated;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(Type::class)]
#[CoversClass(BaseType::class)]
#[CoversClass(TypeRelated::class)]
class TypeRelatedTest extends TestCase
{
    public function testFactory()
    {
        $field = $this->createMock(Field::class);
        $type = TypeRelated::factory($field);
        $this->assertSame($type->field, $field);
        $this->assertFalse($type->isNull);
    }

    public function testFactoryFieldIsNullThrowsValueError()
    {
        $this->expectException(ValueError::class);
        $type = TypeRelated::factory();
    }
}
