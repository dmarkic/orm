<?php

namespace Blrf\Tests\Orm;

use Blrf\Orm\Factory;
use Blrf\Orm\Container;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Manager;
use PHPUnit\Framework\Attributes\CoversClass;
use BadMethodCallException;

use function React\Async\await;
use function React\Promise\resolve;

#[CoversClass(Model::class)]
class ModelTest extends TestCase
{
    /**
     * Setup before each test
     *
     * Setup container so it will return our mocked manager
     *
     * - We need manager mock
     */
    public function setUp(): void
    {
        $manager = $this->createMock(Manager::class);
        $container = new Container();
        $container->set('blrf.orm.manager', $manager);
        Factory::setContainer($container);
    }

    /**
     * Tear down after each test
     *
     * Remove container from factory.
     */
    public function tearDown(): void
    {
        Factory::setContainer(null);
    }

    public function testCallStaticUnknownMethodWillThrowBadMethodException()
    {
        $this->expectException(BadMethodCallException::class);
        await(Model::unknownMethod());
    }

    public function testStaticFindWillCallModelManagerInvokeFind()
    {
        $arguments = ['arg' => 'yes'];
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('invokeFind')
            ->with(Model::class, '', [$arguments])
            ->willReturn(resolve('ret'));
        await(Model::find($arguments));
    }

    public function testCallUnknownMethodWillThrowBadMethodException()
    {
        $this->expectException(BadMethodCallException::class);
        $model = new TestModel();
        await($model->unknownMethod());
    }

    public function testCallSetSomethingMethodWillCallModelManagerGetModelField()
    {
        $arguments = ['arg' => 'yes'];
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('getModelField')
            ->with($model, 'RelatedField', [$arguments])
            ->willReturn(resolve('ret'));
        await($model->getRelatedField($arguments));
    }

    public function testCallGetSomethingMethodWillCallModelManagerSetModelField()
    {
        $arguments = ['arg' => 'yes'];
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('setModelField')
            ->with($model, 'RelatedField', [$arguments])
            ->willReturn(resolve('ret'));
        await($model->setRelatedField($arguments));
    }

    public function testCallToarrayWillCallModelManagerToArray()
    {
        $resolveRelated = false;
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('modelToArray')
            ->with($model, $resolveRelated)
            ->willReturn(resolve('ret'));
        await($model->toArray($resolveRelated));
    }

    public function testCallJsonEncodeOnModel()
    {
        $resolveRelated = false;
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('modelToArray')
            ->with($model, $resolveRelated)
            ->willReturn(resolve('ret'));
        $ret = json_encode($model);
        $this->assertSame('"ret"', $ret);
    }

    public function testCallAssignWillCallModelManagerAssignModel()
    {
        $data = ['data' => 'yes'];
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('assignModel')
            ->with($model, $data)
            ->willReturn(resolve('ret'));
        await($model->assign($data));
    }

    public function testCallInsertWillCallModelManagerInsertModel()
    {
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('insertModel')
            ->with($model)
            ->willReturn(resolve('ret'));
        await($model->insert());
    }

    public function testCallUpdateWillCallModelManagerUpdateModel()
    {
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('updateModel')
            ->with($model)
            ->willReturn(resolve('ret'));
        await($model->update());
    }

    public function testCallSaveWillCallModelManagerSaveModel()
    {
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('saveModel')
            ->with($model)
            ->willReturn(resolve('ret'));
        await($model->save());
    }

    public function testCallDeleteWillCallModelManagerDeleteModel()
    {
        $model = new TestModel();
        $manager = Factory::getModelManager();
        $manager
            ->expects($this->once())
            ->method('deleteModel')
            ->with($model)
            ->willReturn(resolve(true));
        await($model->delete());
    }
}
