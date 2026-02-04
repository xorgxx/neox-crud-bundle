<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Maker;

use Neox\NeoxCrudBundle\Maker\Contract\DoctrineEntityHelperInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * make:neox:crud-maker
 *
 * Generates:
 *  - a FormType
 *  - a CrudHandler
 *  - basic Twig templates
 *  - optional translation YAML
 *
 * It supports a safe behavior when a FormType already exists:
 *  - The existing FormType is NEVER modified.
 *  - A suggested version (with fields, translations, associations if enabled)
 *    is written to "src/Form/FooType.php.sav" for manual merge.
 */
class NeoxCrudMaker extends AbstractMaker
{
    private ?string $templatesNamespace = null;
    private ?string $defaultBaseLayout  = null;

    /**
     * @param string[] $fieldKeys
     * @param array<string,string> $patterns
     */
    public function __construct(private DoctrineEntityHelperInterface $doctrineHelper, private array $fieldKeys = [
        'label',
        'placeholder',
        'help'
    ], private array $patterns = [], public readonly array $makers = [])
    {
    }

    public static function getCommandName(): string
    {
        return 'make:neox:crud-maker';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a full CRUD (handler + form + templates + optional translations) powered by NeoxCrudBundle.';
    }

    /**
     * Declare optional dependencies required by this maker.
     *
     * No additional packages required besides MakerBundle for this generator.
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // Intentionally left blank.
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command->setHelp(<<<'HELP'
This maker generates a full CRUD for an entity, using the generic Neox CRUD handler system.

Options overview and examples:

- --slug=<slug>
    Preferred resource slug. Defaults to the lowercased short entity name.
    Example: php bin/console make:neox:crud-maker Product --slug=catalog-item

- --resource=<slug> [DEPRECATED]
    Legacy alias for --slug, still accepted for backward compatibility.

- --with-trans
    Also generate a translation YAML for this resource.
    Example: php bin/console make:neox:crud-maker Product --with-trans --locale=en

- --locale=<code>
    Locale for the generated translation file (used only with --with-trans). Default: fr

- --twig-namespace=<NamespaceOrPath>
    Either a Twig namespace (e.g. Admin, NeoxCrud) or a full Twig template path
    (e.g. @Admin/Partial/_layout.html.twig). Used to resolve the base layout to extend.

- --twig-base-layout=<path>
    Explicit Twig base layout path (e.g. @App/admin/_layout.html.twig or /admin/_layout.html.twig).
    Takes precedence over --twig-namespace and configuration.

- --with-controller
    Generate a dedicated controller class extending GenericCrudController for this resource.
    Default: disabled. If omitted, the generic controller + routes can still serve the CRUD.
    Example:
      php bin/console make:neox:crud-maker Product --with-controller

- --enable-live-table
    Enable LiveTable for this resource by generating the handler config.yaml with
    neox_crud.live_table enabled (per handler, not global).

Typical usages:
  php bin/console make:neox:crud-maker Product
  php bin/console make:neox:crud-maker Product --slug=catalog-item --with-trans --locale=en
  php bin/console make:neox:crud-maker Product --twig-namespace=NeoxCrud
  php bin/console make:neox:crud-maker Product --twig-base-layout=/admin/_layout.html.twig
  php bin/console make:neox:crud-maker Product --with-controller

Expose routes (so the generic CRUD controller is reachable):
  In your config/routes/neox_crud.yaml:

  neox_crud:
      resource: '@NeoxCrudBundle/Controller/'
      type: attribute
      prefix: /

Open your CRUD page in the browser:
  http://localhost/admin/<resource>
  Example for Product (slug "product"): http://localhost/admin/product
HELP)
                ->addArgument('entity-class', InputArgument::REQUIRED, 'Entity class (FQCN or shortcut, e.g. App\Entity\Product or Product)')
            // New explicit option name (preferred)
                ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'Resource slug (preferred) (default: entity shortname lowercased).', null)
            // Legacy option kept for BC
                ->addOption('resource', null, InputOption::VALUE_OPTIONAL, '[Deprecated] Resource slug (use --slug instead).', null)
                ->addOption('with-trans', null, InputOption::VALUE_NONE, 'Generate a translation file for this CRUD.')
                ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Locale for the translation file (when --with-trans).', 'fr')
                ->addOption('twig-namespace', null, InputOption::VALUE_OPTIONAL, 'Twig namespace to use for base layout extends (overrides configuration).', null)
                ->addOption('twig-base-layout', null, InputOption::VALUE_OPTIONAL, 'Explicit Twig base layout path (e.g. \'@App/admin/_layout.html.twig\' or \'/admin/_layout.html.twig\'). Overrides configuration.', null)
                ->addOption('with-controller', null, InputOption::VALUE_NONE, 'Generate a dedicated controller extending GenericCrudController (disabled by default).')
                ->addOption('enable-live-table', null, InputOption::VALUE_NONE, 'Enable LiveTable in the generated handler config.yaml (per resource).')
                ->addOption('with-bulk-ui', null, InputOption::VALUE_NONE, 'Include selection column + bulk actions UI in index template (disabled by default).');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        // Keep it simple: everything via arguments/options.
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassInput = (string)$input->getArgument('entity-class');

        $enableLiveTable = (bool) $input->getOption('enable-live-table');

        if (!str_contains($entityClassInput, '\\')) {
            $entityClass = $this->doctrineHelper->getEntityNamespace() . '\\' . $entityClassInput;
        } else {
            $entityClass = $entityClassInput;
        }

        $entityMetadata  = $this->doctrineHelper->getMetadata($entityClass);
        $entityShortName = $entityMetadata->getReflectionClass()
                                          ->getShortName();

        // Prefer the new --slug option; fallback to legacy --resource for BC
        $slugOption     = $input->getOption('slug');
        $resourceOption = $input->getOption('resource');
        $chosen         = $slugOption ?? $resourceOption;
        $resourceSlug   = $chosen ? strtolower((string)$chosen) : strtolower($entityShortName);

        $formClassNameDetails = $generator->createClassNameDetails($entityShortName . 'Type', 'Form\\');

        $formFqcn       = $formClassNameDetails->getFullName();
        $formShort      = $formClassNameDetails->getShortName();
        $templatePrefix = 'admin/' . $resourceSlug;

        // Determine Twig namespace from CLI or configuration (if any)
        $twigNamespace = $input->getOption('twig-namespace') ?? $this->makers['templates_namespace'];

        // Precedence for base layout:
        // 1) CLI --twig-base-layout
        // 2) Config makers.base_layout injected into $this->defaultBaseLayout
        // 3) If twig-namespace provided: derive @<ns>/admin/_layout.html.twig
        // 4) Fallback to '/admin/_layout.html.twig'
        $explicitBaseLayout = $input->getOption('twig-base-layout');
        $twigBaseLayout     = null;
        if ($explicitBaseLayout) {
            $twigBaseLayout = (string)$explicitBaseLayout;
        } elseif ($this->makers['base_layout']) {
            $twigBaseLayout = $this->makers['base_layout'];
        } elseif ($twigNamespace) {
            $raw               = (string)$twigNamespace;
            $looksLikeFullPath = str_starts_with($raw, '@') || str_ends_with($raw, '.html.twig') || str_contains($raw, '/');
            $twigBaseLayout    = $looksLikeFullPath ? $raw : ('@' . $raw . '/admin/_layout.html.twig');
        } else {
            $twigBaseLayout = '/admin/_layout.html.twig';
        }

        // Fields: all simple fields except the id
        $fieldNames = array_filter($entityMetadata->getFieldNames(), static fn (string $field): bool => $field !== 'id');

        // 1) Generate FormType – with .sav behavior
        $projectRoot      = $generator->getRootDirectory();
        $formPath         = 'src/Form/' . $formShort . '.php';
        $absoluteFormPath = $projectRoot . '/' . $formPath;

        // Build form fields with auto-guessed Symfony Form Types based on Doctrine metadata
        $formFields = [];
        foreach ($fieldNames as $fieldName) {
            $doctrineType = $entityMetadata->getTypeOfField($fieldName);
            $formType     = match ($doctrineType) {
                // strings
                'string' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
                'text'   => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',

                // numbers
                'integer', 'smallint', 'bigint' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType',
                'float', 'decimal' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\NumberType',

                // booleans
                'boolean' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',

                // dates/times
                'date', 'date_immutable' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\DateType',
                'datetime', 'datetimetz', 'datetime_immutable' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType',
                'time', 'time_immutable' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TimeType',

                // JSON default as textarea (raw JSON editing) – may be overridden below based on PHP property type
                // default mapping reference (for tests): 'json' => \Symfony\Component\Form\Extension\Core\Type\TextareaType
                'json' => \Symfony\Component\Form\Extension\Core\Type\TextareaType::class,

                // Doctrine arrays -> CollectionType with text entries
                'array', 'simple_array' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType',

                // uuid/guid (fallback to text to avoid extra deps)
                'uuid', 'guid' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',

                default => null,
            };

            $options = [];
            if ($doctrineType === 'array' || $doctrineType === 'simple_array') {
                // default to simple strings entries; developers can adjust afterwards
                $options = [
                    'entry_type'   => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    // ensure empty submit results in [] and not null
                    'empty_data' => [],
                ];
            }

            // Heuristic: if a JSON field is actually represented as a PHP array (very common for roles),
            // prefer a CollectionType to avoid null/string submission. Conservative fallback on field name 'roles'.
            if ($doctrineType === 'json') {
                $propertyIsArray = false;
                $reflClass       = $entityMetadata->getReflectionClass();
                if ($reflClass->hasProperty($fieldName)) {
                    $prop = $reflClass->getProperty($fieldName);
                    $type = $prop->getType();
                    if ($type instanceof \ReflectionNamedType && $type->getName() === 'array') {
                        $propertyIsArray = true;
                    }
                }

                if ($propertyIsArray || $fieldName === 'roles') {
                    $formType = '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType';
                    $options  = [
                        'entry_type'   => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
                        'allow_add'    => true,
                        'allow_delete' => true,
                        'by_reference' => false,
                        'empty_data'   => [],
                    ];
                }
            }

            // If boolean and nullable in Doctrine, make the field not required to avoid validation hiccups
            if ($doctrineType === 'boolean') {
                // Doctrine\ORM\Mapping\ClassMetadata has getFieldMapping(),
                // but Doctrine\Persistence\Mapping\ClassMetadata (interface) does not declare it.
                // Guard the call for compatibility across versions/drivers.
                if (\method_exists($entityMetadata, 'getFieldMapping')) {
                    /** @var array<string, mixed> $mapping */
                    $mapping = $entityMetadata->getFieldMapping($fieldName);
                    if (isset($mapping['nullable']) && $mapping['nullable'] === true) {
                        $options['required'] = false;
                    }
                }
            }

            $formFields[] = [
                'name' => $fieldName,
                'type' => $formType,
                // FQCN with leading backslash or null
                'options' => $options,
            ];
        }

        $formContext = [
            'entity_class'    => $entityClass,
            'form_class_name' => $formShort,
            'fields'          => $fieldNames,
            // kept for other templates (index, translations)
            'form_fields' => $formFields,
            // used by FormType template for explicit field types
            'resource'   => $resourceSlug,
            'field_keys' => $this->fieldKeys,
        ];

        if (file_exists($absoluteFormPath)) {
            // Do NOT touch the existing FormType.
            // Instead, generate a suggested version as .php.sav from our template stub
            $savPath = $formPath . '.sav';
            $generator->generateFile($savPath, __DIR__ . '/tpl/NeoxCrudFormType.tpl.php', $formContext);

            $io->note(sprintf('FormType "%s" already exists. A suggested version has been written to "%s".', $formFqcn, $savPath));
        } else {
            // No existing FormType: generate it normally
            $generator->generateClass($formClassNameDetails->getFullName(), __DIR__ . '/tpl/NeoxCrudFormType.tpl.php', $formContext);
        }

        // 2) Generate handler
        // Fixed path as requested: Crud/Handle/<Entity>/<Entity>CrudHandler.php
        $handlerNamespacePrefix  = 'Crud\\Handle\\' . $entityShortName . '\\';
        $handlerClassNameDetails = $generator->createClassNameDetails($entityShortName . 'CrudHandler', $handlerNamespacePrefix);

        $generator->generateClass($handlerClassNameDetails->getFullName(), __DIR__ . '/tpl/CrudHandler.tpl.php', [
                'resource'        => $resourceSlug,
                'entity_class'    => $entityClass,
                'form_type'       => $formFqcn,
                'template_prefix' => $templatePrefix,
                'class_name'      => $handlerClassNameDetails->getShortName(),
            ]);

        // Also emit a commented per-handler config.yaml next to the handler (idempotent)
        $handlerDir = 'src' . DIRECTORY_SEPARATOR . 'Crud' . DIRECTORY_SEPARATOR . 'Handle' . DIRECTORY_SEPARATOR . $entityShortName;
        $configPath = $handlerDir . DIRECTORY_SEPARATOR . 'config.yaml';
        if (!file_exists($configPath)) {
            $generator->generateFile(
                $configPath,
                __DIR__ . '/tpl/HandlerConfig.yaml.tpl',
                [
                    'resource'   => $resourceSlug,
                    'class_name' => $handlerClassNameDetails->getShortName(),
                    // Suggest all detected entity fields in comments for quick start
                    'available_fields' => $fieldNames,
                    'enable_live_table' => $enableLiveTable,
                ]
            );
        }

        // 3) Optional dedicated controller generation
        if ((bool) $input->getOption('with-controller')) {
            $controllerClassNameDetails = $generator->createClassNameDetails($entityShortName . 'CrudController', 'Controller\\');

            $generator->generateClass($controllerClassNameDetails->getFullName(), __DIR__ . '/tpl/NeoxCrudController.tpl.php', [
                'resource'   => $resourceSlug,
                'class_name' => $controllerClassNameDetails->getShortName(),
            ]);
        }

        // 4) Optional translation file
        if ($input->getOption('with-trans')) {
            $locale    = (string)$input->getOption('locale');
            $transPath = sprintf('translations/%s.%s.yaml', $resourceSlug, $locale);

            $labelBase = ucfirst($resourceSlug);

            $lines   = [];
            $lines[] = sprintf('%s:', $resourceSlug);
            $lines[] = '  title:';
            $lines[] = sprintf("    index: 'Liste des %ss'", $labelBase);
            $lines[] = sprintf("    new: 'Nouveau %s'", $labelBase);
            $lines[] = sprintf("    edit: 'Modifier %s'", $labelBase);
            $lines[] = '  field:';
            foreach ($fieldNames as $field) {
                $lines[] = sprintf('    %s:', $field);
                $pretty  = ucfirst(str_replace('_', ' ', $field));

                foreach ($this->fieldKeys as $key) {
                    if ($key === 'label') {
                        $value = $pretty;
                    } else {
                        $pattern = $this->patterns[ $key ] ?? '';
                        if ($pattern !== '') {
                            $value = strtr($pattern, [
                                '%field%'       => $field,
                                '%field_label%' => $pretty,
                                '%resource%'    => $resourceSlug,
                            ]);
                        } else {
                            $value = '';
                        }
                    }
                    $lines[] = sprintf("      %s: '%s'", $key, str_replace("'", "\\'", $value));
                }
            }
            $lines[] = '  action:';
            $lines[] = "    new: 'Nouveau'";
            $lines[] = "    edit: 'Éditer'";
            $lines[] = "    delete: 'Supprimer'";

            // Write raw YAML content for translations (do not treat as a Twig template)
            $generator->dumpFile($transPath, implode("\n", $lines) . "\n");
        }

        $generator->writeChanges();

        $io->success(sprintf('Neox CRUD generated for %s', $entityClass));
        $io->text([
            'Routes (via GenericCrudController) will be available as:',
            sprintf('- /admin/%s', $resourceSlug),
            sprintf('- /admin/%s/new', $resourceSlug),
            sprintf('- /admin/%s/{id}/edit', $resourceSlug),
        ]);

        if ($enableLiveTable) {
            $io->text('LiveTable: enabled in the generated handler config.yaml (option --enable-live-table).');
        } else {
            $io->text('LiveTable: to enable it, uncomment the neox_crud.live_table block in the handler config.yaml (or rerun with --enable-live-table).');
        }
    }

    /**
     * Setter used by DI to inject default templates namespace from configuration.
     * Keep it optional to avoid BC impact when not configured.
     */
    public function setTemplatesNamespace(?string $templatesNamespace): void
    {
        $this->templatesNamespace = $templatesNamespace ?: null;
    }

    /**
     * Inject default base layout from configuration (makers.base_layout).
     */
    public function setBaseLayout(?string $baseLayout): void
    {
        $this->defaultBaseLayout = $baseLayout ?: null;
    }
}
