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
use DateTime;
use DateTimeImmutable;
use ValueError;

/**
 * Orm factory that uses Container (psr-11) to obtain required objects:
 *
 * - blrf.orm.manager: Get Orm manager (self::getModelManager())
 * - blrf.orm.meta.driver: Get Orm meta driver (self::getModelMetaDriver())
 * - blrf.orm.meta.naming: Get orm meta data naming strategy (self::getModelMetaNaming())
 * - blrf.orm.datetime.class: Default class for datetime fields (Should extend \DateTime)
 * - blrf.orm.datetime.format: Default date-time format (For datetime type)
 * - blrf.orm.datetime.format.time: Default time format (not used at the moment)
 * - blrf.orm.datetime.format.date: Default date format (for date type)
 * - blrf.orm.logger: Get logger: self::getLogger()
 */
abstract class Factory implements FactoryInterface
{
    protected static ?ContainerInterface $container = null;

    public static function setContainer(?ContainerInterface $container): void
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
     * @return class-string<MetaDriver>
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

    /** @return class-string<\DateTime|\DateTimeImmutable> Cannot return DateTimeInterface as we need createFromFormat */
    public static function getDateTimeClass(): string
    {
        $container = self::getContainer();
        $id = 'blrf.orm.datetime.class';
        if ($container->has($id)) {
            $ret = $container->get($id);
            if (!is_string($ret)) {
                throw new ValueError('blrf.orm.datetime.class should be class-string');
            }
            if (!is_a($ret, DateTime::class, true) && !is_a($ret, DateTimeImmutable::class, true)) {
                throw new ValueError('Class: ' . $ret . ' should extend from DateTime or DateTimeImmutable class');
            }
            return $ret;
        }
        return DateTimeImmutable::class;
    }

    public static function getDateTimeFormat(): string
    {
        $container = self::getContainer();
        $id = 'blrf.orm.datetime.format';
        if ($container->has($id)) {
            return $container->get($id);
        }
        return 'Y-m-d H:i:s';
    }

    public static function getDateTimeDateFormat(): string
    {
        $container = self::getContainer();
        $id = 'blrf.orm.datetime.format.date';
        if ($container->has($id)) {
            return $container->get($id);
        }
        return '!Y-m-d';
    }

    /**
     * Get time format
     *
     * Not used anywhere at the moment.
     */
    public static function getDateTimeTimeFormat(): string
    {
        $container = self::getContainer();
        $id = 'blrf.orm.datetime.format.time';
        if ($container->has($id)) {
            return $container->get($id);
        }
        return 'H:i:s';
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
