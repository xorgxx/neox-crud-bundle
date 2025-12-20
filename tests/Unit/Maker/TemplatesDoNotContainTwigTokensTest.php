<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class TemplatesDoNotContainTwigTokensTest extends TestCase
{
    public function testPhpTemplatesDoNotContainTwigDelimiters(): void
    {
        $tplDir = __DIR__ . '/../../../src/Maker/tpl';
        self::assertDirectoryExists($tplDir);

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tplDir));
        foreach ($it as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            $path = (string) $file->getPathname();
            if (!str_ends_with($path, '.tpl.php')) {
                continue; // only guard PHP templates
            }
            $content = (string) file_get_contents($path);
            $this->assertStringNotContainsString('{{', $content, 'Twig {{ found in ' . $path);
            $this->assertStringNotContainsString('{%', $content, 'Twig {% found in ' . $path);
        }
    }
}
