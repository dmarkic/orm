<?php

namespace Blrf\Orm\Model\Attribute;

use Blrf\Orm\Model\Attribute\Model as ModelAttr;
use Attribute;

/**
 * Mark as derived model
 *
 * Derived model is used when base model defines ormHydrateModel() and returns
 * derived model (model from which derived model extends).
 *
 * Derived model cannot define additional or change attributes, etc.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DerivedModel extends ModelAttr
{
    public function __construct(public readonly string $model)
    {
    }
}
