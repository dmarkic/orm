<?php

declare(strict_types=1);

namespace Blrf\Orm\Container;

use Psr\Container\NotFoundExceptionInterface;
use Exception;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
