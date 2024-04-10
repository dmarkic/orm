<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\Result as DbalResult;
use Blrf\Orm\Model;
use Countable;
use Iterator;
use count;

/**
 * Model query result
 *
 * How do we present this result?
 *
 * Raw result is available in Result::$result
 *
 * @implements Iterator<int, Model>
 */
class Result implements Iterator, Countable
{
    public const HYDRATE_OBJECT = 'HYDRATE_OBJECT';

    protected int $position = 0;

    public readonly ?int $insertId;
    public readonly int $affectedRows;
    public readonly int $warningCount;

    public function __construct(
        public readonly DbalResult $result,
        public readonly Meta $meta,
        public readonly string $hydrate_type = self::HYDRATE_OBJECT
    ) {
        $this->position = 0;
        $this->insertId = $result->insertId;
        $this->affectedRows = $result->affectedRows;
        $this->warningCount = $result->warningCount;
    }

    public function count(): int
    {
        return count($this->result);
    }

    public function first(): ?Model
    {
        $row = $this->result->rows[0] ?? null;
        return ($row === null ? null : $this->hydrateRow($row));
    }

    /** @param array<mixed> $row */
    public function hydrateRow(array $row): Model
    {
        switch ($this->hydrate_type) {
            case self::HYDRATE_OBJECT:
                return $this->meta->manager->getHydrator($this->meta->model)
                    ->hydrate(new $this->meta->model(), $this->meta->getData(), $row, true);
            default:
                throw new \Exception('Unknown hydration type: ' . $this->hydrate_type);
        }
    }

    /**
     * ITERATOR METHODS
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): Model
    {
        return $this->hydrateRow($this->result->rows[$this->position]);
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->result->rows[$this->position]);
    }
}
