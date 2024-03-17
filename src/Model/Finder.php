<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\Connection;
use Blrf\Dbal\Result as DbalResult;
use Blrf\Dbal\Query\Type as QueryType;
use Blrf\Dbal\Query\Condition;
use Blrf\Dbal\Query\ConditionGroup;
use Blrf\Dbal\Query\ConditionType;
use Blrf\Orm\Model;
use Blrf\Orm\Factory;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\RelatedField;
use Blrf\Orm\Model\Exception\NotFoundException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;
use ArgumentCountError;
use BadMethodCallException;

use function React\Promise\reject;

/**
 * Model finder
 */
class Finder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        public readonly Meta $meta
    ) {
        $this->setLogger(Factory::getLogger());
    }

    /**
     * Start or execute find on model
     *
     * Arguments are the same as for QueryBuilder fromArray method
     *
     * Model is always prefixed with '\' and field delimited with '.'.
     *
     * - \Model.field
     *
     * By default it will return QueryBuilder which is setup to query all fields
     * from model and you have to call execute() on it to run query.
     *
     * If $execute is true, it will execute query immediately.
     *
     * If you want to search for all models via Model::find() you have to call it with
     * Model::find(true) to immediatelly execute query otherwise Model::find() will start QueryBuilder.
     *
     * @todo Create Model::findAll() to immediately execute query.
     *
     * @return PromiseInterface<QueryBuilder|Result>
     */
    public function find(array $arguments = [], bool $execute = false): PromiseInterface
    {
        $this->logger->debug('Find model: ' . $this->meta->model);
        if (isset($arguments[0])) {
            $arguments = $arguments[0];
            if (is_bool($arguments)) {
                $execute = $arguments;
                $arguments = [];
            }
        }
        $arguments = $arguments[0] ?? $arguments; // when called via Model::find*
        $arguments['class'] = $arguments['class'] ?? QueryBuilder::class;
        $arguments['type'] = QueryType::SELECT;
        $arguments['from'] = $arguments['from'] ?? '\\' . $this->meta->model;
        if (!isset($arguments['select'])) {
            $arguments['select'] = array_map(
                fn(Field $field) => '\\' . $this->meta->model . '.' . $field->name,
                array_filter(
                    $this->meta->getData()->getFields(),
                    fn(Field $field) => $field->type->type !== Field\Type::RELATED
                )
            );
        }
        if (!is_array($arguments['from'])) {
            $arguments['from'] = [$arguments['from']];
        }
        return $this->meta->manager->getConnection($this->meta->model, 'find')->then(
            function (Connection $connection) use ($arguments, $execute): PromiseInterface|QueryBuilder {
                $qb = QueryBuilder::fromArray($arguments, $connection, $this->meta);
                if ($execute) {
                    return $qb->execute();
                }
                return $qb;
            }
        );
    }

    /**
     * Find all
     *
     * Same as Model::find(true)
     */
    public function findAll(array $arguments = []): PromiseInterface
    {
        return $this->find([], true);
    }
    /**
     * Find model by primary key(s)
     *
     * @see self::find()
     * @return PromiseInterface<Model>
     */
    public function findByPk(array $arguments): PromiseInterface
    {
        // check if arguments are ok
        $model = $this->meta->model;
        foreach ($arguments as $aid => $argument) {
            if (!is_scalar($argument)) {
                return reject(
                    new BadMethodCallException(
                        'FindByPk: ' . $model . ' argument: ' . $aid . ' is not a scalar value'
                    )
                );
            }
        }
        $metadata = $this->meta->getData();
        // check if model has primary key(s) at all
        $primaryIndex = $metadata->getPrimaryIndex();
        if ($primaryIndex === null) {
            return reject(
                new BadMethodCallException(
                    'findByPk: model: ' . $model::class . ' has no primary index'
                )
            );
        }
        $fields = $primaryIndex->fields;
        $fieldsCount = Count($fields);
        // check if arguments count matches the primary index fields count
        $argumentsCount = count($arguments);
        if ($fieldsCount != $argumentsCount) {
            return reject(
                new ArgumentCountError(
                    'findByPk: ' . $model::class . ' expecting ' . $fieldsCount . ', ' .
                    'received ' . $argumentsCount . ' argument(s)'
                )
            );
        }
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[] = new Condition($field->column, '=');
        }
        return $this->find()->then(
            function (QueryBuilder $qb) use ($conditions, $arguments): PromiseInterface {
                return $qb
                    ->where(new ConditionGroup(ConditionType::AND, ...$conditions))
                    ->setParameters($arguments)
                    ->limit(1)
                    ->execute();
            }
        )->then(
            function (Result $result) use ($model, $arguments) {
                if (count($result) == 0) {
                    throw new NotFoundException(
                        'No such model ' . $model . ' in database: primaryKey(s): ' . implode(',', $arguments)
                    );
                }
                return $result->first();
            }
        );
    }

    /**
     * Find first model by ...
     *
     * Arguments:
     *
     * - field => value
     */
    public function findFirstBy(array $arguments): PromiseInterface
    {
        $arguments = $arguments[0] ?? $arguments; // when called via Model::findFirstBy*
        /**
         * Check arguments and create where and parameters
         */
        $model = $this->meta->model;
        $metadata = $this->meta->getData();
        $conditions = [];
        $parameters = [];
        foreach ($arguments as $fieldName => $fieldValue) {
            $field = $metadata->getField($fieldName);
            if ($field === null) {
                return reject(
                    new BadMethodCallException('No such field: ' . $field . ' in model ' . $meta->model)
                );
            }
            $conditions[] = new Condition($field->column);
            $parameters[] = $field->cast($fieldValue);
        }
        return $this->find()->then(
            function (QueryBuilder $qb) use ($conditions, $arguments): PromiseInterface {
                return $qb
                    ->where(new ConditionGroup(ConditionType::AND, ...$conditions))
                    ->setParameters($arguments)
                    ->limit(1)
                    ->execute();
            }
        )->then(
            function (Result $result) use ($model, $arguments) {
                if (count($result) == 0) {
                    throw new NotFoundException(
                        'No such model ' . $model . ' in database: primaryKey(s): ' . implode(',', $arguments)
                    );
                }
                return $result->first();
            }
        );
    }
}
