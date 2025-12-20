<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;

final class CrudHandlerTemplateTest extends TestCase
{
    public function testCrudHandlerTemplateEmitsImportsAndClassConstants(): void
    {
        $tpl = __DIR__ . '/../../../src/Maker/tpl/CrudHandler.tpl.php';
        self::assertFileExists($tpl);

        $resource        = 'test';
        $entity_class    = 'App\\Entity\\Test';
        $form_type       = 'App\\Form\\TestType';
        $template_prefix = 'admin/test';
        $class_name      = 'TestCrudHandler';
        // Template now expects a dynamic namespace provided by Maker's Generator
        $namespace = 'Neox\\NeoxCrudBundle\\Crud\\Handle\\Test';

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $tpl; // variables above are used by the template
        $out = (string) ob_get_clean();

        // Opening tag + namespace (fixed path: Crud/Handle/<Entity>/...)
        self::assertStringContainsString('namespace Neox\\NeoxCrudBundle\\Crud\\Handle\\Test;', $out);

        // Imports
        self::assertStringContainsString('use App\\Entity\\Test;', $out);
        self::assertStringContainsString('use App\\Form\\TestType;', $out);

        // Methods returning fully-qualified ::class (leading backslash)
        self::assertStringContainsString('return \\App\\Entity\\Test::class;', $out);
        self::assertStringContainsString('return \\App\\Form\\TestType::class;', $out);

        // Template prefix
        self::assertStringContainsString("return 'admin/test';", $out);
    }
}
