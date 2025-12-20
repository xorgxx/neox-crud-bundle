<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class JsonMappingHeuristicTest extends TestCase
{
    public function testMakerContainsJsonArrayHeuristic(): void
    {
        $maker = __DIR__ . '/../../../src/Maker/NeoxCrudMaker.php';
        self::assertFileExists($maker);
        $contents = (string) file_get_contents($maker);

        // JSON default mapping present
        self::assertStringContainsString("'json' => \\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType", $contents);

        // Heuristic for roles / array-typed JSON
        self::assertStringContainsString('if ($doctrineType === \'json\')', $contents);
        self::assertStringContainsString('$fieldName === \'roles\'', $contents);
        self::assertStringContainsString('ReflectionNamedType', $contents);
        self::assertStringContainsString('CollectionType', $contents);
        self::assertStringContainsString("'empty_data' => []", $contents);
    }
}
