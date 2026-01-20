<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class NeoxCrudIndexTemplateTest extends TestCase
{
    public function testIndexTemplateUsesSafeTranslationFallback(): void
    {
        $maker = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($maker);
        $contents = (string) file_get_contents($maker);

        self::assertStringNotContainsString('NeoxCrudIndex.tpl.twig', $contents);
    }
}
