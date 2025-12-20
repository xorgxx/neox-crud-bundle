<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Crud;

// Require fixtures (Composer doesn't autoload tests/ by default in this package)
require_once __DIR__ . '/../../Fixtures/Handler/DummyEntity.php';
require_once __DIR__ . '/../../Fixtures/Handler/DummyHandler.php';

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
// keep namespace unique
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tests\Fixtures\Handler\DummyEntity;
use Tests\Fixtures\Handler\DummyHandler;

final class IndexFieldsConfigTest extends TestCase
{
    public function testYamlOverrideIsUsedWhenPresent(): void
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $dispatcher  = $this->createMock(EventDispatcherInterface::class);

        // Metadata is not consulted when YAML is present, but provide a safe default
        $metadata = $this->createMock(ClassMetadata::class);
        $em->method('getClassMetadata')->with(DummyEntity::class)->willReturn($metadata);

        $handler = new DummyHandler($em, $formFactory, $dispatcher);

        $fields = $handler->getIndexFields();

        $this->assertSame(['id', 'name', 'createdAt'], $fields, 'Fields should be read from DummyHandler.yaml');
    }

    public function testDefaultFallsBackToDoctrineMetadataWhenNoYaml(): void
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $dispatcher  = $this->createMock(EventDispatcherInterface::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getFieldNames')->willReturn(['id', 'name', 'createdAt', 'enabled']);
        $em->method('getClassMetadata')->with(DummyEntity::class)->willReturn($metadata);

        $handler = new class ($em, $formFactory, $dispatcher) extends \Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler {
            public function getName(): string
            {
                return 'dummy_no_yaml';
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
        };

        $fields = $handler->getIndexFields();

        $this->assertSame(['name', 'createdAt', 'enabled'], array_values($fields));
        $this->assertNotContains('id', $fields);
    }
}
