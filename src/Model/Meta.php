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
 */
class Meta implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected Meta\Data $data;

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

    public function getData(): Meta\Data
    {
        return $this->data;
    }
}
