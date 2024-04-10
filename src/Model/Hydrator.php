<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Orm\Factory;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Meta\Data as MetaData;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\RelatedField;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;
use RuntimeException;
use ReflectionClass;
use ReflectionProperty;

use function React\Promise\resolve;

/**
 * Model (de)Hydrator
 *
 * It uses PHP Reflection to get/set property values.
 *
 * @phpstan-import-type ChangesReturn from Changes
 */
class Hydrator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ReflectionClass<Model> */
    protected ReflectionClass $ref;
    public readonly Changes $changes;
    /**
     * Properties
     *
     * @var array<ReflectionProperty> (name => prop)
     */
    protected array $props = [];

    public function __construct(
        public readonly Meta $meta
    ) {
        $this->setLogger(Factory::getLogger());
        $this->ref = new ReflectionClass($meta->model);
        $this->changes = new Changes($meta);
    }

    /**
     * Get model object property
     *
     */
    protected function getProperty(string $name): ReflectionProperty
    {
        if (!isset($this->props[$name])) {
            $this->props[$name] = $this->ref->getProperty($name);
        }
        return $this->props[$name];
    }

    /** @return ChangesReturn */
    public function getChanges(Model $model): array
    {
        assert($model::class === $this->meta->model, 'Wrong model: ' . $model::class . ' != ' . $this->meta->model);
        return $this->changes->getChanges($model);
    }

    /**
     * Hydrate model
     *
     * Hydrate model from received data.
     *
     * Data may be received from Model::assign() or from Result class.
     *
     * Data is: [ column => value, ... ]
     *
     * Currently only Result hydrateRow() will call hydrate with changes=true
     *
     * ## Model::ormHydrateModel()
     *
     * Model object may specify static method `ormHydrateModel(Model $model, MetaData $metadata, array $data): Model`,
     * which may return different Model of same type.
     *
     * This case is useful if you have Models that are the same but implement different functionality
     * based on database column.
     *
     * The model that is returned, should set DerivedModel(sourceModel) attribute.
     * @see Meta\Driver\Attribute
     * @note Not yet tested/supported via Meta\Driver\Model
     *
     * @param array<string, mixed> $data
     * @param bool $changes Remember values in Changes (if loaded from database)
     */
    public function hydrate(Model $model, MetaData $metadata, array $data, bool $changes = false): Model
    {
        $this->logger->debug('Hydrate model: ' . $model::class);
        /**
         * This really should be tested as it will probably not work out-of-the-box.
         */
        $method = 'ormHydrateModel';
        if (method_exists($model, $method)) {
            $ret = $model::$method($model, $metadata, $data);
            if (!($ret instanceof $model)) {
                throw new RuntimeException(
                    'Model ' . $model::class . ' has ormHydrateModel() method but it returned: ' .
                    (is_object($ret) ? 'object: ' . get_class($ret) : ' type: ' . gettype($ret))
                );
            }
            $model = $ret;
            $this->logger->debug('ormHydrateModel returned: ' . get_class($model));
        }
        foreach ($metadata->getFields() as $field) {
            if (isset($data[$field->column])) {
                $this->setFieldValue($model, $field, $data[$field->column]);
            }
        }
        if ($changes) {
            $this->changes->store($model);
        }
        return $model;
    }

    /**
     * Dehydrate object
     *
     * Convert object to database values
     *
     * Uses Field::decast() to cast value to database value.
     * @return array<string, mixed>
     */
    public function dehydrate(Model $model): array
    {
        $this->logger->debug('Dehydrate model: ' . $model::class);
        $ret = [];
        foreach ($this->meta->getData()->getFields() as $field) {
            $this->logger->debug('Dehydrate field: ' . $field->name . ' type: ' . $field->type->type->value);
            /**
             * Skip related fields
             */
            if ($field->type->type != Field\Type::RELATED) {
                $value = $this->getFieldValue($model, $field);
                $value = $field->decast($value);
                $ret[$field->column] = $value;
            }
        }
        return $ret;
    }

    /**
     * Set field value
     *
     * Value is casted through Field::cast() to be set as expected in model.
     */
    public function setFieldValue(Model $model, Field $field, mixed $value): self
    {
        $cvalue = $field->cast($value);
        $this->getProperty($field->name)->setValue($model, $cvalue);
        return $this;
    }

    /**
     * Get field value
     *
     * Value is received as set in model. You should call Field::decast($value) to get
     * database value.
     */
    public function getFieldValue(Model $model, Field $field): mixed
    {
        $name = $field->name;
        $prop = $this->getProperty($name);
        if ($prop->isInitialized($model)) {
            return $prop->getValue($model);
        }
        return null;
    }

    /**
     * Convert model to array
     *
     * ## Resolve related
     *
     * We should probably make it possible for user to select, which related models are to
     * be resolved during conversion to array.
     *
     * If related models were already resolved, $resolveRelated = false will not yield correct result as
     * fields will be resolved into arrays already.
     *
     * @param bool $resolveRelated attempt to resolve related models
     * @return PromiseInterface<array<string, mixed>|array<void>>
     */
    public function toArray(Model $model, MetaData $data, bool $resolveRelated = false): PromiseInterface
    {
        $this->logger->info('Convert model: ' . $model::class . ' toArray');
        $ret = [];
        $promises = [];
        foreach ($data->getFields() as $field) {
            $this->logger->debug('Getting field value: ' . $field->name . ' type: ' . $field->type);
            if ($field->type->type == Field\Type::RELATED) {
                continue;
            }
            $value = $this->getFieldValue($model, $field);
            if ($value instanceof RelatedProxyInterface) {
                $this->logger->debug(' > field value is a related proxy');
                if ($resolveRelated) {
                    $promises[$field->name] = $value->ormProxyResolve();
                } else {
                    $ret[$field->name] = $value->getOrmProxyValue();
                }
            } else {
                if ($value instanceof Model) {
                    $promises[$field->name] = $value;
                } else {
                    /**
                     * Should we decast the value here back to database value?
                     *
                     * This is a "normal" field and one example is the DateTime field.
                     * When \DateTime object is converted to json it's output is:
                     * {"date": ..., "timezone_type": ..., "timezone": ...}.
                     *
                     * If user would want a simple "datetime", it should implement it's own
                     * DateTime and use Factory::setDateTimeClass() to change default class which
                     * implements different jsonSerialize() method.
                     */
                    $ret[$field->name] = $value;
                }
            }
        }
        /**
         * Was there any related proxy value?
         */
        if (count($promises) > 0) {
            return \React\Promise\all($promises)->then(
                function (array $result): PromiseInterface {
                    $manager = Factory::getModelManager();
                    $promises = [];
                    foreach ($result as $fieldName => $fieldValue) {
                        $this->logger->debug('fieldName: ' . $fieldName);
                        /**
                         * If we'd return object, jsonSerialize() would be called on it again.
                         * We'll serialize it here immediatelly.
                         */
                        $promises[$fieldName] = $manager->modelToArray($fieldValue);
                    }
                    return \React\Promise\all($promises);
                }
            )->then(
                function (array $result) use ($ret): array {
                    foreach ($result as $fieldName => $fieldValue) {
                        $ret[$fieldName] = $fieldValue;
                    }
                    return $ret;
                }
            );
        }
        return \React\Promise\resolve($ret);
    }
}
