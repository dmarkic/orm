<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute as BaseAttribute;
use Attribute;
use ValueError;

/**
 * Define model index
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Index extends BaseAttribute
{
    public readonly Index\Type $type;
    /**
     * @var Field[] $fields
     */
    public readonly array $fields;
    public readonly string $name;

    /**
     * Construct new index
     *
     * $fields array allows string here as Attribute driver will convert those to Fields.
     * Data::addIndex() will check for this.
     *
     * @param Field[] $fields
     * @throws ValueError if fields argument is empty
     */
    public function __construct(
        Index\Type|string $type,
        array $fields,
        string $name = ''
    ) {
        if (is_string($type)) {
            $type = Index\Type::from($type);
        }
        if (empty($fields)) {
            throw new ValueError('Fields cannot be empty');
        }
        $this->type = $type;
        $this->fields = $fields;
        if (strlen($name) == 0) {
            $name = match ($type) {
                Index\Type::PRIMARY => 'PRIMARY KEY',
                Index\Type::UNIQUE => 'UNIQUE',
                default => 'INDEX'
            };
        }
        $this->name = $name;
    }
}
