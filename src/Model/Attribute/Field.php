<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Factory;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Related;
use Blrf\Orm\Model\RelatedProxyInterface;
use Blrf\Orm\Model\Attribute as BaseAttribute;
use Attribute;
use ValueError;
use LogicException;

/**
 * Define model property as field
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field extends BaseAttribute
{
    /**
     * Field name
     *
     * This is the model property.
     */
    public readonly string $name;
    /**
     * Field type
     *
     * Basic field type
     */
    public readonly Field\BaseType $type;
    /**
     * Field attributes
     * @var array<int, BaseAttribute>
     */
    public readonly array $attributes;
    /**
     * Optional map model property to column
     */
    public readonly string $column;
    /**
     * Relation
     *
     * Set if this field is marked (via attribute) as related field
     */
    protected ?Relation $relation = null;
    /**
     * Generated Value
     *
     * Set if this field is marked as generated value (via attribute)
     */
    protected ?GeneratedValue $generatedValue = null;
    /**
     * Quote identifier
     *
     * If QuoteIdentifier attribute is found, this will be set to true.
     */
    protected bool $quoteIdentifier = false;

    /**
     * Construct new field
     *
     * @param string $name Field name (or model object property)
     * @param Field\BaseType|array{'type': string}|string $type Basic field type
     * @param string $column Column in database table (if null, same as name)
     * @param BaseAttribute $attribute List of attributes for field
     */
    public function __construct(
        string $name = '',
        Field\BaseType|array|string $type = '',
        ?string $column = null,
        BaseAttribute ...$attribute
    ) {
        if (strlen($name) == 0) {
            throw new ValueError('Field name cannot be empty');
        }
        $this->name = $name;
        // check type
        if (is_string($type)) {
            $type = Field\Type::fromString($type);
        } elseif (is_array($type)) {
            $type = Field\Type::fromArray($type);
        }
        $this->type = $type;
        // assign attributes
        $this->attributes = array_values($attribute);
        // check attributes and assign:
        // - relation
        // - generated value
        foreach ($this->attributes as $attribute) {
            if ($attribute instanceof Relation) {
                assert($this->relation === null, 'Relation attribute already exists on field: ' . $this->name);
                $this->relation = $attribute;
            } elseif ($attribute instanceof GeneratedValue) {
                assert(
                    $this->generatedValue === null,
                    'Generated value attribute already exists on field: ' . $this->name
                );
                $this->generatedValue = $attribute;
            } elseif ($attribute instanceof QuoteIdentifier) {
                $this->quoteIdentifier = true;
            }
        }
        $this->column = (empty($column) ? $name : $column);
    }

    /**
     * Convert object to string
     *
     * @return string $column
     */
    public function __toString()
    {
        return $this->column;
    }

    public function getRelation(): ?Relation
    {
        return $this->relation;
    }

    public function isGeneratedValue(): bool
    {
        return $this->generatedValue !== null;
    }

    public function quoteIdentifier(): bool
    {
        return $this->quoteIdentifier;
    }

    /**
     * Cast field value to value type expected in model object property
     *
     * Hydrator will call this method.
     *
     * ## Relation
     *
     * If field is a relation to another model it will created RelatedProxy object via Manager::getRelatedProxy().
     *
     * @throws ValueError if field is null, is not generatedValue (attr) and null is not allowed
     */
    public function cast(mixed $value): mixed
    {
        if ($value === null) {
            if ($this->generatedValue === null && $this->type->isNull === false) {
                throw new ValueError('Field: ' . $this->name . ' cannot be null');
            }
            return null;
        }
        if ($this->relation) {
            switch ($this->relation->type) {
                case Relation\Type::ONETOONE:
                    if ($value instanceof Model) {
                        return $value;
                    }
                    /**
                     * This is quite dangerous. If for instance an object is provided
                     * like Promise, it will be converted to int and set to incorrect value.
                     *
                     * Let's not allow objects as values for now.
                     *
                     * Note: if it is promise, someone forgot to call await() or did not resolve promise
                     *       correctly.
                     */
                    if (is_object($value)) {
                        throw new ValueError(
                            'Cannot cast field: ' . $this->name . ' value from object: ' . get_class($value)
                        );
                    }
                    $value = $this->type->cast($value);
                    /**
                     * Create related proxy object
                     */
                    return Factory::getModelManager()->getRelatedProxy($this->relation, $value);
                case Relation\Type::ONETOMANY:
                    /**
                     * This field is one to many related, so normal casting is performed.
                     */
                    return $this->type->cast($value);
                default:
                    // @codeCoverageIgnoreStart
                    throw new LogicException(
                        'Unhandled field relation type: ' . $this->relation->type->value . '. ' .
                        'Field: ' . $this->name
                    );
                    // @codeCoverageIgnoreEnd
            }
        }
        return $this->type->cast($value);
    }

    /**
     * de-cast value to value expected by database
     */
    public function decast(mixed $value): mixed
    {
        $relation = $this->getRelation();
        if ($relation !== null) {
            /**
             * This would work only for ONETOONE relation?!
             */
            if ($value instanceof RelatedProxyInterface) {
                $value = $value->getOrmProxyValue();
            } elseif ($value instanceof Model) {
                $hydrator = Factory::getModelManager()->getHydrator($value::class);
                $value = $hydrator->getFieldValue($value, $relation->getField());
            }
        }
        return $this->type->decast($value);
    }
}
