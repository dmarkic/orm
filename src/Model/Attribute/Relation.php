<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute as BaseAttribute;
use Attribute;
/**
 * Define model relation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Relation extends BaseAttribute
{
    /**
     * Relation type
     */
    public readonly Relation\Type $type;
    /**
     * Related model
     * @var class-string<\Blrf\Orm\Model>
     */
    public readonly string $model;
    /**
     * Related field
     * @see $field
     */
    public readonly string $field;
    /**
     * Real Field object from related model
     * @see Blrf\Orm\Model\Meta\Data::finalize()
     */
    protected Field $rfield;
    /**
     * Alias for ONETOMANY relation
     */
    public readonly string $alias;


    /**
     * @param class-string<\Blrf\Orm\Model> $model
     */
    public function __construct(
        Relation\Type|string $type,
        string $model,
        string $field,
        string $alias = ''
    ) {
        if (is_string($type)) {
            $type = Relation\Type::from($type);
        }
        $this->type = $type;
        $this->model = $model;
        $this->field = $field;
        $this->alias = strtolower($alias);
    }

    /**
     * @return array{
     *      attrName: class-string,
     *      type: Relation\Type,
     *      model: class-string,
     *      field: string,
     *      alias: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'attrName'  => get_class($this),
            'type'      => $this->type,
            'model'     => $this->model,
            'field'     => $this->field,
            'alias'     => $this->alias
        ];
    }

    /**
     * Real Field object from related model
     *
     * Once meta-data, where this relation is specified, is done, it
     * will finalize() and resolve sfield to real Field from related
     * model meta-data.
     */
    public function setField(Field $field): self
    {
        $this->rfield = $field;
        return $this;
    }

    public function getField(): Field
    {
        return $this->rfield;
    }
}
