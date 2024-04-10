<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\Result as DbalResult;
use Blrf\Dbal\ResultStream as DbalResultStream;
use Blrf\Dbal\Driver\QueryBuilder as BaseQueryBuilder;
use Blrf\Orm\Factory;
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
