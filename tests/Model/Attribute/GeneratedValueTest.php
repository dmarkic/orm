<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute\GeneratedValue;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GeneratedValue::class)]
class GeneratedValueTest extends TestCase
{
    public function testGeneratedValueDefault()
    {
        $attr = new GeneratedValue();
        $this->assertEquals($attr::IDENTITY, $attr->strategy);
    }
}
