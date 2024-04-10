<?php

declare(strict_types=1);

namespace Blrf\Orm;

use Blrf\Orm\Container\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Simple Psr-11 container implementation
 *
 * @see https://www.php-fig.org/psr/psr-11/
 * @see Factory
 */
class Container implements ContainerInterface
{
    /**
     * Container data
     * @var array<mixed>
     */
    protected array $data = [];

    /**
     * Get container entry
     *
     * @throws NotFoundException if entry with id does not exist
     */
    public function get(string $id)
    {
        if (!isset($this->data[$id])) {
            throw new NotFoundException('No such entry in container: ' . $id);
        }
        return $this->data[$id];
    }

    /**
     * Check if container entry exist
     */
    public function has(string $id): bool
    {
        return isset($this->data[$id]);
    }

    /**
     * Set container entry
     */
    public function set(string $id, mixed $value): void
    {
        $this->data[$id] = $value;
    }
}
