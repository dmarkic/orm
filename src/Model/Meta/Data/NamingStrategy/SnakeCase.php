<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Meta\Data\NamingStrategy;

use Blrf\Orm\Model\Meta\Data\NamingStrategy;

/**
 * snake_case naming strategy
 *
 * Convert: FooBar to foo_bar
 */
class SnakeCase implements NamingStrategy
{
    protected string $prefix = '';

    /**
     * Convert string to snake_case
     */
    public static function convert(string $name): string
    {
        // https://stackoverflow.com/questions/1993721/how-to-convert-pascalcase-to-snake-case
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $name)), '_');
    }

    /**
     * Set global prefix
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get global prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Convert className to table_name
     */
    public function getTableName(string $className): string
    {
        return self::getPrefix() . self::convert($className);
    }
}
