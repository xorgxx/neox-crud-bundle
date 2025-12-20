<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class NeoxCrudIndexTemplateTest extends TestCase
{
    public function testIndexTemplateUsesSafeTranslationFallback(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/NeoxCrudIndex.tpl.twig';
        self::assertFileExists($tpl);
        $contents = (string) file_get_contents($tpl);

        // Should not rely on null-coalescing operator for translations
        self::assertStringNotContainsString('|trans({}, resource) ??', $contents);

        // Should set keys and compare translated value with key
        self::assertStringContainsString('set __title_key =', $contents);
        self::assertStringContainsString('__title_val != __title_key', $contents);
    }
}
