<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Meta\Driver;

use Blrf\Orm\Factory;
use Blrf\Orm\Model\Exception\MetaDriverNotAvailableException;
use Blrf\Orm\Model\Meta\Driver;
use Blrf\Orm\Model\Meta;
use Blrf\Orm\Model\Meta\Data;
use Blrf\Orm\Model\Attribute\Source;
use Blrf\Orm\Model\Attribute\Index;
use Blrf\Orm\Model\Attribute\Model;
use Blrf\Orm\Model\Attribute\DerivedModel;
use Blrf\Orm\Model\Attribute\Field;
use Blrf\Orm\Model\Attribute\RelatedField;
use Blrf\Orm\Model\Meta\Data\NamingStrategy;
use React\Promise\PromiseInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use BadMethodCallException;
use RuntimeException;

use function React\Promise\resolve;

/**
 * Get meta-data from model class attributes
 *
 * @todo It should be possible to define indexes via properties.
 * @todo Make NamingStrategy where prefixes may be defined, etc
 */
class Attribute extends Driver
{
    /**
     * @var ReflectionClass<\Blrf\Orm\Model>
     */
    protected ReflectionClass $ref;
    protected NamingStrategy $ns;

    /**
     * Construct new attribute driver
     *
     * This driver supports DerivedModel.
     *
     * If DerivedModel is received, ReflectionClass will be switched to
     * original model and original model attributes will be read.
     */
    public function __construct(
        Meta $meta
    ) {
        $this->ref = new ReflectionClass($meta->model);
        $attributes = $this->ref->getAttributes(Model::class, ReflectionAttribute::IS_INSTANCEOF);
        if (count($attributes) == 0) {
            $attributes = $this->ref->getAttributes(Source::class);
            if (empty($attributes)) {
                throw new MetaDriverNotAvailableException('No attributes found in class: ' . $meta->model);
            }
        } else {
            $modelAttr = reset($attributes);
            $i = $modelAttr->newInstance();
            if ($i instanceof DerivedModel) {
                /**
                 * Replace reflection to provided model
                 */
                $this->ref = new ReflectionClass($i->model);
            }
        }
        $this->ns = Factory::getModelMetaNamingStrategy();
        parent::__construct($meta);
    }

    /**
     * Read attributes
     */
    public function getMetaData(): PromiseInterface
    {
        $this->logger->info('Get ' . $this->meta->model . ' meta-data');
        $data = new Data($this->meta);
        /**
         * Indexes can be added after properties have been read.
         */
        $indexes = [];
        /**
         * Read class level attributes
         */
        $this->logger->info('Reading class level attributes');
        $attributes = $this->ref->getAttributes();
        foreach ($attributes as $attr) {
            $class = $attr->getName();
            $this->logger->debug(' > ' . $class . ' attribute');
            switch ($class) {
                case Model::class:
                case DerivedModel::class:
                    break;
                case Source::class:
                    $arguments = $attr->getArguments();
                    /*
                    if (count($arguments) === 0) {
                        $parts = explode('\\', $this->meta->model);
                        $table = end($parts);
                        $arguments[] = $this->ns->getTableName($table);
                    }
                    */
                    if (!isset($arguments['name'])) {
                        $parts = explode('\\', $this->meta->model);
                        $table = end($parts);
                        $arguments['name'] = $this->ns->getTableName($table);
                    }
                    $source = new $class(...$arguments);
                    $data->setSource($source);
                    break;
                case Index::class:
                    $indexes[] = $attr->getArguments();
                    break;
                default:
                    throw new RuntimeException('Unknown attribute: ' . $class);
            }
        }
        /**
         * Read property level attributes
         */
        $this->logger->info('Reading properties for model: ' .  $this->meta->model);
        $properties = $this->ref->getProperties();
        foreach ($properties as $property) {
            $this->logger->debug(' > reading property: ' . $property->getName());

            // field prop must be present Field(name, type, column, attributes)
            $attrs = $property->getAttributes(Field::class);
            if (count($attrs) > 1) {
                throw new RuntimeException('Too many Field attributes on property: ' . $property->getName());
            }
            if (count($attrs) > 0) {
                $attr = reset($attrs);
                $args = $attr->getArguments();
                $this->logger->debug('  > field attribute: ' . $attr->getName() . ' args: ' . count($args));
                $fattrs = [];

                //Arguments should be named
                $name = $args['name'] ?? $property->getName();
                $type = $args['type'] ?? (string)$property->getType();
                $fattrs = $args['attributes'] ?? [];
                $column = $args['column'] ?? null;

                if (is_string($type) && enum_exists($type)) {
                    $type = [
                        'type'  => 'enum',
                        'options'   => $type::cases()
                    ];
                }

                //$this->logger->debug(' >> Field: name=' . $name . ' type=' . $type . ' column: ' . $column);

                // Read the rest of attributes
                foreach ($property->getAttributes() as $attr) {
                    switch ($attr->getName()) {
                        case Field::class:
                            break;
                        default:
                            $this->logger->debug(' >> found additional attribute: ' . $attr->getName());
                            $fattrs[] = $attr->newInstance();
                    }
                }
                $data->addField(new Field($name, $type, $column, ...$fattrs));
            } else {
                $this->logger->debug(' ! property without Field attribute: ' . $property->getName());
            }
        }
        $this->logger->info('Reading indexes');
        foreach ($indexes as $index) {
            $data->createIndex(...$index);
        }

        /**
         * Source attribute was not provided, so set source from class name
         */
        if (!$data->hasSource()) {
            $parts = explode('\\', $this->meta->model);
            $table = end($parts);
            $table = $this->ns->getTableName($table);
            $data->setSource(new Source($table));
        }

        $this->logger->info('Done ' . $this->meta->model . ' meta-data');
        return resolve($data);
    }
}
