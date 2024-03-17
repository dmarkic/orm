<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;
use Evenement\EventEmitter;

/**
 * Streaming result
 *
 * For streaming queries.
 */
class ResultStream extends EventEmitter implements ReadableStreamInterface
{
    protected bool $closed = false;

    public function __construct(
        public readonly ReadableStreamInterface $stream,
        public readonly Meta $meta,
        public readonly string $hydrate_type = Result::HYDRATE_OBJECT
    ) {
        $this->stream->on('data', [$this, 'onData']);
        $this->stream->on('end', [$this, 'onEnd']);
        $this->stream->on('error', [$this, 'onError']);
        $this->stream->on('close', [$this, 'close']);
    }

    public function isReadable(): bool
    {
        return !$this->closed;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->stream->close();
        $this->emit('close');
        $this->removeAllListeners();
    }

    public function pause(): void
    {
        $this->stream->pause();
    }

    public function resume(): void
    {
        $this->stream->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = []): WritableStreamInterface
    {
        Util::pipe($this, $dest, $options);
        return $dest;
    }

    public function onData(array $data)
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

    public function onEnd()
    {
        if (!$this->closed) {
            $this->emit('end');
            $this->close();
        }
    }

    public function onError(\Throwable $error)
    {
        $this->emit('error', [$error]);
        $this->close();
    }
}
