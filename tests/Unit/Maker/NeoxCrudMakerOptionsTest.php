<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class NeoxCrudMakerOptionsTest extends TestCase
{
    public function testMakerDefinesTwigBaseLayoutOption(): void
    {
        $file = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($file);
        $contents = (string) file_get_contents($file);

        self::assertStringContainsString("'twig-base-layout'", $contents);
    }

    public function testMakerDefinesWithControllerOption(): void
    {
        $file = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($file);
        $contents = (string) file_get_contents($file);

        self::assertStringContainsString("'with-controller'", $contents);
    }

    public function testMakerDefinesWithBulkUiOption(): void
    {
        $file = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($file);
        $contents = (string) file_get_contents($file);

        self::assertStringContainsString("'with-bulk-ui'", $contents);
    }
}
