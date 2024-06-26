<?php

namespace Blrf\Orm\Model\Exception;

use Blrf\Orm\Model;

/**
 * Update model internal exception
 *
 * If no changes, this exception is thrown.
 * @internal
 */
class UpdateNoChangesException extends \Exception
{
}
