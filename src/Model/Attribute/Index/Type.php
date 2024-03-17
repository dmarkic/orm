<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Attribute\Index;

/**
 * Index type enum
 */
enum Type: string
{
    /**
     * Primary index
     */
    case PRIMARY = 'PRIMARY';
    /**
     * Unique index
     */
    case UNIQUE = 'UNIQUE';
    /**
     * "Normal" index
     */
    case KEY = 'KEY';
}
