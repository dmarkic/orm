<?php

declare(strict_types=1);

namespace Blrf\Orm;

use Blrf\Orm\Model\Manager;
use Blrf\Orm\Model\Meta;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

interface FactoryInterface
{
    public static function setContainer(ContainerInterface $container);

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
     * Get logger instance
     */
    public static function getLogger(): LoggerInterface;
}
