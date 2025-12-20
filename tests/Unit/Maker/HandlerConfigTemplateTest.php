<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class HandlerConfigTemplateTest extends TestCase
{
    public function testHandlerConfigTemplateExistsAndContainsComments(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/HandlerConfig.yaml.tpl';
        self::assertFileExists($tpl);

        ob_start();
        // Minimal variables used by the template
        $resource   = 'product';
        $class_name = 'ProductCrudHandler';
        /** @noinspection PhpIncludeInspection */
        include $tpl;
        $out = (string) ob_get_clean();

        self::assertStringContainsString('# NeoxCrud — Per-handler configuration (optional)', $out);
        self::assertStringContainsString('<HandlerDir>/config.yaml', $out);
        self::assertStringContainsString("# index_fields: ['id', 'name', 'createdAt']", $out);
        self::assertStringContainsString('# neox_crud:', $out);
        self::assertStringContainsString('Handler : ProductCrudHandler', $out);
    }

    public function testTemplateRendersDetectedFieldsWhenProvided(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/HandlerConfig.yaml.tpl';
        self::assertFileExists($tpl);

        ob_start();
        // Variables used by the template
        $resource         = 'book';
        $class_name       = 'BookCrudHandler';
        $available_fields = ['id', 'title', 'createdAt'];
        /** @noinspection PhpIncludeInspection */
        include $tpl;
        $out = (string) ob_get_clean();

        self::assertStringContainsString('# Detected entity fields (Doctrine):', $out);
        self::assertStringContainsString('# - id', $out);
        self::assertStringContainsString('# - title', $out);
        self::assertStringContainsString('# Quick start — uncomment to use all fields as-is:', $out);
        self::assertStringContainsString("# index_fields: ['id', 'title', 'createdAt']", $out);
    }
}
