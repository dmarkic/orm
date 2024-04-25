<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\Connection;
use Blrf\Dbal\Query\Condition;
use Blrf\Dbal\Query\ConditionGroup;
use Blrf\Dbal\Query\ConditionType;
use Blrf\Dbal\Query\FromExpression;
use Blrf\Dbal\Query\OrderByExpression;
use Blrf\Dbal\Query\SelectExpression;
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
 *
 * @phpstan-type FindArguments array{
 *      order?:array<mixed>|string,
 *      limit?:array{limit?: int|null, offset?: int|null}|int|null,
 *      offset?:int
 * }
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
     * Start find
     *
     * This will setup the QueryBuilder to perform select on current model.
     *
     * Certain stuff will be available to be provided as arguments to find. To support
     * where query, we should probably be able to parse the where query.
     *
     * Arguments would look like this:
     * 0 => 'where query',
     * 'key' => 'value'
     * where keys are various SQL expression (limit, order,...)
     *
     * @param FindArguments|array<FindArguments> $arguments
     * @return PromiseInterface<QueryBuilder>
     * @note This method should accept field names not columns
     */
    public function find(array $arguments = []): PromiseInterface
    {
        $this->logger->debug('find() with argument keys: ' . implode(', ', array_keys($arguments)));
        return $this->meta->manager->getConnection($this->meta->model, 'find')->then(
            function (Connection $connection) use ($arguments): QueryBuilder {
                if (isset($arguments[0])) {
                    /**
                     * maybe this should be done in Manager::invokeFind()?
                     */
                    $arguments = $arguments[0];
                }
                /**
                 * Only joined table will have aliases, when we get there.
                 */
                $metadata = $this->meta->getData();
                $source = $metadata->getSource();
                $alias = $source->name;
                $qb = new QueryBuilder($connection->query(), $this->meta);
                $qb->fromArray([
                    'type'  => 'SELECT',
                    ...$arguments
                ]);
                $qb->fromSource($source);
                $select = array_map(
                    fn(Field $field) => $qb->selectField($field, $alias),
                    array_filter(
                        $metadata->getFields(),
                        fn(Field $field) => $field->type->type != Field\Type::RELATED
                    )
                );
                /**
                 * order argument
                 *
                if (isset($arguments['order'])) {
                    if (is_array($arguments['order'])) {
                        foreach ($arguments['order'] as $order) {
                            if (is_array($order)) {
                                $qb->addOrderByExpression(OrderByExpression::fromArray($order));
                            } elseif (is_string($order)) {
                                $qb->addOrderbyExpression(OrderByExpression::fromString($order));
                            }
                        }
                    } elseif (is_string($arguments['order'])) {
                        $qb->addOrderByExpression(OrderByExpression::fromString($arguments['order']));
                    }
                }
                /**
                 * limit, offset argument
                 *
                if (isset($arguments['limit'])) {
                    if (is_array($arguments['limit'])) {
                        $limitArg = $arguments['limit'];
                    } else {
                        $limitArg = $arguments;
                    }
                    $limit = isset($limitArg['limit']) ? (int)$limitArg['limit'] : null;
                    $offset = isset($limitArg['offset']) ? (int)$limitArg['offset'] : null;
                    $qb->limit($limit, $offset);
                }*/
                return $qb;
            }
        );
    }

    /**
     * Find all
     *
     * Same as Model::find(true)
     * @param FindArguments $arguments
     * @return PromiseInterface<Result>
     */
    public function findAll(array $arguments = []): PromiseInterface
    {
        return $this->find($arguments)->then(
            function (QueryBuilder $qb): PromiseInterface {
                return $qb->execute();
            }
        );
    }
    /**
     * Find model by primary key(s)
     *
     * @see self::find()
     * @param array<mixed> $arguments
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
                    'findByPk: model: ' . $model . ' has no primary index'
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
                    'findByPk: ' . $model . ' expecting ' . $fieldsCount . ', ' .
                    'received ' . $argumentsCount . ' argument(s)'
                )
            );
        }
        return $this->find()->then(
            function (QueryBuilder $qb) use ($fields, $arguments): PromiseInterface {
                $conditions = [];
                foreach ($fields as $field) {
                    $conditions[] = $qb->fieldCondition($field);
                }
                return $qb
                    ->where(new ConditionGroup(ConditionType::AND, ...$conditions))
                    ->setParameters($arguments)
                    ->limit(1)
                    ->execute();
            }
        )->then(
            function (Result $result) use ($model, $arguments): Model {
                $ret = $result->first();
                if ($ret === null) {
                    throw new NotFoundException(
                        'No such model ' . $model . ' in database: primaryKey(s): ' . implode(',', $arguments)
                    );
                }
                return $ret;
            }
        );
    }

    /**
     * Find first model by ...
     *
     * Arguments:
     *
     * - field => value
     * @param array<mixed> $arguments
     * @return PromiseInterface<Model>
     */
    public function findFirstBy(array $arguments): PromiseInterface
    {
        $arguments = $arguments[0] ?? $arguments; // when called via Model::findFirstBy*
        return $this->find()->then(
            function (QueryBuilder $qb) use ($arguments): PromiseInterface {
                $model = $this->meta->model;
                $metadata = $this->meta->getData();
                $conditions = [];
                $parameters = [];
                foreach ($arguments as $fieldName => $fieldValue) {
                    $field = $metadata->getField($fieldName);
                    if ($field === null) {
                        throw new BadMethodCallException('No such field: ' . $fieldName . ' in model: ' . $model);
                    }
                    $conditions[] = $qb->fieldCondition($field);
                    $parameters[] = $field->cast($fieldValue);
                }

                return $qb
                    ->where(new ConditionGroup(ConditionType::AND, ...$conditions))
                    ->setParameters($parameters)
                    ->limit(1)
                    ->execute();
            }
        )->then(
            function (Result $result): Model {
                $ret = $result->first();
                if ($ret === null) {
                    throw new NotFoundException(
                        'No such model ' . $this->meta->model
                    );
                }
                return $ret;
            }
        );
    }
}
