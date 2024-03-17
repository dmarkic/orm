<?php

declare(strict_types=1);

namespace Blrf\Orm;

use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Meta\Driver as MetaDriver;
use Blrf\Orm\Model\Meta\Data\NamingStrategy;
use Blrf\Orm\Model\Meta\Data\NamingStrategy\SnakeCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Orm factory that uses Container (psr-11) to obtain required objects:
 *
 * - blrf.orm.manager: Get Orm manager (self::getModelManager())
 * - blrf.orm.meta.driver: Get Orm meta driver (self::getModelMetaDriver())
 * - blrf.orm.meta.naming: Get orm meta data naming strategy (self::getModelMetaNaming())
 * - blrf.orm.logger: Get logger: self::getLogger()
 */
abstract class Factory implements FactoryInterface
{
    protected static ?ContainerInterface $container = null;

    public static function setContainer(?ContainerInterface $container)
    {
        self::$container = $container;
    }

    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            self::$container = new Container();
        }
        return self::$container;
    }

    /**
     * Get model manager instance
     */
    public static function getModelManager(): Manager
    {
        $container = self::getContainer();
        $id = 'blrf.orm.manager';
        if ($container->has($id)) {
            return $container->get($id);
        }
        $manager = new Manager();
        $container->set($id, $manager);
        return $manager;
    }

    /**
     * Get model meta driver class
     */
    public static function getModelMetaDriver(): ?string
    {
        $container = self::getContainer();
        $id = 'blrf.orm.meta.driver';
        if ($container->has($id)) {
            return $container->get($id);
        }
        return null;
    }

    /**
     * Get model meta data naming strategy
     *
     * Currently there is no easy way to set prefix (or other stuff), except by setting blrf.orm.meta.naming
     * in container with naming strategy object that is setup as you'd like.
     *
     * Maybe, we could add blrf.orm.meta.naming.prefix that would be used to set prefix from container.
     */
    public static function getModelMetaNamingStrategy(): NamingStrategy
    {
        $container = self::getContainer();
        $id = 'blrf.orm.meta.naming';
        if ($container->has($id)) {
            return $container->get($id);
        }
        return new SnakeCase();
    }

    /**
     * Get logger instance
     */
    public static function getLogger(): LoggerInterface
    {
        $container = self::getContainer();
        $id = 'blrf.orm.logger';
        if ($container->has($id)) {
            return $container->get($id);
        }
        $logger = new NullLogger();
        $container->set($id, $logger);
        return $logger;
    }
}
