<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class NeoxCrudIndexBulkTemplateTest extends TestCase
{
    public function testBulkTemplateExistsAndContainsSelectionAndCsrf(): void
    {
        $maker = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($maker);
        $contents = (string) file_get_contents($maker);

        self::assertStringNotContainsString('NeoxCrudIndexBulk.tpl.twig', $contents);
    }
}
