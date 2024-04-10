<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Meta;

use Blrf\Orm\Factory;
use Blrf\Orm\Model\Exception\MetaDriverNotAvailableException;
use Blrf\Orm\Model\Meta;
use Blrf\Orm\Model\Meta\Driver\Model as DriverModel;
use Blrf\Orm\Model\Meta\Driver\Attribute as DriverAttribute;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;
use RuntimeException;

use function React\Promise\resolve;

/**
 * Meta data access driver
 *
 * Can be set as default with `\Blrf\Orm\Factory` using Container key: `blrf.orm.meta.driver`.
 *
 * Or it can use "smart" algorithm to detect which driver to use.
 *
 * ## From within Model class
 *
 * Model class may define method: ormMetaData() which will create meta data for object.
 *
 * ## From Attributes
 *
 * Model can use attributes to define MetaData
 *
 * ## From database table
 *
 * TBD;
 * Get meta-data from database schema.
 */
abstract class Driver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public static function factory(Meta $meta): Driver
    {
        /**
         * Check if driver is set in factory
         */
        $driverClass = Factory::getModelMetaDriver();
        if ($driverClass) {
            Factory::getLogger()->info('Found ' . $driverClass . ' for model: ' . $meta->model . ' in factory');
            return new $driverClass($meta);
        }
        /**
         * Check if ormMetaData method is defined
         */
        if (method_exists($meta->model, 'ormMetaData')) {
            Factory::getLogger()->info(
                'Found ' . DriverModel::class . ' for model: ' . $meta->model . ' with ormMetaData() method'
            );
            return new DriverModel($meta);
        }
        /**
         * Try attribute driver
         */
        try {
            Factory::getLogger()->info('Trying ' . DriverAttribute::class . ' for mode: ' . $meta->model);
            return new DriverAttribute($meta);
        } catch (MetaDriverNotAvailableException $e) {
            Factory::getLogger()->warning(
                DriverAttribute::class . ' not available for model: ' . $meta->model . ': ' . $e->getMessage()
            );
        }
        throw new RuntimeException('No model meta driver available for: ' . $meta->model);
    }

    public function __construct(
        public readonly Meta $meta
    ) {
        $this->setLogger(Factory::getLogger());
        $this->logger->info('Starting ' . static::class . ' driver for: ' . $this->meta->model);
    }

    /**
     * Driver may be initialized
     *
     * @return PromiseInterface<Driver>
     */
    public function init(): PromiseInterface
    {
        return resolve($this);
    }

    /**
     * Get meta data
     *
     * @return PromiseInterface<Data>
     */
    abstract public function getMetaData(): PromiseInterface;
}
