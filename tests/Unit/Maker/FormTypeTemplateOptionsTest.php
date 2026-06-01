<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class FormTypeTemplateOptionsTest extends TestCase
{
    public function testFormTypeTemplateHandlesArrayOptionEmission(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/NeoxCrudFormType.tpl.php';
        self::assertFileExists($tpl);
        $contents = (string) file_get_contents($tpl);

        // Ensure the template is capable of emitting array literals for options (including non-empty arrays)
        self::assertStringContainsString('is_array($optValue)', $contents);
        self::assertStringContainsString('$emitArray', $contents);
        self::assertStringContainsString("return '[' . implode", $contents);
    }
}
