<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class BaseLayoutResolutionTest extends TestCase
{
    public function testIndexTemplateUsesTwigBaseLayoutVariable(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/NeoxCrudIndex.tpl.twig';
        self::assertFileExists($tpl);
        $contents = (string) file_get_contents($tpl);

        self::assertStringContainsString('twig_base_layout', $contents);
        self::assertStringContainsString('{% extends base_layout %}', $contents);
        self::assertStringContainsString("'/admin/_layout.html.twig'", $contents, 'Default fallback should remain /admin/_layout.html.twig');
    }

    public function testFormTemplateUsesTwigBaseLayoutVariable(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/NeoxCrudForm.tpl.twig';
        self::assertFileExists($tpl);
        $contents = (string) file_get_contents($tpl);

        self::assertStringContainsString('twig_base_layout', $contents);
        self::assertStringContainsString('{% extends base_layout %}', $contents);
        self::assertStringContainsString("'/admin/_layout.html.twig'", $contents, 'Default fallback should remain /admin/_layout.html.twig');
    }
}
