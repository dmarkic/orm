<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use BadMethodCallException;
use RuntimeException;
use LogicException;
use Blrf\Dbal\Config as DbalConfig;
use Blrf\Dbal\Connection;
use Blrf\Dbal\Result as DbalResult;
use Blrf\Dbal\Query\Condition;
use Blrf\Orm\Factory;
use Blrf\Orm\Model;
use Blrf\Orm\Model\Meta\Data\NamingStrategy\SnakeCase;
use Blrf\Orm\Model\Attribute\Relation;
use Blrf\Orm\Model\Exception\UpdateNoChangesException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;
use function React\Promise\reject;

/**
 * Model manager
 *
 * @phpstan-import-type ChangesReturn from Changes
 * @phpstan-import-type FindArguments from Model\Finder
 */
class Manager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Connections
     */
    protected Connections $connections;
    /**
     * List of initialized models
     *
     * Model is initialized only once.
     *
     * @var array<string,bool>
     * @todo Do we need this?
     */
    protected array $initializedModels = [];
    /**
     * Model meta(s)
     *
     * @var array<string,PromiseInterface<Meta>>
     */
    protected array $metas = [];
    /**
     * Model hydrator(s)
     * @var array<string,Hydrator>
     */
    protected array $hydrators = [];
    /**
     * Model finder(s)
     * @var array<string,PromiseInterface<Finder>>
     */
    protected array $finders = [];


    public function __construct()
    {
        $this->connections = new Connections($this);
        $this->setLogger(Factory::getLogger());
    }

    /**
     * Initialize Model
     *
     * @note Do we need this?!?
     */
    public function initialize(Model $model): void
    {
        if (!isset($this->initializedModels[$model::class])) {
            $this->logger->debug('Initialize model: ' . $model::class);
            if (method_exists($model, 'ormInitialize')) {
                $model->ormInitialize();
            }
            $this->initializedModels[$model::class] = true;
        }
    }

    /**
     * Get model meta
     *
     * Once meta is resolved, hydrator for model will be created.
     *
     * We first read the data and after that, we finalize the data.
     *
     * @see Meta\Data::finalize();
     * @param class-string<Model> $model
     * @return PromiseInterface<Meta>
     */
    public function getMeta(string $model): PromiseInterface
    {
        $class = strtolower($model);
        if (!isset($this->metas[$class])) {
            $this->logger->info('Starting meta for model: ' . $model);
            $this->metas[$class] = (new Meta($this, $model))->init();
            $this->metas[$class]->then(
                function (Meta $meta) use ($class): PromiseInterface {
                    $this->hydrators[$class] = new Hydrator($meta);
                    $this->logger->info('Meta for model: ' . $class . ' initialized. Running finalize');
                    return $meta->finalize();
                }
            );
        }
        return $this->metas[$class];
    }

    /**
     * Get model finder
     *
     * @param class-string<Model> $model
     * @return PromiseInterface<Finder>
     */
    public function getFinder(string $model): PromiseInterface
    {
        $class = strtolower($model);
        if (!isset($this->finders[$class])) {
            $this->logger->info('Creating Finder for model:' . $model);
            $this->finders[$class] = $this->getMeta($model)->then(
                function (Meta $meta) use ($model): Finder {
                    $this->logger->info('Finder created for model: ' . $model);
                    return new Finder($meta);
                }
            );
        }
        return $this->finders[$class];
    }

    /**
     * Get hydrator
     *
     * Hydrator is always called after meta for model was already retreived.
     * If it's not it's an error in design.
     *
     * Hydrator is created once meta is done loading.
     *
     * @see self::getMeta()
     */
    public function getHydrator(string $model): Hydrator
    {
        $class = strtolower($model);
        if (!isset($this->hydrators[$class])) {
            throw new LogicException('Hydrator not set for class: ' . $class);
        }
        return $this->hydrators[$class];
    }

    /**
     * Get changes for model.
     *
     * This will not work if model meta was never loaded.
     * @return ChangesReturn
     */
    public function getChanges(Model $model): array
    {
        return $this->getHydrator($model::class)->getChanges($model);
    }

    /**
     * Add connection to manager
     *
     *
     * @see Connections
     * @param string[] $ops
     */
    public function addConnection(DbalConfig $config, string $for = '*', array $ops = []): static
    {
        $this->logger->info('Adding connection: ' . $config . ' for: ' . $for . ' ops: ' . implode(', ', $ops));
        $this->getConnections()->attach(
            $config,
            [
                'for'           => $for,
                'ops'           => $ops,
                'connection'    => null
            ]
        );
        return $this;
    }

    /**
     * Get connection for model and operation
     *
     * @see Connections
     * @return PromiseInterface<\Blrf\Dbal\Connection>
     */
    public function getConnection(string $for = '*', string $op = 'NOT_DEFINED_YET'): PromiseInterface
    {
        return $this->getConnections()->get($for, $op);
    }

    /**
     * Get orm connections
     *
     * @see Connections
     */
    public function getConnections(): Connections
    {
        return $this->connections;
    }

    /**
     * Model::find*() called
     *
     * @see Finder
     * @param class-string<Model> $model
     * @param FindArguments|array<FindArguments> $arguments
     * @return PromiseInterface<QueryBuilder|Result|Model>
     */
    public function invokeFind(string $model, string $name, array $arguments): PromiseInterface
    {
        $this->logger->info('model: ' . $model . ' name: find' . $name . '() arguments: ' . count($arguments));
        return $this->getFinder($model)->then(
            function (Finder $finder) use ($model, $name, $arguments): PromiseInterface {
                $method = 'find' . $name;
                $this->logger->debug('Got finder, calling method: ' . $method);
                if (method_exists($finder, $method)) {
                    return $finder->$method($arguments);
                }
                return reject(
                    new BadMethodCallException(
                        'Call to undefined method ' . $model . '::' . $method
                    )
                );
            }
        );
    }

    /**
     * Get related model proxy object
     *
     * Proxy is used, so we do not load model immediatelly. We only remember relation and relation
     * value and resolve if needed.
     *
     * Proxy class is created on the fly, extends the model and implements the RelatedProxyInterface. All the
     * proxy code is injected via RelatedProxyTrait.
     *
     * Currently only used for ONETOONE relation
     *
     * @see self::createRelatedProxyClass()
     */
    public function getRelatedProxy(Relation $relation, mixed $value): RelatedProxyInterface
    {
        $this->logger->debug('Get related proxy: relation: ' . $relation->type->value . ' model: ' . $relation->model);
        $proxy = $this->createRelatedProxyClass($relation->model);
        $proxy->setOrmProxyRelation($relation);
        $proxy->setOrmProxyValue($value);
        return $proxy;
    }

    /**
     * Create related model proxy class
     *
     * Used mostly for testing.
     *
     * @see self::getRelatedProxy()
     * @note Uses eval()
     */
    protected function createRelatedProxyClass(string $modelClass): RelatedProxyInterface
    {
        $className = preg_replace('/[^0-9a-zA-Z]/', '_', $modelClass) . '_Proxy';
        if (!class_exists($className)) {
            $interface = RelatedProxyInterface::class;
            $trait = RelatedProxyTrait::class;
            $code = 'class ' . $className . ' extends ' . $modelClass . ' implements ' . $interface . ' {' .
                    'use ' . $trait . ';' .
                    '}';
            eval($code);
        }
        if (!is_a($className, RelatedProxyInterface::class, true)) {
            throw new LogicException('Expecting RelatedProxyInterface class: ' . $className);
        }
        return new $className();
    }

    /**
     * Get model field
     *
     * - Called via Model::__call() method (Eg $model->getSomething()).
     *
     * $name is everything after get. It will search for two field names:
     *
     * - snake_case (eg getFooBar() will search for field: foo_bar)
     * - normal (eg getFooBar() will search for field: FooBar)
     *
     * If call to related field, it will get resolved. See Finder::find() for available
     * arguments when fetching ONETOMANY related fields.
     *
     * @return PromiseInterface<mixed>
     * @param FindArguments|array<mixed> $arguments
     */
    public function getModelField(Model $model, string $name, array $arguments): PromiseInterface
    {
        $this->logger->debug(
            'Get: ' . $name . ' in model: ' . $model::class . ' arguments: ' . count($arguments)
        );
        return $this->getMeta($model::class)->then(
            function (Meta $meta) use ($name, $model, $arguments) {
                $metadata = $meta->getData();
                // example: PublisherId: names: [publisher_id, PublisherId]
                // array_unique and strtolower so we don't search 'Book' and 'book' twice.
                $names = array_unique([SnakeCase::convert($name), strtolower($name)]);

                foreach ($names as $fname) {
                    $field = $metadata->getField($fname);
                    if ($field) {
                        $relation = $field->getRelation();
                        if ($relation) {
                            /**
                             * @todo We should later break this down to some other class
                             */
                            $this->logger->debug('Field: ' . $fname . ' is related: ' . $relation->type->value);
                            if ($relation->type == Relation\Type::ONETOMANY) {
                                if ($relation->alias === $fname) {
                                    /**
                                     * relation->model is the model we search for
                                     * relation->field is the field we are searching for
                                     * value is the original field found in field->type
                                     */
                                    $value = $this->getHydrator($meta->model)
                                                ->getFieldValue($model, $field->type->field);
                                    $this->logger->debug(
                                        'ONETOMANY: value: ' . $value . ' ' .
                                        'model: ' . $relation->model . ' ' .
                                        'field: ' . $relation->field
                                    );
                                    $model = $relation->model;
                                    /**
                                     * Arguments get directly passed into find() method.
                                     *
                                     * We should allow user to call
                                     * $model->getSomeRelatedField()->limit(1)->execute(),
                                     * or $model->getSomeRelatedField(['limit' => 1]), etc.
                                     *
                                     * @see Finder::find()
                                     *
                                     * By analyzing arguments, we should be able to figure out if user wants
                                     * to further modify QueryBuilder before executing query.
                                     *
                                     * Contrary to Finder::find() default is to execute the query.
                                     *
                                     * If user wants to use QueryBuilder it should call:
                                     *
                                     * $model->getSomeRelatedField(false)->limit(1)->execute()
                                     *
                                     * User may also provide arguments accepted by Finder::find() to adjust query:
                                     *
                                     * $model->getSomeRelatedField(['limit' => 1]);
                                     *
                                     * Query will be executed.
                                     */
                                    $execute = true;
                                    if (isset($arguments[0]) && is_bool($arguments[0])) {
                                        $execute = $arguments[0];
                                        unset($arguments[0]);
                                    }
                                    return $this->getFinder($model)->then(
                                        function (Finder $finder) use ($arguments): PromiseInterface {
                                            return $finder->find(...$arguments);
                                        }
                                    )->then(
                                        function (
                                            QueryBuilder $qb
                                        ) use (
                                            $execute,
                                            $relation,
                                            $value
                                        ): PromiseInterface|QueryBuilder {
                                            $qb
                                                ->andWhere($qb->fieldCondition($relation->getField()))
                                                ->addParameter($value);
                                            if ($execute) {
                                                return $qb->execute();
                                            }
                                            return $qb;
                                        }
                                    );
                                } else {
                                    $this->logger->debug('Field: ' . $fname . ' is normal model property');
                                    return $this->getHydrator($meta->model)->getFieldValue($model, $field);
                                }
                            }
                            /**
                             * It's a related field
                             */
                            $hydrator = $this->getHydrator($meta->model);
                            $value = $hydrator->getFieldValue($model, $field);
                            if ($value instanceof RelatedProxyInterface) {
                                return $value->ormProxyResolve()->then(
                                    function ($value) use ($hydrator, $model, $field) {
                                        $hydrator->setFieldValue($model, $field, $value);
                                        return $value;
                                    }
                                );
                            } elseif ($value instanceof $relation->model || $value === null) {
                                $this->logger->debug('Value already resolved or NULL');
                                return $value;
                            } else {
                                throw new \Exception('Do not know what to do with value: ' . gettype($value));
                            }
                        } else {
                            /**
                             * Probably a 'normal' model property
                             */
                            $this->logger->debug('Field: ' . $fname . ' is normal model property');
                            return $this->getHydrator($meta->model)->getFieldValue($model, $field);
                        }
                    }
                }
                throw new RuntimeException('No such field: ' . $name . ' in model: ' . $meta->model);
            }
        );
    }

    /**
     * Set model field
     *
     * - Called via Model::__call() method (Eg $model->setSomething()).
     *
     * @see self::getModelField()
     * @return PromiseInterface<Model>
     * @param array <string,mixed> $arguments
     */
    public function setModelField(Model $model, string $name, array $arguments): PromiseInterface
    {
        $this->logger->debug(
            'Set model field: ' . $model::class . ' name: '  . $name . ' arguments: ' . count($arguments)
        );
        return $this->getMeta($model::class)->then(
            function (Meta $meta) use ($name, $model, $arguments) {
                $metadata = $meta->getData();
                // example: PublisherId: names: [publisher_id, PublisherId]
                // array_unique and strtolower so we don't search 'Book' and 'book' twice.
                $names = array_unique([SnakeCase::convert($name), strtolower($name)]);
                foreach ($names as $fname) {
                    $field = $metadata->getField($fname);
                    if ($field) {
                        $this->getHydrator($meta->model)->setFieldValue($model, $field, reset($arguments));
                        return $model;
                    }
                }
                throw new RuntimeException('No such field: ' . $name . ' in model: ' . $meta->model);
            }
        );
    }

    /**
     * Convert model to array
     *
     * @return PromiseInterface<array>
     * @return PromiseInterface<array<string, mixed>>
     */
    public function modelToArray(Model $model, bool $resolveRelated = false): PromiseInterface
    {
        $this->logger->debug('Model to array: ' . $model::class);
        return $this->getMeta($model::class)->then(
            function (Meta $meta) use ($model, $resolveRelated) {
                $hydrator = $this->getHydrator($model::class);
                return $hydrator->toArray($model, $meta->getData(), $resolveRelated);
            }
        );
    }

    /**
     * Assign data to model
     *
     * Expected data array:
     *
     * ```php
     * [
     *   'field' => 'value',
     *   '...'   => '...'
     * ]
     * ```
     *
     * @see Model::assign()
     * @param array <string, mixed> $data
     * @return PromiseInterface<Model>
     */
    public function assignModel(Model $model, array $data): PromiseInterface
    {
        $this->logger->debug('Inserting model: ' . $model::class);
        return $this->getMeta($model::class)->then(
            function (Meta $meta) use ($model, $data): Model {
                $hydrator = $this->getHydrator($model::class);
                /**
                 * Hydrate expects database columns
                 */
                $cdata = [];
                foreach ($meta->getData()->getFields() as $field) {
                    if (isset($data[$field->name])) {
                        $cdata[$field->column] = $data[$field->name];
                    }
                }
                $hydrator->hydrate($model, $meta->getData(), $cdata);
                return $model;
            }
        );
    }

    /**
     * Insert model into database
     *
     * 1. Get meta data
     * 2. Dehydrate model into array of values
     * 3. Get connection for model for 'insert'
     * 4. Run query
     * 5. If insertId is set and generatedValue field exists, set field value to received Id
     *
     * @return PromiseInterface<Model>
     */
    public function insertModel(Model $model): PromiseInterface
    {
        $values = [];
        $meta = null;
        $this->logger->debug('Inserting model: ' . $model::class);
        return $this->getMeta($model::class)->then(
            function (Meta $_meta) use ($model, &$meta, &$values): PromiseInterface {
                $meta = $_meta;
                $metadata = $meta->getData();
                $hydrator = $this->getHydrator($model::class);
                $values = $hydrator->dehydrate($model);
                return $this->getConnection($model::class, 'insert');
            }
        )->then(
            function (Connection $db) use (&$meta, &$values): PromiseInterface {
                $qb = new QueryBuilder($db->query(), $meta);
                $qb
                    ->insertSource($meta->getData()->getSource());
                foreach ($meta->getData()->getFields() as $field) {
                    if (isset($values[$field->column])) {
                        $qb->fieldValue($field, $values[$field->column]);
                    }
                }
                return $qb->execute();
            }
        )->then(
            function (Result $res) use (&$meta, $model) {
                if (!empty($res->insertId)) {
                    $this->logger->debug('Received insertId: ' . $res->insertId);
                    /**
                     * Get generated field
                     */
                    $field = $meta->getData()->getGeneratedValueField();
                    if ($field !== null) {
                        $this->logger->debug('Setting ' . $field->name . ' to insertId: ' . $res->insertId);
                        $this
                            ->getHydrator($model::class)
                            ->setFieldValue($model, $field, $res->insertId);
                    }
                    $this->getHydrator($model::class)->changes->store($model);
                    return $model;
                }
            }
        );
    }

    /**
     * Update model
     *
     * 1. Get meta data
     * 2. Get changes (if none found, return)
     * 3. Get primary or unique columns (if non found, return RuntimeException)
     * 4. Get connection for model for 'update'
     * 5. Get changed columns and values and run update query
     * 6. Register changes (hydrator->changes)
     * 6. Return model
     *
     * @return PromiseInterface<Model>
     */
    public function updateModel(Model $model): PromiseInterface
    {
        $this->logger->debug('Update model: ' . $model::class);
        $meta = null;
        $index = null;
        $changes = [];
        return $this->getMeta($model::class)->then(
            function (Meta $_meta) use ($model, &$meta, &$index, &$changes): PromiseInterface|Model {
                $meta = $_meta;
                $changes = $this->getChanges($model);
                $this->logger->info('Found ' . count($changes) . ' change(s) in model: ' . $model::class);
                if (count($changes) == 0) {
                    throw new UpdateNoChangesException();
                }
                $metadata = $meta->getData();
                $index = $metadata->getPrimaryIndex();
                if ($index === null) {
                    $unique = $metadata->getUniqueIndexes();
                    $index = reset($unique);
                    $index = $index === false ? null : $index;
                }
                if ($index === null) {
                    throw new RuntimeException(
                        'Cannot update model: ' . $model::class . '. No primary or unique index exists'
                    );
                }
                return $this->getConnection($model::class, 'update');
            }
        )->then(
            function (Connection $db = null) use ($model, &$meta, &$index, &$changes): PromiseInterface {

                $modelData = $this->getHydrator($model::class)->dehydrate($model);
                $values = [];
                $qb = new QueryBuilder($db->query(), $meta);
                $qb->updateSource($meta->getData()->getSource());
                foreach ($changes as $change) {
                    $column = $change['field']->column;
                    if (!isset($modelData[$column])) {
                        throw new LogicException('Column: ' . $column . ' does not exist in dehydrated model data');
                    }
                    //$values[$column] = $modelData[$column];
                    $qb->fieldValue($change['field'], $modelData[$column]);
                }
                //assert(!empty($values), 'Values are empty for update');
                $params = [];
                $hydrator = $this->getHydrator($model::class);
                $conditions = [];
                foreach ($index->fields as $field) {
                    $conditions[] = $qb->fieldCondition($field);
                    $params[] = $field->decast($hydrator->getFieldValue($model, $field));
                }
                assert(!empty($conditions), 'Conditions are empty');
                assert(!empty($params), 'Parameters are empty');
                return $qb
                    ->where(
                        fn($cb) => $cb->and(...$conditions)
                    )
                    ->addParameter(...$params)
                    ->execute();
            }
        )->then(
            function (Result $res) use ($model) {
                $this->logger->debug('Affected rows: ' . $res->affectedRows);
                $this->getHydrator($model::class)->changes->store($model);
                return $model;
            }
        )->otherwise(
            function (UpdateNoChangesException $e) use ($model) {
                return $model;
            }
        );
    }

    /**
     * Save model to database
     *
     * We have to decide if we insert or update the model.
     *
     * This method may be quite dangerous. If we set some model primary id to something that
     * is already in database, we'll probably update incorrect model.
     *
     * The easiest way to figure out between insert or update is by using Primary index with
     * GeneratedValue. If this is null we insert, otherwise we update.
     *
     * So for now, if there is primary index and there is a GeneratedValue field, we'll support save(),
     * otherwise we'll return RuntimeException.
     * @return PromiseInterface<Model>
     */
    public function saveModel(Model $model): PromiseInterface
    {
        $this->logger->debug('Save model: ' . $model::class);
        $metadata = null;
        $index = null;
        return $this->getMeta($model::class)->then(
            function (Meta $meta) use ($model, &$metadata, &$index): PromiseInterface {
                $metadata = $meta->getData();
                $index = $metadata->getPrimaryIndex();
                if ($index === null) {
                    /**
                     * No primary index - insert.
                     */
                    return $this->insertModel($model);
                }
                $gvField = null;
                foreach ($index->fields as $field) {
                    if ($field->isGeneratedValue()) {
                        $gvField = $field;
                        break;
                    }
                }
                if ($gvField === null) {
                    throw new RuntimeException(
                        'No primary index generated value field in model: ' . $model::class . '. ' .
                        'Please use insert() or update().'
                    );
                }
                $hydrator = $hydrator = $this->getHydrator($model::class);
                $value = $hydrator->getFieldValue($model, $gvField);
                if ($value === null) {
                    return $this->insertModel($model);
                }
                return $this->updateModel($model);
            }
        );
    }

    /**
     * Delete model from database
     *
     * 1. Get meta data
     * 2. Get primary or unique columns
     * 3. Get connection for 'delete'
     * 4. Run query
     * 5. Return affected rows
     *
     * @return PromiseInterface<bool>
     */
    public function deleteModel(Model $model): PromiseInterface
    {
        $index = null;
        $meta = null;
        return $this->getMeta($model::class)->then(
            function (Meta $_meta) use (&$index, &$meta, $model): PromiseInterface {
                $meta = $_meta;
                $metadata = $meta->getData();
                $index = $metadata->getPrimaryIndex();
                if ($index === null) {
                    $unique = $metadata->getUniqueIndexes();
                    $index = reset($unique);
                    $index = ($index === false ? null : $index);
                }
                if ($index === null) {
                    throw new RuntimeException(
                        'Cannot delete model: ' . $model::class . '. No primary or unique index exists'
                    );
                }
                return $this->getConnection($model::class, 'delete');
            }
        )->then(
            function (Connection $db) use (&$index, &$meta, $model): PromiseInterface {
                $params = [];
                $hydrator = $this->getHydrator($model::class);
                $conditions = [];
                $qb = new QueryBuilder($db->query(), $meta);
                foreach ($index->fields as $field) {
                    $conditions[] = $qb->fieldCondition($field);
                    $params[] = $hydrator->getFieldValue($model, $field);
                }
                return $qb
                    ->deleteSource($meta->getData()->getSource())
                    ->where(
                        fn($cb) => $cb->and(...$conditions)
                    )
                    ->setParameters($params)
                    ->execute();
            }
        )->then(
            function (Result $res) use ($model): bool {
                /**
                 * Destroy changes for object
                 */
                $this->getHydrator($model::class)->changes->delete($model);
                return ($res->affectedRows > 0);
            }
        );
    }
}
