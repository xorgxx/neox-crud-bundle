<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Crud;

// Require fixtures
require_once __DIR__ . '/../../Fixtures/Handler/DummyEntity.php';
require_once __DIR__ . '/../../Fixtures/Handler/OptionsHandler.php';

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tests\Fixtures\Handler\DummyEntity;
use Tests\Fixtures\Handler\OptionsHandler;

final class UiActionsConfigTest extends TestCase
{
    private function createHandler(): AbstractDoctrineCrudHandler
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $dispatcher  = $this->createMock(EventDispatcherInterface::class);

        // Not used because YAML is present; still provide a stub
        $metadata = $this->createMock(ClassMetadata::class);
        $em->method('getClassMetadata')->willReturn($metadata);

        return new OptionsHandler($em, $formFactory, $dispatcher);
    }

    public function testToolbarAndBulkActionsAreParsed(): void
    {
        $handler = $this->createHandler();

        $toolbar = $handler->getToolbarButtons();
        $this->assertIsArray($toolbar);
        $this->assertNotEmpty($toolbar, 'Toolbar buttons should be parsed from YAML');
        $this->assertArrayHasKey(0, $toolbar);
        $firstToolbar = $toolbar[0];
        $this->assertIsArray($firstToolbar);
        $this->assertArrayHasKey('name', $firstToolbar);
        $this->assertSame('export_csv', $firstToolbar['name']);

        $bulk = $handler->getBulkActions();
        $this->assertIsArray($bulk);
        $this->assertNotEmpty($bulk, 'Bulk actions should be parsed from YAML');
        $this->assertArrayHasKey(0, $bulk);
        $firstBulk = $bulk[0];
        $this->assertIsArray($firstBulk);
        $this->assertArrayHasKey('name', $firstBulk);
        $this->assertSame('bulk_delete', $firstBulk['name']);
        $this->assertArrayHasKey('selection_required', $firstBulk);
        $this->assertTrue($firstBulk['selection_required']);
    }

    public function testRowActionsResolutionAndOrdering(): void
    {
        $handler = $this->createHandler();

        $entity     = new DummyEntity();
        $entity->id = 123;

        $row = $handler->getRowActionsFor($entity);
        $this->assertIsArray($row);
        // Priority 10 (edit) should appear before 5 (delete)
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey(1, $row);
        $this->assertIsArray($row[0]);
        $this->assertIsArray($row[1]);
        $this->assertArrayHasKey('name', $row[0]);
        $this->assertArrayHasKey('name', $row[1]);
        $this->assertSame(['edit', 'delete'], array_map(static function ($a) {
            return is_array($a) && array_key_exists('name', $a) ? $a['name'] : null;
        }, $row));

        // Params should be resolved from entity.id
        $edit = $row[0];
        $this->assertIsArray($edit);
        $this->assertArrayHasKey('params', $edit);
        $this->assertIsArray($edit['params']);
        $this->assertArrayHasKey('id', $edit['params']);
        $this->assertSame(123, $edit['params']['id']);
    }
}
