<?php

namespace Blrf\Tests\Orm\Model;

use Blrf\Dbal\Config as DbalConfig;
use Blrf\Dbal\Connection as DbalConnection;
use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Connections;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use ValueError;

use function React\Async\await;

#[CoversClass(Connections::class)]
class ConnectionsTest extends TestCase
{
    public function testConstructor()
    {
        $manager = $this->createStub(Manager::class);
        $connections = new Connections($manager);
        $this->assertEquals($manager, $connections->manager);
    }

    public function testGetWithNoConnectionsWillThrowRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $manager = $this->createStub(Manager::class);
        $connections = new Connections($manager);
        await($connections->get());
    }

    public function testGetFirstWillCreateConnection()
    {
        $connection = $this->createStub(DbalConnection::class);
        $manager = $this->createStub(Manager::class);
        $config = $this->createMock(DbalConfig::class);
        $config->expects($this->once())->method('create')->willReturn(\React\Promise\resolve($connection));
        $connections = new Connections($manager);
        $connections->attach($config);
        $ret = await($connections->get());
        $this->assertEquals($ret, $connection);
    }

    public function testAttachWithInvalidObjectWillThrowValueErrorException()
    {
        $this->expectException(ValueError::class);
        $manager = $this->createStub(Manager::class);
        $connections = new Connections($manager);
        $connections->attach(new \StdClass());
    }
}
