<?php

declare(strict_types=1);

namespace Blrf\Orm\Model;

use Blrf\Dbal\Config as DbalConfig;
use Blrf\Orm\Factory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\PromiseInterface;
use ValueError;
use RuntimeException;
use SplObjectStorage;

/**
 * List of available Dbal connections for Orm
 *
 * You add `\Blrf\Dbal\Config` (connection config) via Manager::addConnection().
 *
 * ## for
 *
 * Idea here is, that you will use Orm for various modules that requires different connections to
 * different tables. So `for` is `fnmatch()`ed against models `namespace\class` and you can use that
 * to control which connection is used certain models or namespaces.
 *
 * Default is `*`
 *
 * ## op
 *
 * Different `op`erations will call `get()` and you could define different connection for
 * `find`, `delete`, .... Operations will be documented later.
 *
 * @note Operation is currently not matched
 * @phpstan-type ConnectionInfo array{
 *      for: string,
 *      ops: string[],
 *      connection: null|PromiseInterface<\Blrf\Dbal\Connection>
 * }
 * @extends SplObjectStorage<DbalConfig, ConnectionInfo>
 */
class Connections extends SplObjectStorage implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Constructor
     *
     * @note Is manager really needed here?
     */
    public function __construct(public readonly Manager $manager)
    {
        $this->setLogger(Factory::getLogger());
    }

    /** @return PromiseInterface<\Blrf\Dbal\Connection> */
    public function get(string $for = '*', string $op = 'NOT_DEFINED_YET'): PromiseInterface
    {
        foreach ($this as $config) {
            $data = $this[$config];
            if (fnmatch($data['for'], $for, FNM_NOESCAPE)) {
                $this->logger->debug('Found connection: ' . $config . ' for ' . $for);
                if ($data['connection'] === null) {
                    $data['connection'] = $config->create();
                    $this->setInfo($data);
                }
                return $data['connection'];
            }
        }
        throw new RuntimeException('No connection available for: ' . $for);
    }

    /**
     * Attach new connection configuration
     *
     * Info is an array:
     *
     * - for: string: fnmatch'ed string for namespace\class
     * - ops: array: Array of operations to use connection for (not defined yet)
     * - connection: The connection it self (will be established by self::get when needed)
     *
     * @param ConnectionInfo|null $info
     */
    public function attach(object $config, mixed $info = null): void
    {
        if (!($config instanceof DbalConfig)) {
            throw new ValueError(
                'Connections expects ' . DbalConfig::class . ' - received: ' . $config::class
            );
        }
        if (!is_array($info)) {
            $info = [
                'for'           => '*',
                'ops'           => [],
                'connection'    => null
            ];
        }
        parent::attach($config, $info);
    }
}
