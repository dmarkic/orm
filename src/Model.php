<?php

declare(strict_types=1);

namespace Blrf\Orm;

use BadMethodCallException;
use Blrf\Dbal\Result;
use Blrf\Dbal\Schema;
use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Meta;
use React\Promise\PromiseInterface;
use JsonSerializable;

use function React\Async\await;
use function React\Promise\resolve;
use function React\Promise\reject;

/**
 * Abstract model
 *
 */
abstract class Model implements JsonSerializable
{
    /**
     * Support static call
     *
     * Static method: find*()
     */
    public static function __callStatic(string $name, array $arguments): PromiseInterface
    {
        if (substr(strtolower($name), 0, 4) == 'find') {
            return Factory::getModelManager()->invokeFind(static::class, substr($name, 4), $arguments);
        }
        throw new BadMethodCallException('Call to undefined static method: ' . static::class . '::' . $name . '()');
    }

    /**
     * Magic method call
     *
     * - get* field
     * - set* field
     */
    public function __call(string $name, array $arguments): PromiseInterface
    {
        if (substr(strtolower($name), 0, 3) == 'get') {
            return Factory::getModelManager()->getModelField($this, substr($name, 3), $arguments);
        } elseif (substr(strtolower($name), 0, 3) == 'set') {
            return Factory::getModelManager()->setModelField($this, substr($name, 3), $arguments);
        }
        throw new BadMethodCallException('Call to undefined method: ' . self::class . '::' . $name . '()');
    }

    /**
     * Convert model to array
     *
     * @see Hydrator::toArray()
     */
    public function toArray(bool $resolveRelated = false): PromiseInterface
    {
        return Factory::getModelManager()->modelToArray($this, $resolveRelated);
    }

    /**
     * JSON Serialize
     *
     * @note uses await()
     * @see self::toArray()
     */
    public function jsonSerialize(): mixed
    {
        return await($this->toArray());
    }

    /**
     * Assign data to model
     *
     * @return PromiseInterface<Model>
     */
    public function assign(array $data): PromiseInterface
    {
        return Factory::getModelManager()->assignModel($this, $data);
    }

    /**
     * Update/Create model
     *
     * @return PromiseInterface<Model>
     */
    public function save(): PromiseInterface
    {
        return Factory::getModelManager()->saveModel($this);
    }

    /**
     * Insert model
     *
     * @return PromiseInterface<Model>
     */
    public function insert(): PromiseInterface
    {
        return Factory::getModelManager()->insertModel($this);
    }

    /**
     * Update model
     *
     * @return PromiseInterface<Model>
     */
    public function update(): PromiseInterface
    {
        return Factory::getModelManager()->updateModel($this);
    }

    /**
     * Delete model
     *
     * @return PromiseInterface<bool>
     */
    public function delete(): PromiseInterface
    {
        return Factory::getModelManager()->deleteModel($this);
    }
}
