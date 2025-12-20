<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Crud;

// Require fixtures
require_once __DIR__ . '/../../Fixtures/Handler/DummyEntity.php';
require_once __DIR__ . '/../../Fixtures/Handler/OptionsHandler.php';

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tests\Fixtures\Handler\DummyEntity;
use Tests\Fixtures\Handler\OptionsHandler;

final class IndexFieldOptionsParsingTest extends TestCase
{
    public function testParsesNamesAndOptionsFromYaml(): void
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $dispatcher  = $this->createMock(EventDispatcherInterface::class);

        // Not used because YAML is present; still provide a stub
        $metadata = $this->createMock(ClassMetadata::class);
        $em->method('getClassMetadata')->with(DummyEntity::class)->willReturn($metadata);

        $handler = new OptionsHandler($em, $formFactory, $dispatcher);

        $fields = $handler->getIndexFields();
        $this->assertSame(
            ['id', 'title', 'enabled', 'imagePath', 'createdAt', 'roles', 'secret'],
            $fields,
            'Field order should follow YAML, with names extracted from map entries.'
        );

        $opts = $handler->getIndexFieldOptions();

        // id has no options
        $this->assertArrayHasKey('id', $opts);
        $this->assertSame([], $opts['id']);

        $this->assertSame(['format' => 'text'], $opts['title']);
        $this->assertSame(['boolean_icon' => true], $opts['enabled']);
        $this->assertSame(['type' => 'image', 'class' => 'thumb-48'], $opts['imagePath']);
        $this->assertSame(['format' => 'Y-m-d'], $opts['createdAt']);
        $this->assertSame(['voters' => ['ROLE_ADMIN', 'ROLE_MANAGER']], $opts['roles']);
        $this->assertSame(['voters' => ['ROLE_SUPER_ADMIN']], $opts['secret'], 'Single voter alias should be normalized to voters array.');
    }
}
