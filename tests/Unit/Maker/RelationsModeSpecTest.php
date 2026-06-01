<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class RelationsModeSpecTest extends TestCase
{
    public function testConfigurationDeclaresMakersRelationsOptions(): void
    {
        $config = __DIR__ . '/../../../src/DependencyInjection/Configuration.php';
        self::assertFileExists($config);
        $contents = (string) file_get_contents($config);

        self::assertStringContainsString("->arrayNode('makers')", $contents);
        self::assertStringContainsString("->arrayNode('relations')", $contents);
        self::assertStringContainsString("->enumNode('default_render')", $contents);
        self::assertStringContainsString("->arrayNode('choice_label_priority')", $contents);
        self::assertStringContainsString("->booleanNode('nullable_required')", $contents);
        self::assertStringContainsString("->enumNode('order')", $contents);
        self::assertStringContainsString("->booleanNode('group_relations')", $contents);
    }

    public function testMakerImplementsRelationsMixModeKeyBehaviors(): void
    {
        $maker = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($maker);
        $contents = (string) file_get_contents($maker);

        // Detect associations
        self::assertStringContainsString('getAssociationNames()', $contents);
        self::assertStringContainsString('getAssociationMapping', $contents);

        // OneToMany skip (safe default)
        self::assertStringContainsString('OneToMany is skipped by default', $contents);
        self::assertStringContainsString('if ($relationType === 4)', $contents);
        self::assertStringContainsString('continue;', $contents);

        // Autocomplete fallback if UX not installed
        self::assertStringContainsString("default_render", $contents);
        self::assertStringContainsString("AutocompleteEntityType", $contents);
        self::assertStringContainsString("class_exists('Symfony\\UX\\Autocomplete\\Form\\AutocompleteEntityType')", $contents);

        // choice_label detection helper
        self::assertStringContainsString('detectChoiceLabel', $contents);
        self::assertStringContainsString('choice_label_priority', $contents);

        // Interleaved order based on Reflection
        self::assertStringContainsString("order' => 'interleaved'", $contents);
        self::assertStringContainsString('getOrderedPropertyNames', $contents);
        self::assertStringContainsString('getProperties()', $contents);
    }
}
