<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Meta;

use Blrf\Orm\Factory;
use Blrf\Orm\Model\Meta;
use Blrf\Orm\Model\Attribute;
use Blrf\Orm\Model\Attribute\Source;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\Index;
use Blrf\Orm\Model\Attribute\GeneratedValue;
use Blrf\Orm\Model\Attribute\Relation;
use React\Promise\PromiseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use LogicException;
use JsonSerializable;
use RuntimeException;
use assert;

use function React\Promise\resolve;

/**
 * Model meta data
 *
 * - Columns are in databases
 * - Fields are models' properties
 *
 * Colums and Fields are mapped.
 *
 * This object holds all attributes of model.
 */
class Data implements LoggerAwareInterface, JsonSerializable
{
    use LoggerAwareTrait;

    /**
     * Source (or table)
     */
    protected Source $source;
    /**
     * List of fields
     * @var Field[]
     */
    protected array $fields = [];
    /**
     * GeneratedValue Identity field
     *
     * This field will receive insertId value.
     */
    protected ?Field $generatedValueField = null;
    /**
     * List of indexes
     * @var Index[]
     */
    protected array $indexes = [];
    /**
     * Primary index
     */
    protected ?Index $primaryIndex = null;
    /**
     * List of unique indexes
     * @var Index[]
     */
    protected array $uniqueIndexes = [];

    /**
     * Constructor
     *
     * May be removed lated.
     */
    public function __construct(public readonly Meta $meta)
    {
        $this->setLogger(Factory::getLogger());
    }

    /**
     * json_encode()
     *
     * @return array{source:Source|null, fields: Field[], indexes: Index[]}
     */
    public function jsonSerialize(): array
    {
        return [
            'source'    => $this->source ?? null,
            'fields'    => $this->fields,
            'indexes'   => $this->indexes
        ];
    }

    /**
     * Finalize meta-data
     *
     * For now this is used only to resolve Relation attribute field to real Field object
     * from related model.
     *
     * This could cause loops if Models are related between each other.
     * ** THIS IS CAUSING LOOP ON CustomerAddress **!!!
     *
     * @return PromiseInterface<self>
     */
    public function finalize(): PromiseInterface
    {
        $this->logger->debug('Finalizing meta-data for: ' . $this->meta->model);
        $promises = [];
        foreach ($this->fields as $field) {
            $relation = $field->getRelation();
            if ($relation) {
                $this->logger->debug(
                    ' > found relation in field: ' . $relation->model . '::' . $relation->field . ' ' .
                    'from model: ' . $this->meta->model
                );
                $promises[] = Factory::getModelManager()->getMeta($relation->model)->then(
                    function (Meta $meta) use ($relation) {
                        $this->logger->debug(' << received metadata for relation: ' . $relation->model);
                        $metadata = $meta->getData();
                        $mdField = $metadata->getField($relation->field);
                        if ($mdField === null) {
                            throw new LogicException('No such field: ' . $relation->field . ' in ' . $relation->model);
                        }
                        $relation->setField($mdField);
                    }
                );
            }
        }
        $this->logger->debug('Got ' . count($promises) . ' relation(s) to solve for ' . $this->meta->model);
        if (count($promises) > 0) {
            return \React\Promise\all($promises)->then(
                function () {
                    $this->logger->debug('Successfuly resolved all relations for ' . $this->meta->model);
                    return $this;
                },
                function (\Throwable $e) {
                    $this->logger->error(
                        "\n\n" .
                        '***** ERROR WHILE FINALIZING ****' . "\n" .
                        $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() .
                        "\n\n"
                    );
                    throw $e;
                }
            );
        }
        return \React\Promise\resolve($this);
    }

    /**
     * Set model source
     */
    public function setSource(Source|string $source, string $schema = null): static
    {
        if ($source instanceof Source) {
            $this->source = $source;
        } else {
            $this->source = new Source($source, $schema);
        }
        return $this;
    }

    /**
     * Is the model source defined
     *
     * Attribute driver needs this.
     */
    public function hasSource(): bool
    {
        return isset($this->source);
    }

    /**
     * Get model source
     */
    public function getSource(): Source
    {
        return $this->source;
    }

