# Attributes

[PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) are used to describe model and it's fields.

All attributes are in `Blrf\Orm\Model\Attribute` namespace.

## #[Model]

Required attribute to mark a PHP class as a model. It has no arguments.

!!! note
    You may ommit this attribute and only use [#[Source]](#source) attribute.

## #[Source]

This attributes describes the database table for the model. It is class level attribute and is optional. If not specified, [Naming strategy](namingstrategy.md) will be used to detect table name from class name.

```php title="Example"
<?php
use Blrf\Orm\Model\Attribute as Attr;
use Blrf\Orm\Model;

#[Attr\Source(name: 'table', schema: 'schema')]
class MyModel extends Model
{
}
```

## #[DerivedModel]

This attribute marks a class as Derived model. Derived model is used when base model defines static `ormHydrateModel()` method and returns
derived model (model from which derived model extends). It inherits all attributes of parent model.

!!! note
    Derived model cannot define additional attributes or change them.

```php title="Example"
<?php
use Blrf\Orm\Model\Attribute as Attr;
use Blrf\Orm\Model;

#[Attr\DerivedModel(BaseModel::class)]
class MyModel extends BaseModel
{
}
```

!!! Example
    See [ShippingModel](https://github.com/dmarkic/orm-bookstore-example/blob/main/src/Model/ShippingMethod.php) and it's derived [ShippingMethod\Standard](https://github.com/dmarkic/orm-bookstore-example/blob/main/src/Model/ShippingMethod/Standard.php) models in [Bookstore example](https://github.com/dmarkic/orm-bookstore-example/).

## #[Index]

This attribute is used to describe database table indexes. It tells ORM which fields in model are indexed, unique, etc so methods like [findByPk()](model.md#findbypk) may be used.

| Parameter | Type   | Required        | Values | Description     |
|:---       |:---    |:---             |:---    |:---                  |
| type      | string |:material-check: | PRIMARY, UNIQUE, KEY | Type of index |
| fields    | array  |:material-check: | | Array of fields |
| name      | string |                 | string | If not provided it will be generated |

```php title="Example"
<?php
use Blrf\Orm\Model\Attribute as Attr;
use Blrf\Orm\Model;

#[Attr\Index(type: 'PRIMARY', fields: ['id'])]
#[Attr\Index(type: 'UNIQUE', fields: ['name'])]
#[Attr\Index(type: 'KEY', fields: ['lastname'])]
class MyModel extends Model
{
}
```

!!! question
    Later this will be used by schema manager: [issue#1](https://github.com/dmarkic/orm/issues/1)

## #[Field]

This attribute marks class property as field. Fields represent a map between database columns and model properties.

| Parameter | Type   | Required        | Values | Description     |
|:---       |:---    |:---             |:---    |:---                  |
| name      | string |                 | string | Property name. If not provided it will be detected |
| type      | string\|array |           | [Types](#types) | Defines data type and properties for database table column. Deteceted if not provided |
| column    | string |                 | string | Database table column name. If not provided `name` will be used |
| attributes | array |                 | [Attributes](#attributes) | Additional field attributes |

### Types

Field type are used to describe field data type. If ommited in [#[Field]](#field) attribute, it will attempt to detect it with some reasonable defaults.

#### int

TBD;

#### float

TBD;

#### decimal

TBD;

#### string

TBD;

#### datetime

TBD;

#### date

TBD;

#### related

TBD;

## #[GeneratedValue]

## #[AutoIncrement]

## #[Relation]

