<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\ResultStream as DbalResultStream;
use React\Stream\ReadableStreamInterface;

/**
 * Streaming result
 *
 * For streaming queries.
 */
class ResultStream extends DbalResultStream
{
    protected bool $closed = false;

    public function __construct(
        public readonly ReadableStreamInterface $stream,
        public readonly Meta $meta,
        public readonly string $hydrate_type = Result::HYDRATE_OBJECT
    ) {
        parent::__construct($stream);
    }

    /** @param array<mixed> $data */
    public function onData(array $data): void
    {
        $model = null;
        switch ($this->hydrate_type) {
            case Result::HYDRATE_OBJECT:
                $model = $this->meta->manager->getHydrator($this->meta->model)
                    ->hydrate(new $this->meta->model(), $this->meta->getData(), $data, true);
                break;
            default:
                throw new \Exception('Unknown hydration type: ' . $this->hydrate_type);
        }
        $this->emit('data', [$model]);
    }
}
