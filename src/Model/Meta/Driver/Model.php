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
    /** @return PromiseInterface<Data> */
    public function getMetaData(): PromiseInterface
    {
        $model = $this->meta->model;
        if (method_exists($model, 'ormMetaData')) {
            $ret = $model::ormMetaData(new Data($this->meta));
        } else {
            throw new RuntimeException('ormMetaData method does not exist in model: ' . $model);
        }
        if ($ret instanceof PromiseInterface) {
            return $ret->then(
                function ($data): Data {
                    if (!($data instanceof Data)) {
                        throw new RuntimeException(
                            'Model: ' . $this->meta->model . ' has ormMetaData() method that ' .
                            'returns Promise but does not resolve to Data object'
                        );
                    }
                    return $data;
                }
            );
        } elseif ($ret instanceof Data) {
            return resolve($ret);
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
