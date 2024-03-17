<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\QueryBuilder as BaseQueryBuilder;
use Blrf\Dbal\Query\FromExpression;
use Blrf\Dbal\Query\SelectExpression;
use Blrf\Dbal\Connection;
use Blrf\Dbal\Result as DbalResult;
use Blrf\Orm\Factory;
use Blrf\Orm\Model\Result as ModelResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Model query builder
 *
 * This query builder will resolve models and fields.
 *
 * For instance:
 *
 * $qb->select('*')->from(Model)->where(Model.id = ?)->setParameters([0 => 1])->limit(1);
 *
 * We should be able to handle ONETOONE relation directly in query, if desired.
 *
 * Based on arguments, this QueryBuilder is for one model. We'll see later.
 */
class QueryBuilder extends BaseQueryBuilder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Get last element from a delimited string
     *
     * Written here, because I don't know which method would be faster to retreive class
     * name without namespace.
     *
     * Based on this stackoverflow:
     *
     * - https://stackoverflow.com/questions/19901850/how-do-i-get-an-objects-unqualified-short-class-name
     *
     * It seems Reflection is the fastest to retreive class "short-name".
     *
     * Example: getLastElement(\Foo\Bar, \) => Bar
     *
     * @return string
     */
    public static function getLastElement(string $expr, string $delimiter = '\\'): string
    {
        return array_slice(explode('\\', $expr), -1)[0];
    }

    public function __construct(
        public readonly Connection $connection,
        public readonly Meta $meta,
        public string $hydrate_type = Result::HYDRATE_OBJECT
    ) {
        $this->setLogger(Factory::getLogger());
    }

    /**
     * Execute query
     *
     * 1. Convert all elements of query to normal sql
     * 2. Get connection and execute query
     * 3. Return our model result
     *
     * @return PromiseInterface<ModelResult>
     */
    public function execute(): PromiseInterface
    {
        return $this->convertToSql()->then(
            function () {
                $this->logger->debug('sql: '  . $this->getSql());
                return $this->connection->execute($this->getSql(), $this->parameters);
            }
        )->then(
            function (DbalResult $result) {
                return new ModelResult($result, $this->meta, $this->hydrate_type);
            }
        );
    }

    /**
     * Execute query and return Result stream
     *
     */
    public function stream(): ReadableStreamInterface
    {
        return \React\Promise\Stream\unwrapReadable(
            $this->convertToSql()->then(
                function () {
                    return new ResultStream(
                        $this->connection->stream($this->getSql(), $this->parameters),
                        $this->meta,
                        $this->hydrate_type
                    );
                }
            )
        );
    }

    /**
     * Convert \Model.field to database columns.
     *
     * @todo Do we need promise here?
     */
    public function convertToSql(): PromiseInterface
    {
        $this->logger->debug('Convert sql: ' . $this->getSql());
        $metadata = $this->meta->getData();
        /**
         * Figure out Model aliases or create them
         *
         * This also forms the 'from' sql.
         */
        $aliases = [];
        $froms = [];
        foreach ($this->from as $from) {
            if (str_starts_with($from->expression, '\\')) {
                $alias = $from->alias;
                if (empty($alias)) {
                    $alias = self::getLastElement($from->expression);
                }
                $aliases[$from->expression] = $alias;
                $froms[] = new FromExpression((string)$metadata->getSource(), $alias);
            }
        }
        $selects = [];
        /**
         * Convert select expressions
         *
         * If select expression has alias it would cause problem with
         * model hydration from result.
         */
        foreach ($this->select as $select) {
            list($alias, $field) = explode('.', $select->expression);
            if (str_starts_with($alias, '\\')) {
                $alias = $aliases[$alias];
            }
            $column = $metadata->getField($field)->column;
            $selects[] = new SelectExpression($alias . '.' . $column);
        }

        $this->from = $froms;
        $this->select = $selects;
        return \React\Promise\resolve(null);
    }
}
