<?php

namespace Blrf\Tests\Orm\Model\Attribute;

use Blrf\Tests\Orm\TestCase;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Attribute;
use Blrf\Orm\Model\Attribute\DerivedModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Attribute::class)]
#[CoversClass(DerivedModel::class)]
class DerivedModelTest extends TestCase
{
    public function testDerivedModel()
    {
        $model = $this->createStub(Model::class);
        $dm = new DerivedModel($model::class);
        $this->assertSame($model::class, $dm->model);
    }

    public function testJsonSerialize()
    {
        $exp = '{"attrName":"Blrf\\\\Orm\\\\Model\\\\Attribute\\\\DerivedModel","model":"name"}';
        $dm = new DerivedModel('name');
        $this->assertSame($exp, json_encode($dm));
    }
}
