<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud;

use Neox\NeoxCrudBundle\Crud\Exception\HandlerNotFoundException;

/**
 * Factory that returns the CRUD handler from the resource name.
 */
class CrudHandlerFactory
{
    /** @var array<string, CrudHandlerInterface> */
    private array $handlers = [];

    /**
     * @param iterable<CrudHandlerInterface> $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $name = $handler->getName();
            if (isset($this->handlers[$name])) {
                throw new \LogicException(sprintf('Duplicate CRUD handler name "%s".', $name));
            }
            $this->handlers[$name] = $handler;
        }
    }

    public function get(string $name): CrudHandlerInterface
    {
        if (!isset($this->handlers[$name])) {
            throw new HandlerNotFoundException(sprintf('CRUD handler "%s" not found', $name));
        }

        return $this->handlers[$name];
    }
}
