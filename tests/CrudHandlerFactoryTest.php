<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Crud;

use Neox\NeoxCrudBundle\Crud\CrudHandlerFactory;
use Neox\NeoxCrudBundle\Crud\CrudHandlerInterface;
use PHPUnit\Framework\TestCase;

class CrudHandlerFactoryTest extends TestCase
{
    public function testGetReturnsRegisteredHandler(): void
    {
        $handler = new class () implements CrudHandlerInterface {
            public function getName(): string
            {
                return 'test';
            }
            public function getEntityClass(): string
            {
                return \stdClass::class;
            }
            public function getFormType(): string
            {
                return 'form';
            }
            public function getTemplatePrefix(): string
            {
                return 'tpl';
            }
            public function getIndexFields(): array
            {
                return [];
            }
            public function findAll(): iterable
            {
                return [];
            }
            public function findList(\Symfony\Component\HttpFoundation\Request $request): iterable
            {
                return [];
            }
            public function find(int|string $id): ?object
            {
                return new \stdClass();
            }
            public function createEntity(): object
            {
                return new \stdClass();
            }
            public function createForm(object $entity): \Symfony\Component\Form\FormInterface
            {
                throw new \RuntimeException('not needed');
            }
            public function handleForm(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\Form\FormInterface $form): bool
            {
                return false;
            }
            public function save(object $entity): void
            {
            }
            public function delete(object $entity): void
            {
            }
            public function preCreate(object $entity, \Symfony\Component\HttpFoundation\Request $request): void
            {
            }
            public function preUpdate(object $entity, \Symfony\Component\HttpFoundation\Request $request): void
            {
            }
            public function preDelete(object $entity, \Symfony\Component\HttpFoundation\Request $request): void
            {
            }
            public function getRedirectAfterCreate(object $entity): array
            {
                return ['route', []];
            }
            public function getRedirectAfterUpdate(object $entity): array
            {
                return ['route', []];
            }
            public function getRedirectAfterDelete(object $entity): array
            {
                return ['route', []];
            }
            public function getRedirectAfterAction(string $action, object $entity): array
            {
                return ['route', []];
            }
            public function supportsAction(string $action, string $method): bool
            {
                return false;
            }
            public function handleAction(
                string $action,
                int|string $id,
                \Symfony\Component\HttpFoundation\Request $request,
                \Symfony\Bundle\FrameworkBundle\Controller\AbstractController $controller
            ): \Symfony\Component\HttpFoundation\Response {
                throw new \RuntimeException('not needed');
            }
        };

        $factory = new CrudHandlerFactory([$handler]);
        $this->assertSame($handler, $factory->get('test'));
    }
}
