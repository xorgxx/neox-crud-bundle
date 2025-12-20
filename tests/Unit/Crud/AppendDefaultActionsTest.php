<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Crud;

use Doctrine\ORM\EntityManagerInterface;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;

final class AppendDefaultActionsTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        parent::setUp();
        // The handler will look for config.yaml next to the class file (this test file directory)
        $this->configPath = __DIR__ . '/config.yaml';
        if (file_exists($this->configPath)) {
            @unlink($this->configPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configPath)) {
            @unlink($this->configPath);
        }
        parent::tearDown();
    }

    private function makeHandler(): AbstractDoctrineCrudHandler
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $dispatcher  = $this->createMock(EventDispatcherInterface::class);

        // Define the handler as an anonymous class inside this file so its directory matches $this->configPath
        return new class ($em, $formFactory, $dispatcher) extends AbstractDoctrineCrudHandler {
            public function __construct(EntityManagerInterface $em, FormFactoryInterface $formFactory, EventDispatcherInterface $dispatcher)
            {
                parent::__construct($em, $formFactory, $dispatcher);
            }

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
                return \stdClass::class;
            }
            public function getTemplatePrefix(): string
            {
                return 'admin/test';
            }
        };
    }

    private function writeConfig(string $yaml): void
    {
        file_put_contents($this->configPath, $yaml);
        self::assertFileExists($this->configPath, 'Failed to create temporary YAML config for the test');
    }

    public function testAppendsEditAndDeleteWhenMissing(): void
    {
        // Only a custom action, defaults should be appended after
        $this->writeConfig(<<<YAML
neox_crud:
  actions:
    - { name: view, label: "Voir", route: foo_show, method: GET, params: { id: "entity.id" } }
  append_default_actions: true
YAML);

        $handler = $this->makeHandler();
        $entity  = new class () {
            public function getId()
            {
                return 123;
            }
        };

        $actions = $handler->getRowActionsFor($entity, []);
        // Expect at least 3 actions: view + edit + delete
        self::assertIsArray($actions);
        self::assertGreaterThanOrEqual(3, count($actions));
        self::assertArrayHasKey(0, $actions);
        self::assertIsArray($actions[0]);
        self::assertArrayHasKey('name', $actions[0]);
        self::assertSame('view', $actions[0]['name']);

        $names = array_map(static function ($a) {
            return is_array($a) && array_key_exists('name', $a) ? $a['name'] : null;
        }, $actions);
        self::assertContains('edit', $names, 'Default edit should be appended');
        self::assertContains('delete', $names, 'Default delete should be appended');

        // Check default action shapes
        $edit   = null;
        $delete = null;
        foreach ($actions as $a) {
            if (!is_array($a) || !array_key_exists('name', $a)) {
                continue;
            }
            if ($a['name'] === 'edit') {
                $edit = $a;
            }
            if ($a['name'] === 'delete') {
                $delete = $a;
            }
        }
        self::assertNotNull($edit);
        self::assertNotNull($delete);
        self::assertIsArray($edit);
        self::assertIsArray($delete);
        self::assertArrayHasKey('method', $edit);
        self::assertArrayHasKey('method', $delete);
        self::assertArrayHasKey('route', $edit);
        self::assertArrayHasKey('route', $delete);
        self::assertSame('GET', $edit['method']);
        self::assertSame('DELETE', $delete['method']);
        self::assertSame('neox_crud_admin_crud_edit', $edit['route']);
        self::assertSame('neox_crud_admin_crud_delete', $delete['route']);
        self::assertArrayHasKey('params', $edit);
        self::assertArrayHasKey('params', $delete);
        self::assertIsArray($edit['params']);
        self::assertIsArray($delete['params']);
        self::assertArrayHasKey('id', $edit['params']);
        self::assertArrayHasKey('id', $delete['params']);
        self::assertSame(123, $edit['params']['id']);
        self::assertSame(123, $delete['params']['id']);
    }

    public function testDoesNotDuplicateIfDeveloperDefinesEditOrDelete(): void
    {
        // Developer already provided an edit action; only delete should be appended
        $this->writeConfig(<<<YAML
neox_crud:
  actions:
    - { name: edit, label: "Modifier", route: neox_crud_admin_crud_edit, method: GET, params: { id: "entity.id" } }
  append_default_actions: true
YAML);

        $handler = $this->makeHandler();
        $entity  = new class () {
            public function getId()
            {
                return 7;
            }
        };

        $actions = $handler->getRowActionsFor($entity, []);
        $this->assertIsArray($actions);
        $names = array_map(static function ($a) {
            return is_array($a) && array_key_exists('name', $a) ? $a['name'] : null;
        }, $actions);

        // Only one edit, and delete appended
        self::assertSame(1, count(array_filter($names, static fn ($n) => $n === 'edit')));
        self::assertContains('delete', $names);
    }
}
