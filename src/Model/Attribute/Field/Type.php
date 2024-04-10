<?php

namespace Blrf\Orm\Model\Attribute\Field;

use Blrf\Orm\Model\Attribute\Field;
use ucfirst;
use class_exists;
use assert;
use LogicException;
use ValueError;

/**
 * Rewrite types
 *
 * You can always do new TypeDecimal(...).
 *
 * But you could always call:
 *
 * Type::fromArray($data) <- where type is specified in array key type
 * Type::fromString(string $type, array $data), which would basically call Type::fromArray()
 *
 * But at the end it's all about attributes:
 *
 * #[Attr\Field('name', 'int')] // default properties of type are used
 * #[Attr\Field('name', ['type' => 'int', ...])] // you can change properties of type
 *
 * Since we are using BaseType constructor, we create factory() methods, that will
 * accept certain arguments specific to the type. And we get rid of BaseType::fromArray, fromString.
 *
 */
enum Type: string
{
    case INT = 'int';
    case FLOAT = 'float';
    case STRING = 'string';
    case ENUM = 'enum';
    case DECIMAL = 'decimal';
    case DATETIME = 'datetime';
    case DATE = 'date';
    case RELATED = 'related';

    /**
     *
     * @param Type $type
     * @return class-string<BaseType>
     */
    protected static function getClassName(Type $type): string
    {
        $type = ucfirst($type->value);
        $class = __NAMESPACE__ . '\\Type' . $type;
        if (class_exists($class)) {
            return $class;
        }
        throw new LogicException('Type class does not exist: ' . $type); // @codeCoverageIgnore
    }

    /**
     * Create field type from array
     *
     * @param array{
     *      type?: string,
     *      min?:int|float|null,
     *      max?:int|float|null,
     *      precision?:int|null,
     *      scale?:int|null,
     *      isNull?:bool,
     *      options?:string[],
     *      format?:string,
     *      field?:Field
     * } $data
     */
    public static function fromArray(array $data): BaseType
    {
        if (!isset($data['type'])) {
            throw new ValueError('Array is missing type key');
        }
        $type = $data['type'];
        if (is_string($type)) {
            $type = self::from(strtolower($type));
        }
        unset($data['type']);
        $class = self::getClassName($type);
        return $class::factory(...$data);
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
    public static function fromString(Type|string $type): BaseType
    {
        if (is_string($type)) {
            $type = self::from(strtolower($type));
        }
        $class = self::getClassName($type);
        return $class::factory();
    }
}
