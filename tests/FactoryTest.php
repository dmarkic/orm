<?php

namespace Blrf\Tests\Orm;

use Blrf\Orm\Factory;
use Blrf\Orm\Container;
use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Meta\Driver as MetaDriver;
use Blrf\Orm\Model\Meta\Data\NamingStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;

#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    public function testGetDefaultContainer()
    {
        $this->assertInstanceOf(Container::class, Factory::getContainer());
    }

    public function testCustomContainer()
    {
        $container = $this->createStub(Container::class);
        Factory::setContainer($container);
        $this->assertEquals($container, Factory::getContainer());
        Factory::getContainer(null);
    }

    public function testGetModelManagerCorrectId()
    {
        $id = 'blrf.orm.manager';
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('has')->with($id)->willReturn(true);
        $container->expects($this->once())->method('get')->with($id)->willReturn($this->createStub(Manager::class));
        Factory::setContainer($container);
        Factory::getModelManager();
        Factory::setContainer(null);
    }

    public function testGetDefaultModelManager()
    {
        $this->assertInstanceOf(Manager::class, Factory::getModelManager());
    }

    public function testGetModelMetaDriverCorrectId()
    {
        $id = 'blrf.orm.meta.driver';
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('has')->with($id)->willReturn(true);
        $container->expects($this->once())->method('get')->with($id)->willReturn(MetaDriver::class);
        Factory::setContainer($container);
        Factory::getModelMetaDriver();
        Factory::setContainer(null);
    }

    public function testGetModelMetaNamingCorrectId()
    {
        $id = 'blrf.orm.meta.naming';
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('has')->with($id)->willReturn(true);
        $container
            ->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($this->createStub(NamingStrategy::class));
        Factory::setContainer($container);
        Factory::getModelMetaNamingStrategy();
        Factory::setContainer(null);
    }

    public function testGetModelMetaNamingDefault()
    {
        $id = 'blrf.orm.meta.naming';
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('has')->with($id)->willReturn(false);
        Factory::setContainer($container);
        Factory::getModelMetaNamingStrategy();
        Factory::setContainer(null);
    }

    public function testGetModelMetaDriverNotSet()
    {
        $this->assertNull(Factory::getModelMetaDriver());
    }

    public function testGetLoggerCorrectId()
    {
        $id = 'blrf.orm.logger';
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('has')->with($id)->willReturn(true);
        $container->expects($this->once())->method('get')->with($id)->willReturn($this->createStub(NullLogger::class));
        Factory::setContainer($container);
        Factory::getLogger();
        Factory::setContainer(null);
    }

    public function testGetDefaultLogger()
    {
        $this->assertInstanceOf(NullLogger::class, Factory::getLogger());
    }
}
