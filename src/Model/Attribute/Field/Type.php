<?php

namespace Blrf\Orm\Model\Attribute\Field;

use ucfirst;
use class_exists;
use assert;
use InvalidArgumentException;
use LogicException;

enum Type: string
{
    case INT = 'int';
    case FLOAT = 'float';
    case STRING = 'string';
    case BOOL = 'bool';
    case ARRAY = 'array';
    case DECIMAL = 'decimal';
    case DATETIME = 'datetime';
    case DATE = 'date';
    case RELATED = 'related';

    protected static function getClassName(Type $type): string
    {
        $type = ucfirst($type->value);
        $class = __NAMESPACE__ . '\\Type' . $type;
        if (class_exists($class)) {
            return $class;
        }
        throw new LogicException('Unknown type: ' . $type);
    }

    /**
     * Create field type from array
     *
     * $data array should atleast have `type`. Other array keys and values
     * are forwarded to type constructor as named arguments.
     */
    public static function fromArray(array $data): BaseType
    {
        if (!isset($data['type'])) {
            throw new InvalidArgumentException('Missing type');
        }
        $type = $data['type'];
        if (is_string($type)) {
            $type = self::from(strtolower($type));
        }
        $class = self::getClassName($type);
        return $class::fromArray($data);
    }

    /**
     * Create field type from string
     *
     * Example:
     *
     * ```php
     * $type = Type::fromString('string', min: 10);
     * ```
     */
    public static function fromString(Type|string $type, ...$arguments): BaseType
    {
        // if called directly with array (fromString('int', ['...']))
        $arguments = $arguments[0] ?? $arguments;
        if (is_string($type)) {
            $type = self::from(strtolower($type));
        }
        $class = self::getClassName($type);
        return $class::fromString($arguments);
    }
}
