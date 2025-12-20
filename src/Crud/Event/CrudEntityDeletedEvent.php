<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after an entity is deleted.
 */
class CrudEntityDeletedEvent extends Event
{
    public function __construct(
        private string $resourceName,
        private string $entityClass,
        private mixed $entityId,
        private ?object $entity = null,
    ) {
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): mixed
    {
        return $this->entityId;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }
}
