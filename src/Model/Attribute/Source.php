<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute as BaseAttribute;
use Attribute;

/**
 * Define source for model
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Source extends BaseAttribute
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $schema = null
    ) {
    }

    public function __toString(): string
    {
        return ($this->schema === null ? '' : $this->schema . '.') . $this->name;
    }
}
