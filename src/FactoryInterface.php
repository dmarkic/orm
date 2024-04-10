<?php

declare(strict_types=1);

namespace Blrf\Orm;

use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Meta;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

interface FactoryInterface
{
    /**
     * Set PSR-11 container
     */
    public static function setContainer(ContainerInterface $container): void;

    /**
     * Get PSR-11 active container
     */
    public static function getContainer(): ContainerInterface;

    /**
     * Get model manager instance
     */
    public static function getModelManager(): Manager;

    /**
     * Get model meta driver class
     */
    public static function getModelMetaDriver(): ?string;

    /**
     * Get default date-time class
     */
    public static function getDateTimeClass(): string;

    /**
     * Get default datetime date format
     */
    public static function getDateTimeDateFormat(): string;

    /**
     * Get default datetime time format
     */
    public static function getDateTimeTimeFormat(): string;

    /**
     * Get logger instance
     */
    public static function getLogger(): LoggerInterface;
}
