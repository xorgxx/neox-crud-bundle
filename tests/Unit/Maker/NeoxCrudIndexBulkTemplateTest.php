<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class NeoxCrudIndexBulkTemplateTest extends TestCase
{
    public function testBulkTemplateExistsAndContainsSelectionAndCsrf(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/NeoxCrudIndexBulk.tpl.twig';
        self::assertFileExists($tpl);
        $contents = (string) file_get_contents($tpl);

        // Header select all checkbox
        self::assertStringContainsString('id="_select_all"', $contents);
        // Row selection checkboxes
        self::assertStringContainsString('name="ids[]"', $contents);
        // Bulk CSRF token pattern
        self::assertStringContainsString("csrf_token('bulk_'", $contents);
        // Ensure it keeps the same safe translation fallback pattern
        self::assertStringContainsString('__title_val != __title_key', $contents);
    }
}
