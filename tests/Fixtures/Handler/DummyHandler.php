<?php

declare(strict_types=1);

namespace Tests\Fixtures\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DummyHandler extends AbstractDoctrineCrudHandler
{
    public function __construct(
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($em, $formFactory, $dispatcher);
    }

    public function getName(): string
    {
        return 'dummy';
    }

    public function getEntityClass(): string
    {
        return DummyEntity::class;
    }

    public function getFormType(): string
    {
        return 'dummy_form';
    }

    public function getTemplatePrefix(): string
    {
        return 'admin/dummy';
    }
}
