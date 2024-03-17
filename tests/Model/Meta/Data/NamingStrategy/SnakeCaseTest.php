<?php

namespace Blrf\Tests\Orm\Model\Meta\Data\NamingStrategy;

use Blrf\Orm\Model\Meta\Data\NamingStrategy;
use Blrf\Orm\Model\Meta\Data\NamingStrategy\SnakeCase;
use Blrf\Tests\Orm\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NamingStrategy::class)]
#[CoversClass(SnakeCase::class)]
class SnakeCaseTest extends TestCase
{
    public function testConvert()
    {
        $this->assertSame('f_foo_bar', SnakeCase::convert('FFooBar'));
    }

    public function testGetTableName()
    {
        $ns = new SnakeCase();
        $ns->setPrefix('test_');
        $this->assertSame('test_std_class', $ns->getTableName(\StdClass::class));
    }
}
