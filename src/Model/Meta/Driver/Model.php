<?php

declare(strict_types=1);

namespace Blrf\Orm\Model\Meta\Driver;

use Blrf\Orm\Model\Meta\Driver;
use Blrf\Orm\Model\Meta\Data;
use React\Promise\PromiseInterface;
use RuntimeException;

use function React\Promise\resolve;
use function React\Promise\reject;

/**
 * Get meta-data from model using Model::ormMetaData()
 *
 * Model::ormMetaData should return `Data` or `PromiseInterface` that resolves to `Data`.
 */
class Model extends Driver
{
    public function getMetaData(): PromiseInterface
    {
        $model = $this->meta->model;
        $ret = $model::ormMetaData(new Data($this->meta));
        if ($ret instanceof PromiseInterface) {
            return $ret->then(
                function ($data): PromiseInterface {
                    if (!($data instanceof Data)) {
                        throw new RuntimeException(
                            'Model: ' . $this->meta->model . ' has ormMetaData() method that ' .
                            'returns Promise but does not resolve to Data object'
                        );
                    }
                    return $data->finalize();
                }
            );
        } elseif ($ret instanceof Data) {
            return $ret->finalize();
        } else {
            return reject(
                new RuntimeException(
                    'Model: ' . $this->meta->model . ' has ormMetaData() method that ' .
                    'does not return Promise or Data object. It returned: ' .
                    (is_object($ret) ? 'object: ' . get_class($ret) : ' type: ' . gettype($ret))
                )
            );
        }
    }
}
