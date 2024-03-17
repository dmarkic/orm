# Async ORM

Async Object–relational mapping for [ReactPHP](https://reactphp.org/).

> **Development version**: This project is currently in development.

**Table of contents**

* [Example](#example)
* [Model](#model)
* [Attributes](#attributes)
* [Factory](#factory)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Example

See example [Bookstore respository](https://github.com/dmarkic/orm-bookstore-example).

This example uses [blrf/dbal](https://github.com/dmarkic/dbal) and [framework-x](https://github.com/reactphp-framework/framework-x) to showcase current ORM development.

## Model

Model represents a single record in one database table. Model is described using [Attributes](#attributes) 

To enable Attributes driver, model class should have [Model](#model-1) or [Source](#source) class level attribute.

## Attributes

### Model

```php
#[Model]
```

`Model` attribute has no arguments. It just describes that class is a Orm model.
You can skip this attribute and just use [Source](#source) attribute.

### Source

```php
#[Source(name: string, schema: ?string)]
```

`Source` attribute defines a database table of a model. If no arguments are provided it uses class name and [Naming strategy](#naming-strategy) to obtain table name automatically.

### Index

```php
#[Index(type: Index\Type|string, fields: array, name: ?string)]
```

`Index` attribute defines indexes on for model. To use `Model::findByPk()` atleast `PRIMARY` index should be defined.

#### type

There are three type of indexes:

- PRIMARY
- UNIQUE
- KEY

#### fields

`fields` is an array of field names (string) that are a part of index.

#### name

`name` is an index name. This argument is optional. If not provided it will create a default name based on index type.

### Field

```php
#[Field(name: string, type: Field\BaseType|array|string, column: ?string, attribute: ...Attribute)]
```

`Field` attribute defines a model class property as field which is mapped to database table column.

#### name

`name` is a property name. Normally it can be ommited, since it's retreived via reflection.

#### type

`type` is a field type. It can be provided as type object, string or array. If not provided, it will try to create type via reflection.

#### column

`column` is a database table column to which this field is mapped. If not provided it will use the property name.

#### attribute

A list of [Attributes](#attributes) associated with field.

#### Type

##### int

```php
public function __construct(?int $min = 0, ?int $max = 0xffffffff, bool $isNull = false);
```

##### float

```php
public function __construct(?float $min = 0, ?float $max = 0xffffffff, bool $isNull = false);
```

##### string

```php
public function __construct(?int $min = null, ?int $max = null, bool $isNull = false);
```

##### decimal

```php
public function __construct(int $precision = 12, int $scale = 2, ?float $min = null, ?float $max = null, bool $isNull = false);
```

##### datetime

```php
public function __construct(
    string $format = 'Y-m-d H:i:s',
    bool $isNull = false,
    Type $type = Type::DATETIME,
    public readonly string $datetimeClass = DateTimeImmutable::class
);
```

##### date

```php
public function __construct(
    string $format = '!Y-m-d',
    bool $isNull = false,
    public readonly string $datetimeClass = DateTimeImmutable::class
);
```

##### related

```php
public function __construct(
    Field $field,
    bool $isNull = false
);
```

### GeneratedValue

```php
#[GeneratedValue(strategy: 'IDENTITY')]
```

This is a [Field](#field) attribute. This defines that the field value is generated by database upon insertion.

Currently only `IDENTITY` strategy is supported.

### AutoIncrement

```php
#[AutoIncrement]
```

This is a [Field](#field) attribute. It's an alias for [GeneratedValue](#generatedvalue) attribute.

### Relation

```php
#[Relation(type: Relation\Type|string, model: string, field: string, alias: string)]
```

This is a [Field](#field) attribute. It defines a relation.

#### type

Currently only two types of relations are supported:

##### ONETOONE

`ONETOONE` relation is a simple one-to-one relation where one field references another Model.

##### ONETOMANY

`ONETOMANY` relation is where one field references a list of models related by another field.

### model

This specifiesa related model of relation. It has to be a full class name of the related model.

### field

`field` by which the related model is related.

### alias

Alias is especially useful for `ONETOMANY` where name of the related field is not apparent.

## Factory

Factory uses psr-11 Container to configure various aspects of ORM. Currently there are 4 keys that factory uses to search for required objects.

### `blrf.orm.manager`

Model manager is the heart of ORM. By default it returns `Blrf\Orm\Model\Manager`.

### `blrf.orm.meta.driver`

This defines the [Meta data driver](#meta-data-drivers). By default, ORM will attempt to figure out the correct meta-driver based on what is available in model class.
With this, you can specify your own driver or effectively disable the driver discovery by choosing one.

### `blrf.orm.meta.naming`

This defines the default [Naming strategy] used by Attribute [meta data driver](#meta-data-drivers). Currently only [SnakeCase](#snakecase) is supported.

### `blrf.orm.logger`

You can attach any psr-3 compatible logger to the ORM.

## Meta data drivers

Meta data drivers are used to obtain information about a model from implementing class. Currently two are supported.

### Model

This driver enables you to define a static method in model which will initalize the `Blrf\Orm\Model\Meta\Data`.

```php
public static ormMetaData(\Blrf\Orm\Model\Meta\Data $data): \Blrf\Orm\Model\Meta\Data|\React\Promise\PromiseInterface;
```

### Attribute

This driver uses [Attributes](#attributes) to obtain model meta data.

## Naming strategy

When Attributes [meta-data-driver](#meta-data-drivers) obtains names of property or class that needs to be converted to database table/column it uses naming strategy to convert those names to names used in database.

Currently only one naming strategy is supported.

### SnakeCase

This naming strategy converts string from `FooBar` to `foo_bar` with optional `prefix`.

It's implemented in `\Blrf\Orm\Model\Meta\Data\NamingStrategy\SnakeCase`.

## Install

```
composer require blrf/orm:dev-main
```

## Tests

To run the test suite, go to project root and run:

```
vendor/bin/phpunit
```

## License

MIT, see [LICENSE file](LICENSE).

## Todo
