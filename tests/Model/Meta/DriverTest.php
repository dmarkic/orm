<?php

namespace Blrf\Tests\Orm\Model\Meta;

use Blrf\Orm\Container;
use Blrf\Orm\Factory;
use Blrf\Orm\Model\Meta;
use Blrf\Orm\Model\Meta\Driver\Model as ModelDriver;
use Blrf\Orm\Model\Meta\Driver\Attribute as AttributeDriver;
use Blrf\Orm\Model\Meta\Driver;
use Blrf\Tests\Orm\TestCase;
use Blrf\Tests\Orm\TestModel;
use Blrf\Tests\Orm\TestModelAttribute;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

use function React\Async\await;

#[CoversClass(Driver::class)]
class DriverTest extends TestCase
{
    public function testFactoryWithModelMetaDriverSetInOrmFactory()
    {
        $meta = $this->getMockedMeta();
        $container = new Container();
        $container->set('blrf.orm.meta.driver', ModelDriver::class);
        Factory::setContainer($container);
        $driver = Driver::factory($meta);
        $this->assertInstanceOf(ModelDriver::class, $driver);
        Factory::setContainer(null);
    }

    public function testFactoryWithModelOrmMetaDataMethod()
    {
        $meta = $this->getMockedMeta(null, TestModel::class);
        $driver = Driver::factory($meta);
        $ret = await($driver->init());
        $this->assertSame($driver, $ret);
        $this->assertInstanceOf(ModelDriver::class, $driver);
    }

    public function testFactoryWithModelAttribute()
    {
        $meta = $this->getMockedMeta(null, TestModelAttribute::class);
        $driver = Driver::factory($meta);
        $this->assertInstanceOf(AttributeDriver::class, $driver);
    }

    public function testMetaDriverNotAvailableThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $meta = $this->getMockedMeta(null, \StdClass::class);
        $driver = Driver::factory($meta);
    }
}
