<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Orm\Factory;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Attribute\Relation;
use React\Promise\PromiseInterface;

/**
 * Related model proxy trait
 *
 * Since related proxy model is created on the fly, we inject this trait into the object, so
 * we can resolve it back to real model.
 */
trait RelatedProxyTrait
{
    /**
     * Value that is proxied
     */
    private mixed $ormProxyValue;
    /**
     * Relation attribute
     */
    private ?Relation $ormProxyRelation;

    public function setOrmProxyValue(mixed $value): void
    {
        $this->ormProxyValue = $value;
    }

    public function getOrmProxyValue(): mixed
    {
        return $this->ormProxyValue;
    }

    public function setOrmProxyRelation(Relation $relation): void
    {
        $this->ormProxyRelation = $relation;
    }

    public function getOrmProxyRelation(): Relation
    {
        return $this->ormProxyRelation;
    }

    /**
     * Resolve proxy to model
     *
     * @return PromiseInterface<Model>
     */
    public function ormProxyResolve(): PromiseInterface
    {
        $manager = Factory::getModelManager();
        $relation = $this->getOrmProxyRelation();
        if ($relation->type == Relation\Type::ONETOONE) {
            /**
             * Run a simple model::findByField(...)
             */
            return $manager->getFinder($relation->model)->then(
                function (Finder $finder) use ($relation): PromiseInterface {
                    return $finder->findFirstBy([$relation->field => $this->getOrmProxyValue()]);
                }
            );
        }
        throw new \Exception('Relation type not implemented: ' . $relation->type->value);
    }
}
