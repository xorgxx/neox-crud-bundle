<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud\Notification;

use Neox\NeoxCrudBundle\Crud\Event\CrudEntityDeletedEvent;
use Neox\NeoxCrudBundle\Crud\Event\CrudEntitySavedEvent;

/**
 * Abstraction for broadcasting CRUD events.
 *
 * Default implementation is Mercure-based, but applications can replace it.
 */
interface CrudNotifierInterface
{
    public function notifyEntitySaved(CrudEntitySavedEvent $event): void;

    public function notifyEntityDeleted(CrudEntityDeletedEvent $event): void;
}
