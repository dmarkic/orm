<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Attribute;
use Blrf\Orm\Model\Attribute\GeneratedValue;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Attribute::class)]
#[CoversClass(GeneratedValue::class)]
class GeneratedValueTest extends TestCase
{
    public function testGeneratedValueDefault()
    {
        $attr = new GeneratedValue();
        $this->assertEquals($attr::IDENTITY, $attr->strategy);
    }

    public function testJsonSerialize()
    {
        $exp = '{"attrName":"Blrf\\\\Orm\\\\Model\\\\Attribute\\\\GeneratedValue",' .
               '"strategy":"IDENTITY"}';
        $attr = new GeneratedValue();
        $this->assertSame($exp, json_encode($attr));
    }
}
