<?php

namespace Blrf\Tests\Orm;

use Blrf\Orm\Container;
use Blrf\Orm\Container\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\NotFoundExceptionInterface;

#[CoversClass(Container::class)]
#[CoversClass(NotFoundException::class)]
class ContainerTest extends TestCase
{
    public function testGetUnexistentThrowsPsrNotFoundException()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        (new Container())->get('not.found');
    }

    public function testSetHasAndGet()
    {
        $key = 'key';
        $value = 'test';
        $c = new Container();
        $this->assertFalse($c->has($key));
        $c->set($key, $value);
        $this->assertTrue($c->has($key));
        $this->assertSame($value, $c->get($key));
    }
}
