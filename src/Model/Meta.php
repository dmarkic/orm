<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Orm\Factory;
use Blrf\Orm\Model;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;

/**
 * Model meta
 *
 * Uses Driver to obtain Meta data.
 *
 * To avoid loops in related fields, we run finalize after data was read.
 * @see Meta\Data::finalize();
 */
class Meta implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected Meta\Data $data;

    /** @param class-string<Model> $model */
    public function __construct(
        public readonly Manager $manager,
        public readonly string $model
    ) {
        $this->setLogger(Factory::getLogger());
    }

    /**
     * Initialize and read meta data
     *
     * @return PromiseInterface<Meta>
     */
    public function init(): PromiseInterface
    {
        return Meta\Driver::factory($this)->init()->then(
            function (Meta\Driver $driver): PromiseInterface {
                return $driver->getMetaData();
            }
        )->then(
            function (Meta\Data $data) {
                $this->data = $data;
                return $this;
            }
        );
    }

    /** @return PromiseInterface<Meta> */
    public function finalize(): PromiseInterface
    {
        return $this->data->finalize()->then(
            function () {
                return $this;
            }
        );
    }

    public function getData(): Meta\Data
    {
        return $this->data;
    }
}
