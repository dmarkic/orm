<?php

namespace Blrf\Tests\Orm;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Blrf\Orm\Model\Meta;
use Blrf\Orm\Model\Manager;
use ReflectionProperty;

abstract class TestCase extends BaseTestCase
{
    public function getMockedMeta(Manager $manager = null, string $model = 'Model'): Meta
    {
        if ($manager === null) {
            $manager = $this->createStub(Manager::class);
        }
        $meta = $this->getMockBuilder(Meta::class)
            ->setConstructorArgs([$manager, $model])
            ->getMock();
        return $meta;
    }
}
