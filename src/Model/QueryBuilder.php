<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\Result as DbalResult;
use Blrf\Dbal\ResultStream as DbalResultStream;
use Blrf\Dbal\Driver\QueryBuilder as BaseQueryBuilder;
use Blrf\Dbal\Query\Condition;
use Blrf\Orm\Factory;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Source;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;

/**
 * Model query builder
 *
 * @method QueryBuilder where(\Blrf\Dbal\Query\Condition|\Blrf\Dbal\Query\ConditionGroup|callable $condition)
 * @method QueryBuilder andWhere(\Blrf\Dbal\Query\Condition|\Blrf\Dbal\Query\ConditionGroup|callable $condition)
 * @method QueryBuilder orWhere(\Blrf\Dbal\Query\Condition|\Blrf\Dbal\Query\ConditionGroup|callable $condition)
 * @method QueryBuilder addOrderByExpression(\Blrf\Dbal\Query\OrderByExpression $expr)
 * @method QueryBuilder limit(?int $offset = null, ?int $limit = null)
 * @method QueryBuilder setParameters(array $params)
 * @method QueryBuilder addParameter(mixed ...$param)
 */
class QueryBuilder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        public readonly BaseQueryBuilder $queryBuilder,
        public readonly Meta $meta
    ) {
        $this->setLogger(Factory::getLogger());
    }

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): QueryBuilder
    {
        $this->logger->debug('Calling queryBuilder method: ' . $name);
        $this->queryBuilder->$name(...$arguments);
        return $this;
    }

    public function fromSource(Source $source): self
    {
        $from = (string)$source;
        $alias = $source->name;
        if ($source->quoteIdentifier) {
            $from = $this->queryBuilder->quoteIdentifier($from);
            $alias = $this->queryBuilder->quoteIdentifier($alias);
            $this->logger->debug('Source quoted identifier: from: ' . $from . ' alias: ' . $alias);
        }
        $this->queryBuilder->addFromExpression(
            $this->queryBuilder->createFromExpression($from, $alias)
        );
        return $this;
    }

    public function updateSource(Source $source): self
    {
        $from = (string)$source;
        $alias = $source->name;
        if ($source->quoteIdentifier) {
            $from = $this->queryBuilder->quoteIdentifier($from);
            $alias = $this->queryBuilder->quoteIdentifier($alias);
            $this->logger->debug('Source quoted identifier: from: ' . $from . ' alias: ' . $alias);
        }
        $this->queryBuilder->update(
            $this->queryBuilder->createFromExpression($from, $alias)
        );
        return $this;
    }

    public function insertSource(Source $source): self
    {
        $from = (string)$source;
        if ($source->quoteIdentifier) {
            $from = $this->queryBuilder->quoteIdentifier($from);
            $this->logger->debug('Source quoted identifier: from: ' . $from);
        }
        $this->queryBuilder->insert(
            $this->queryBuilder->createFromExpression($from)
        );
        return $this;
    }

    public function deleteSource(Source $source): self
    {
        $from = (string)$source;
        if ($source->quoteIdentifier) {
            $from = $this->queryBuilder->quoteIdentifier($from);
            $this->logger->debug('Source quoted identifier: from: ' . $from);
        }
        $this->queryBuilder->delete(
            $this->queryBuilder->createFromExpression($from)
        );
        return $this;
    }

    public function selectField(Field $field, string $tableAlias): self
    {
        $column = $field->column;
        $expr = $tableAlias . '.' . $column;
        $alias = $column;
        if ($field->quoteIdentifier()) {
            $expr = $this->queryBuilder->quoteIdentifier($expr);
            $alias = $this->queryBuilder->quoteIdentifier($alias);
            $this->logger->debug('Quoted identifiers: expr: ' . $expr . ' alias: ' . $alias);
        }
        $this->queryBuilder->addSelectExpression(
            $this->queryBuilder->createSelectExpression($expr, $alias)
        );
        return $this;
    }

    public function fieldValue(Field $field, mixed $value): self
    {
        $column = $field->column;
        if ($field->quoteIdentifier()) {
            $column = $this->queryBuilder->quoteIdentifier($column);
        }
        $this->queryBuilder->value($column, $value);
        return $this;
    }

    public function fieldCondition(Field $field, string $operator = '='): Condition
    {
        $column = $field->column;
        if ($field->quoteIdentifier()) {
            $column = $this->queryBuilder->quoteIdentifier($column);
        }
        return new Condition($column, $operator);
    }

    /** @return PromiseInterface<Result> */
    public function execute(): PromiseInterface
    {
        $this->logger->debug(
            'Execute sql: ' . $this->queryBuilder->getSql() . ' ' .
            'params: ' . print_r($this->queryBuilder->getParameters(), true)
        );
        return $this->queryBuilder->execute()->then(
            function (DbalResult $res): Result {
                return new Result($res, $this->meta);
            }
        );
    }

    public function stream(): ResultStream
    {
        $this->logger->debug('Stream sql: ' . $this->queryBuilder->getSql());
        return new ResultStream($this->queryBuilder->stream(), $this->meta);
    }
}
