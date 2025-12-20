<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud\EventSubscriber;

use Neox\NeoxCrudBundle\Crud\Event\CrudEntityDeletedEvent;
use Neox\NeoxCrudBundle\Crud\Event\CrudEntitySavedEvent;
use Neox\NeoxCrudBundle\Crud\Notification\CrudNotifierInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Mercure subscriber that pushes CRUD events to topics.
 */
class CrudMercureSubscriber implements EventSubscriberInterface, CrudNotifierInterface
{
    public function __construct(
        private HubInterface $hub,
        private string $topicPrefix = '/crud',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CrudEntitySavedEvent::class   => 'notifyEntitySaved',
            CrudEntityDeletedEvent::class => 'notifyEntityDeleted',
        ];
    }

    public function notifyEntitySaved(CrudEntitySavedEvent $event): void
    {
        try {
            $topicEntity = sprintf('%s/%s/%s', $this->topicPrefix, $event->getResourceName(), $event->getEntityId());
            $topicList   = sprintf('%s/%s', $this->topicPrefix, $event->getResourceName());

            $payload = [
                'type'        => 'entity.saved',
                'resource'    => $event->getResourceName(),
                'entityClass' => $event->getEntityClass(),
                'id'          => $event->getEntityId(),
                'operation'   => $event->getOperation(),
            ];

            $update = new Update(
                [$topicEntity, $topicList],
                json_encode($payload, \JSON_THROW_ON_ERROR)
            );

            $this->hub->publish($update);
        } catch (\Throwable $e) {
            // Optionally log the error; Mercure failures should not break the CRUD flow.
        }
    }

    public function notifyEntityDeleted(CrudEntityDeletedEvent $event): void
    {
        try {
            $topicEntity = sprintf('%s/%s/%s', $this->topicPrefix, $event->getResourceName(), $event->getEntityId());
            $topicList   = sprintf('%s/%s', $this->topicPrefix, $event->getResourceName());

            $payload = [
                'type'        => 'entity.deleted',
                'resource'    => $event->getResourceName(),
                'entityClass' => $event->getEntityClass(),
                'id'          => $event->getEntityId(),
            ];

            $update = new Update(
                [$topicEntity, $topicList],
                json_encode($payload, \JSON_THROW_ON_ERROR)
            );

            $this->hub->publish($update);
        } catch (\Throwable $e) {
            // Optionally log the error; Mercure failures should not break the CRUD flow.
        }
    }
}
