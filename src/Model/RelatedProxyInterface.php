<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

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
    public function setOrmProxyValue(mixed $value);
    public function getOrmProxyValue();
    public function setOrmProxyRelation(Relation $relation);
    public function getOrmProxyRelation(): Relation;
    public function ormProxyResolve(): PromiseInterface;
}
