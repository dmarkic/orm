<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Relation;

/**
 * Relation type enum
 */
enum Type: string
{
    case ONETOONE = 'ONETOONE';
    case ONETOMANY = 'ONETOMANY';
}
