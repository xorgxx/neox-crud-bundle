<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after an entity is saved (create or update).
 */
class CrudEntitySavedEvent extends Event
{
    public const OPERATION_CREATE = 'create';
    public const OPERATION_UPDATE = 'update';

    public function __construct(
        private string $resourceName,
        private string $entityClass,
        private mixed $entityId,
        private object $entity,
        private string $operation,
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

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function isCreate(): bool
    {
        return $this->operation === self::OPERATION_CREATE;
    }

    public function isUpdate(): bool
    {
        return $this->operation === self::OPERATION_UPDATE;
    }
}
