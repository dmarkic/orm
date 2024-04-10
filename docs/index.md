# Async ORM

Async ORM implemented using [blrf/dbal](https://blrf.net/dbal) to use with any [ReactPHP](https://reactphp.org/) project.
Full example project using [Framework X](https://framework-x.org/) is available on [Orm bookstore example](https://github.com/dmarkic/orm-bookstore-example) GitHub repository.

## Install

Use [Composer](https://getcomposer.org/) to install `blrf/orm` package.

```
composer install blrf/orm:dev-main
```

## Example

### Table

For this example, we'll write a simple model that corresponds to the following database table named `example`.

| Column     | Type      | Length | Unsigned         | Options                           |
|:---        |:---       |:---    |:---              |:---                               |
| **id**     | `INT`     | 10     | :material-check: | `AUTO_INCREMENT`, `PRIMARY INDEX` |
| name       | `VARCHAR` | 50     |                  | *no default*                      |
| age        | `TINYINT` | 3      |                  | *no default*                      |

We start by writing a simple Model that will map object properties, called **fields**, to our table columns.

### Model

```php title="Example model" linenums="1"
<?php
namespace Blrf\OrmExample;

use Blrf\Orm\Model\Attribute as Attr;
use Blrf\Orm\Model;

#[Attr\Model]
#[Attr\Index(type: 'PRIMARY', fields: ['id'])]
class Example extends Model
{
    #[Attr\Field]
    #[Attr\GeneratedValue]
    protected int $id;

    #[Attr\Field]
    protected string $name;

    #[Attr\Field]
    protected int $age;
}
```

Our `Example` model extends `\Blrf\Orm\Model` and it is described using attributes. First attribute we use is `Model`, which will help ORM identify that that object is indeed ORM model described with attributes.

Next we define a `PRIMARY INDEX` with `Index` attribute. This will let ORM know that `id` field is a part of PRIMARY (and unique) index and can be used to find unique rows from table.

Each model has a set of **fields** which map object properties to database columns. We use `Field` attribute to mark property as field. Since our **id** column in table is set as `AUTO_INCREMENT` we need to tell ORM that this is a database generated value. So we add `GeneratedValue` attribute to the `id` field.

`name` and `age` properties are a simple **field**s and their datatype is automatically detected.

We are now ready to insert, update, find and delete our example models. But before we do that, we need to let ORM know how to connect to database.

### Connection

We use [Blrf\Dbal\Config](https://blrf.net/dbal/api/config/) to define a connection and we need to add that connection to ORM ModelManager.

```php title="Create connection"
<?php
$connection = new Blrf\Dbal\Config('mysql://user:pass@localhost/example');
Blrf\Orm\Factory::getModelManager()->addConnection($connection);
```

### Setters and getters

Before we start writing scripts to manipulate models, let's add some setters and getters to our example model as it will make our life a bit easier.

```php title="Example model with setters and getters" linenums="1" hl_lines="22-47"
<?php

namespace Blrf\OrmExample;

use Blrf\Orm\Model\Attribute as Attr;
use Blrf\Orm\Model;

#[Attr\Model]
#[Attr\Index(type: 'PRIMARY', fields: ['id'])]
class Example extends Model
{
    #[Attr\Field]
    #[Attr\GeneratedValue]
    protected int $id;

    #[Attr\Field]
    protected string $name;

    #[Attr\Field]
    protected int $age;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }
}

```

### Create model

Now we can create/insert our first model.

```php title="Create model"
<?php
$model = new Blrf\OrmExample\Example();
$model
    ->setName('Dejan Markič')
    ->setAge(40)
    ->save()
    ->then(
        function (Blrf\Orm\Model $model) {
            echo " - Model saved with id: " . $model->getId() . "\n";
        }
    );
```

Running this script will output:

```
 - Model saved with id: 1
```

### Find model

Let's try and find this model in database. Since we have defined a `PRIMARY KEY`, we can find model with [findByPk()](model.md#findbypk) method.

```php title="Find model by primary key"
<?php
Blrf\OrmExample\Example::findByPk(1)->then(
    function (Blrf\OrmExample\Example $model) {
        echo " - Model found:\n";
        echo " \t - id  : " . $model->getId() . "\n";
        echo " \t - name: " . $model->getName() . "\n";
        echo " \t - age : " . $model->getAge() . "\n";
    }
);
```

Running this script will output:

```
 - Model found:
         - id  : 1
         - name: Dejan Markič
         - age : 40
```

### Update model

Because I made a mistake when writing my age to model, let's write a script that will update the age. First we need to load the model and then we can update it.

```php title="Update model age"
<?php
Blrf\OrmExample\Example::findByPk(1)->then(
    function (Blrf\OrmExample\Example $model) {
        echo " - Updating model id: " . $model->getId() . "\n";
        return $model->setAge(43)->save();
    }
)->then(
    function (Blrf\OrmExample\Example $model) {
        echo " - New age: " . $model->getAge() . "\n";
    }
);
```

This script will output:

```
 - Updating model id: 1
 - New age: 43
```

### Delete model

To conclude this simple example, let's delete a model from table. First we need to load the model and then we can delete it.

```php title="Delete model"
<?php
Blrf\OrmExample\Example::findByPk(1)->then(
    function (Blrf\OrmExample\Example $model) {
        echo " - Updating model id: " . $model->getId() . "\n";
        return $model->delete();
    }
)->then(
    function (bool $deleted) {
        echo " - Model deleted: " . ($deleted ? 'YES' : 'NO') . "\n";
    }
);
```

This script will output:

```
 - Updating model id: 1
 - Model deleted: YES
```

Just for the sake of example, since we have deleted an example model with id 1, if we try to use [findByPk()](model.md#findbypk) now, we'll get [Blrf\Orm\Model\Exception\NotFoundException](model.md#notfoundexception) exception. Let's test that.

```php title="Delete model" hl_lines="11-15"
<?php
Blrf\OrmExample\Example::findByPk(1)->then(
    function (Blrf\OrmExample\Example $model) {
        echo " - Updating model id: " . $model->getId() . "\n";
        return $model->delete();
    }
)->then(
    function (bool $deleted) {
        echo " - Model deleted: " . ($deleted ? 'YES' : 'NO') . "\n";
    }
)->catch(
    function (Blrf\Orm\Model\Exception\NotFoundException $e) {
        echo " - Error: " . $e->getMessage() . "\n";
    }
);
```

Running this script will output

```
 - Error: No such model Blrf\OrmExample\Example in database: primaryKey(s): 1
```