    /**
     * Add field to model
     */
    public function addField(Field $field): static
    {
        $this->logger->debug('Add field: ' . $field->name . ' type: ' . $field->type);
        $this->fields[strtolower($field->name)] = $field;
        /**
         * Check attributes
         */
        foreach ($field->attributes as $attr) {
            $this->logger->debug(' > Field attr: ' . $attr::class);
            if ($attr instanceof GeneratedValue && $attr->strategy == $attr::IDENTITY) {
                /**
                 * It should probably only be one?!
                 *
                 * See how we will handle database defaults (Eg current_timestamp(), ...)
                 */
                assert($this->generatedValueField === null, 'Generated value field already exist: ' . $field->name);
                $this->generatedValueField = $field;
            }
            /**
             * Any field may specifiy ONETOMANY relation from that field to another Model.
             */
            if (
                /**
                 * We'll call addField again with same attribute.
                 * Avoid loop.
                 */
                $field->type->type !== Field\Type::RELATED &&
                /**
                 * Check attribute
                 */
                $attr instanceof Relation && $attr->type == Relation\Type::ONETOMANY
            ) {
                /**
                 * Add field for ONETOMANY relation
                 */
                $this->addField(
                    new Field(
                        $attr->alias,
                        Field\TypeRelated::factory($field), // missing isNull??
                        null,
                        // only include the related attribute
                        $attr
                    )
                );
            }
        }
        return $this;
    }

    /**
     * Create and add field
     *
     * @param Field\BaseType|string|array{type:string} $type
     */
    public function createField(
        string $name,
        Field\BaseType|string|array $type,
        string $column = null,
        Attribute ...$attributes
    ): static {
        return $this->addField(
            new Field($name, $type, $column, ...$attributes)
        );
    }

    /**
     * Get field by field name
     *
     * @note field names are always lowercased
     */
    public function getField(string $name): ?Field
    {
        return $this->fields[strtolower($name)] ?? null;
    }

    /**
     * Get all fields
     *
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get generated value (autoincrement, ...) field
     */
    public function getGeneratedValueField(): ?Field
    {
        return $this->generatedValueField;
    }

    /**
     * Add index
     *
     * @throws RuntimeException if field is unknown
     */
    public function addIndex(Index $index): static
    {
        foreach ($index->fields as $field) {
            if (!($field instanceof Field)) {
                throw new RuntimeException('Cannot add index: field is not a Field object');
            }
            if ($this->getField($field->name) === null) {
                throw new RuntimeException('Cannot add index: no such field: ' . $field->name);
            }
        }
        if (isset($this->indexes[$index->name])) {
            throw new RuntimeException('Cannot add index: Index already exists: ' . $index->name);
        }
        $this->indexes[$index->name] = $index;
        if ($index->type == Index\Type::PRIMARY) {
            /**
             * Can there be more than one primary index?
             */
            assert($this->primaryIndex === null, 'Primary index already exists');
            $this->primaryIndex = $index;
        } elseif ($index->type == Index\Type::UNIQUE) {
            $this->uniqueIndexes[] = $index;
        }
        return $this;
    }

    /**
     * Create and add index
     *
     * $fields may be specified as `field name` and will be replaced by field objects.
     * Fields must already exist!
     *
     * @throws RuntimeException if field is unknown or field object is not valid
     * @param string[]|Field[] $fields
     */
    public function createIndex(Index\Type|string $type, array $fields, string $name = 'INDEX'): static
    {
        if (empty($fields)) {
            throw new RuntimeException('Index expects atleast one field');
        }
        foreach ($fields as $fidx => $field) {
            if (is_string($field)) {
                $fields[$fidx] = $this->fields[$field] ?? null;
                if ($fields[$fidx] === null) {
                    throw new RuntimeException('Cannot add index: unknown field: ' . $field);
                }
            }
        }
        foreach ($fields as $idx => $field) {
            if (!($field instanceof Field)) {
                throw new RuntimeException('Field at index: ' . $idx . ' not Field object');
            }
        }
        return $this->addIndex(new Index($type, $fields, $name));
    }

    /**
     * Get primary index
     */
    public function getPrimaryIndex(): ?Index
    {
        return $this->primaryIndex;
    }

    /**
     * Get list of unique indexes
     *
     * @return array<Index>
     */
    public function getUniqueIndexes(): array
    {
        return $this->uniqueIndexes;
    }
}
