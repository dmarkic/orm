<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Meta\Data;

/**
 * Model meta-data naming strategy
 *
 * Currently only used in Meta\Driver\Attribute
 */
interface NamingStrategy
{
    /**
     * Get prefix
     */
    public function getPrefix(): string;
    /**
     * Get table name from model class name
     */
    public function getTableName(string $className): string;
}
