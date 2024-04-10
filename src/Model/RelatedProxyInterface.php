<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Orm\Model;
use Blrf\Orm\Model\Attribute\Relation;
use React\Promise\PromiseInterface;

/**
 * Interface for related proxy object
 *
 * This interface exists only so we can check if object is Related Proxy.
 * @see Manager::getRelatedProxy()
 */
interface RelatedProxyInterface
{
    public function setOrmProxyValue(mixed $value): void;
    public function getOrmProxyValue(): mixed;
    public function setOrmProxyRelation(Relation $relation): void;
    public function getOrmProxyRelation(): Relation;
    /** @return PromiseInterface<Model> */
    public function ormProxyResolve(): PromiseInterface;
}
