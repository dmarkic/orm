<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Orm\Model;
use Blrf\Orm\Factory;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Relation;
use Blrf\Orm\Model\RelatedProxyInterface;
use spl_object_id;
use is_scalar;

/**
 * Model changes
 *
 * This class is reponsible for tracking changes in Models. For updates, we only update
 * changed fields.
 * It's also possible to create an Audit for model from this information. Example should be provided.
 *
 * - Maybe create a method eg Model::ormGetChanges() to get changes in model.
 *
 * Changes are only monitored when object is created from database values.
 *
 * ## spl_object_id()
 *
 * Something to think about:
 * Model id's are obtained via spl_object_id(). It may happen that if Book model was loaded and data
 * was stored as Id: 1, and later on this Book object was destroyed. spl_object_id() may assign same Id to
 * different Book object and we may have invalid data for getChanges() ?
 * Since getChanges() is currently only used in Manager::updateModel() and model was previously loaded
 * from database, the data should be overwritten.
 *
 * @see Hydrator::hydrate()
 * @phpstan-type ChangesReturn array{
 *      Field: Field,
 *      current: mixed,
 *      previous: mixed
 * }|array<void>
 */
class Changes
{
    /**
     * Current data
     *
     * [ modelId => [ fieldName => [ Field => Field, value => scalar] ] ]
     * @var array<int, array<string, array{field: Field, value: mixed}>>
     */
    protected array $data = [];

    public function __construct(
        public readonly Meta $meta
    ) {
    }

    protected function getModelId(Model $model): int
    {
        return spl_object_id($model);
    }

    /**
     * Get changes
     *
     * Returned array:
     *
     * ```php
     * [
     *   fieldName => [
     *      'field' => Field,
     *      'current'   => 'currentValue',
     *      'previous' => 'previousValue'
     *   ]
     * ]
     * ```
     *
     * @return ChangesReturn
     */
    public function getChanges(Model $model): array
    {
        $id = $this->getModelId($model);
        $data = $this->data[$id] ?? null;
        if ($data === null) {
            return [];
        }
        $hydrator = Factory::getModelManager()->getHydrator($this->meta->model);
        $changes = [];
        foreach ($data as $fieldName => $pData) {
            $field = $pData['field'];
            $previousValue = $pData['value'];
            $currentValue = $field->decast($hydrator->getFieldValue($model, $field));
            if ($currentValue !== $previousValue) {
                $changes[$field->name] = [
                    'field' => $field,
                    'current'   => $currentValue,
                    'previous'  => $previousValue
                ];
            }
        }
        return $changes;
    }

    /**
     * Store snapshot of model data
     *
     * @see Hydrator::hydrate()
     */
    public function store(Model $model): void
    {
        $id = $this->getModelId($model);
        $hydrator = Factory::getModelManager()->getHydrator($this->meta->model);
        $data = [];
        foreach ($this->meta->getData()->getFields() as $field) {
            /**
             * Related fields are not stored.
             */
            if ($field->type->type == Field\Type::RELATED) {
                continue;
            }
            $data[$field->name] = [
                'field' => $field,
                'value' => $field->decast($hydrator->getFieldValue($model, $field))
            ];
        }
        $this->data[$id] = $data;
    }

    /**
     * Delete snapshot data of model
     */
    public function delete(Model $model): void
    {
        $id = $this->getModelId($model);
        if (isset($this->data[$id])) {
            unset($this->data[$id]);
        }
    }
}
