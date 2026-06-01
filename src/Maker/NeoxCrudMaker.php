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

    /** @var array<string, array{name: string, type: string|null, options: array<string, mixed>}> */
    private array $relationFormFieldsByName = [];

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

    /** @var array<int, array{entity_class: string, form_type_class: string}> */
    private array $formTypesToGenerate = [];

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
                ->addOption('enable-live-table', null, InputOption::VALUE_NONE, 'Enable LiveTable in the generated handler config.yaml (per resource).');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        // --- Interactive prompts for options not already passed as CLI flags ---

        if (!$input->getOption('with-trans')) {
            $withTrans = $io->confirm('Générer un fichier de traductions ?', false);
            $input->setOption('with-trans', $withTrans);
            if ($withTrans) {
                $locale = $io->ask('Langue (locale)', $input->getOption('locale') ?: 'fr');
                $input->setOption('locale', $locale);
            }
        }

        if (!$input->getOption('with-controller')) {
            $input->setOption('with-controller', $io->confirm('Générer un contrôleur dédié (GenericCrudController) ?', false));
        }

        if (!$input->getOption('enable-live-table')) {
            $input->setOption('enable-live-table', $io->confirm('Activer le LiveTable ?', false));
        }

        // ---------------------------------------------------------------

        $entityClass = $this->resolveEntityClass((string) $input->getArgument('entity-class'));
        $entityMetadata = $this->doctrineHelper->getMetadata($entityClass);

        $relationsConfig = $this->getRelationsConfig();
        $mode = (string) ($relationsConfig['mode'] ?? 'mix');
        $defaultRender = (string) ($relationsConfig['default_render'] ?? 'select');
        $choiceLabelPriority = $relationsConfig['choice_label_priority'] ?? ['name', 'title', 'label', 'id'];

        $associationNames = $entityMetadata->getAssociationNames();
        if ($associationNames === []) {
            return;
        }

        foreach ($associationNames as $assocName) {
            $mapping = $entityMetadata->getAssociationMapping($assocName);
            $relationType = (int) ($mapping['type'] ?? 0);

            // OneToMany (inverse side) - ask user for integration type
            if ($relationType === 4) {
                $targetEntity = (string) ($mapping['targetEntity'] ?? '');
                if ($targetEntity === '') {
                    continue;
                }

                $inferredFormType = $this->inferFormTypeFromEntity($targetEntity);
                $isJoinEntity     = $this->isJoinEntity($targetEntity);

                if ($isJoinEntity) {
                    $integrationChoices = [
                        '1' => 'CollectionType (inline editing with entry_type, requires custom JavaScript)',
                        '2' => 'Live Component CollectionType (zero JavaScript, requires symfony/ux-live-component)  ← recommandé',
                        '4' => 'Custom/Complex (skip, implement manually)',
                        '5' => 'Skip this relation',
                    ];
                } else {
                    $integrationChoices = [
                        '1' => 'CollectionType (inline editing with entry_type, requires custom JavaScript)',
                        '2' => 'Live Component CollectionType (zero JavaScript, requires symfony/ux-live-component)',
                        '3' => 'UX Autocomplete (AutocompleteEntityType with multiple=true)  ← recommandé',
                        '4' => 'Custom/Complex (skip, implement manually)',
                        '5' => 'Skip this relation',
                    ];
                }

                $integrationChoice = $io->choice(
                    sprintf(
                        'Relation "%s" (OneToMany, targetEntity: %s)%s\nFormType cible déduit : %s\nType d\'intégration ?',
                        $assocName,
                        $targetEntity,
                        $isJoinEntity ? '\n[entité join détectée : champs supplémentaires présents — option 3 non disponible]' : '',
                        $inferredFormType
                    ),
                    $integrationChoices,
                    $integrationChoices['5']
                );
                // SymfonyStyle::choice() can return either the key (e.g. '2') or the value (label)
                // depending on how the underlying ChoiceQuestion normalizes the choices.
                if (array_key_exists($integrationChoice, $integrationChoices)) {
                    $integrationType = (string) $integrationChoice;
                } else {
                    $integrationType = array_search($integrationChoice, $integrationChoices, true) ?: '5';
                }

                // PHP casts numeric-string array keys to int, so integrationType can be int(2).
                // Normalize to string to make strict comparisons reliable.
                $integrationType = (string) $integrationType;

                if ($integrationType === '5') {
                    continue;
                }

                if ($integrationType === '2' || $integrationType === '1') {
                    $options = [
                        'entry_type' => $inferredFormType,
                        'allow_add' => true,
                        'allow_delete' => true,
                        'by_reference' => false,
                    ];

                    if ($integrationType === '2') {
                        $options['attr'] = [
                            'data-neox-live-collection' => '1',
                        ];
                        $options['row_attr'] = [
                            'data-neox-live-collection' => '1',
                        ];
                    }

                    $this->relationFormFieldsByName[$assocName] = [
                        'name' => $assocName,
                        'type' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType',
                        'options' => $options,
                        'live_component' => $integrationType === '2',
                    ];

                    // Check if the target FormType exists, offer to generate it if not
                    if (!class_exists($inferredFormType)) {
                        $generateFormType = $io->confirm(
                            sprintf('Le FormType %s n\'existe pas. Le générer dans handlerType ? (y/n)', $inferredFormType),
                            true
                        );
                        if ($generateFormType) {
                            $this->formTypesToGenerate[] = [
                                'entity_class' => $targetEntity,
                                'form_type_class' => $inferredFormType,
                            ];
                        }
                    }

                    // Check that add*/remove* methods exist on the parent entity
                    $this->checkEntityCollectionMethods($entityClass, $assocName, $io);
                } elseif ($integrationType === '3') {
                    $choiceLabel = $this->detectChoiceLabel($targetEntity, $choiceLabelPriority);
                    if ($choiceLabel === null) {
                        $choiceLabel = (string) $io->ask('Nom du champ à utiliser comme choice_label', 'id');
                    }

                    $this->relationFormFieldsByName[$assocName] = [
                        'name' => $assocName,
                        'type' => '\\Symfony\\UX\\Autocomplete\\Form\\AutocompleteEntityType',
                        'options' => [
                            'class' => $targetEntity,
                            'choice_label' => $choiceLabel,
                            'multiple' => true,
                            'searchable_fields' => [$choiceLabel],
                        ],
                    ];
                }
                // custom: skip, user will implement manually
                continue;
            }

            $targetEntity = (string) ($mapping['targetEntity'] ?? '');
            if ($targetEntity === '') {
                continue;
            }

            $render = $defaultRender;
            if ($mode === 'interactive' || ($render === 'autocomplete' && !\class_exists('Symfony\\UX\\Autocomplete\\Form\\AutocompleteEntityType'))) {
                $render = (string) $io->choice(
                    sprintf('Relation "%s": Type de rendu ?', $assocName),
                    ['select', 'autocomplete', 'checkbox'],
                    $render
                );
            }

            $choiceLabel = $this->detectChoiceLabel($targetEntity, $choiceLabelPriority);
            if ($mode === 'interactive' || $choiceLabel === null) {
                $choiceLabel = (string) $io->choice(
                    sprintf('Relation "%s": impossible de détecter choice_label. Choisir :', $assocName),
                    ['id', 'custom'],
                    $choiceLabel ?? 'id'
                );
                if ($choiceLabel === 'custom') {
                    $choiceLabel = (string) $io->ask('Nom du champ à utiliser comme choice_label', 'id');
                }
            }

            $multiple = $relationType === 8;

            if ($render === 'autocomplete') {
                $type = '\\Symfony\\UX\\Autocomplete\\Form\\AutocompleteEntityType';
                $options = [
                    'class' => $targetEntity,
                    'choice_label' => $choiceLabel,
                    'multiple' => $multiple,
                    'searchable_fields' => [$choiceLabel],
                ];
            } else {
                $type = '\\Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType';
                $options = [
                    'class' => $targetEntity,
                    'choice_label' => $choiceLabel,
                    'multiple' => $multiple,
                ];
                if ($render === 'checkbox') {
                    $options['expanded'] = true;
                }
            }

            $this->relationFormFieldsByName[$assocName] = [
                'name' => $assocName,
                'type' => $type,
                'options' => $options,
            ];
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassInput = (string)$input->getArgument('entity-class');

        $enableLiveTable = (bool) $input->getOption('enable-live-table');

        $entityClass = $this->resolveEntityClass($entityClassInput);

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

        // Exclude scalar fields coming from traits (commonly technical metadata like MobileSyncMetadataTrait)
        $entityRefl = $entityMetadata->getReflectionClass();
        $fieldNames = array_values(array_filter(
            $fieldNames,
            static function (string $fieldName) use ($entityRefl): bool {
                if (!$entityRefl->hasProperty($fieldName)) {
                    return true;
                }

                $prop = $entityRefl->getProperty($fieldName);
                return !$prop->getDeclaringClass()->isTrait();
            }
        ));

        // Build field types for config.yaml template
        /** @var array<string, string> $fieldTypes */
        $fieldTypes = [];
        foreach ($fieldNames as $fieldName) {
            $doctrineType = $entityMetadata->getTypeOfField($fieldName);
            $fieldTypes[$fieldName] = $doctrineType;
        }

        // 1) Generate FormType – with .sav behavior
        $projectRoot      = $generator->getRootDirectory();
        $formPath         = 'src/Form/' . $formShort . '.php';
        $absoluteFormPath = $projectRoot . '/' . $formPath;

        // Build scalar form fields with auto-guessed Symfony Form Types based on Doctrine metadata
        /** @var array<string, array{name: string, type: string|null, options: array<string, mixed>}> $scalarFormFieldsByName */
        $scalarFormFieldsByName = [];
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

            // IntegerType: empty_data => 0 prevents null being passed to int-typed setters
            if (in_array($doctrineType, ['integer', 'smallint', 'bigint'], true)) {
                $options['empty_data'] = 0;
            }

            // CheckboxType (boolean): always required => false — unchecked = not submitted = null otherwise
            if ($doctrineType === 'boolean') {
                $options['required'] = false;
            }

            // If boolean and nullable in Doctrine, make the field not required to avoid validation hiccups
            if ($doctrineType === 'boolean') {
                // Doctrine\ORM\Mapping\ClassMetadata has getFieldMapping(),
                // but Doctrine\Persistence\Mapping\ClassMetadata (interface) does not declare it.
                // Guard the call for compatibility across versions/drivers.
                if (\method_exists($entityMetadata, 'getFieldMapping')) {
                    $mapping = $entityMetadata->getFieldMapping($fieldName);
                    $nullable = \is_object($mapping) ? ($mapping->nullable ?? false) : ($mapping['nullable'] ?? false);
                    if ($nullable === true) {
                        $options['required'] = false;
                    }
                }
            }

            $scalarFormFieldsByName[$fieldName] = [
                'name' => $fieldName,
                'type' => $formType,
                // FQCN with leading backslash or null
                'options' => $options,
            ];
        }

        $relationsConfig = $this->getRelationsConfig();
        $nullableRequired = (bool) ($relationsConfig['nullable_required'] ?? false);

        // Apply nullable => required=false for relation fields if configured
        if (!$nullableRequired) {
            foreach ($this->relationFormFieldsByName as $assocName => $fieldDef) {
                $mapping = $entityMetadata->getAssociationMapping($assocName);
                $isNullable = (bool) ($mapping['joinColumns'][0]['nullable'] ?? false);
                if ($isNullable) {
                    $fieldDef['options']['required'] = false;
                    $this->relationFormFieldsByName[$assocName] = $fieldDef;
                }
            }
        }

        // Merge scalar + relation fields according to makers.relations.order (default: interleaved)
        $order = (string) ($relationsConfig['order'] ?? 'interleaved');
        $formFields = [];
        if ($order === 'interleaved') {
            $associationNames = $entityMetadata->getAssociationNames();
            $orderedNames = $this->getOrderedPropertyNamesWithAssociations($entityMetadata->getReflectionClass(), $associationNames);
            foreach ($orderedNames as $propName) {
                if (isset($scalarFormFieldsByName[$propName])) {
                    $formFields[] = $scalarFormFieldsByName[$propName];
                    unset($scalarFormFieldsByName[$propName]);
                    continue;
                }
                if (isset($this->relationFormFieldsByName[$propName])) {
                    $formFields[] = $this->relationFormFieldsByName[$propName];
                    unset($this->relationFormFieldsByName[$propName]);
                }
            }
        }

        // Remaining scalars (and relations if any) appended at the end
        foreach ($scalarFormFieldsByName as $fieldDef) {
            $formFields[] = $fieldDef;
        }
        foreach ($this->relationFormFieldsByName as $fieldDef) {
            $formFields[] = $fieldDef;
        }

        $formContext = [
            'entity_class'    => $entityClass,
            'form_class_name' => $formShort,
            'form_namespace'  => 'App\\Form',
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

        // 1.5) Generate target FormTypes for OneToMany relations (in handlerType subdirectory)
        foreach ($this->formTypesToGenerate as $formTypeToGenerate) {
            $targetEntityClass = $formTypeToGenerate['entity_class'];
            $targetFormTypeClass = $formTypeToGenerate['form_type_class'];

            try {
                $targetEntityMetadata = $this->doctrineHelper->getMetadata($targetEntityClass);
                $targetEntityShortName = $targetEntityMetadata->getReflectionClass()->getShortName();

                // Generate in Form/handlerType directory: Form/handlerType/
                $formNamespacePrefix = 'Form\\handlerType\\';
                $targetFormClassNameDetails = $generator->createClassNameDetails($targetEntityShortName . 'Type', $formNamespacePrefix);

                // Build scalar form fields for target entity
                $targetFieldNames = array_filter($targetEntityMetadata->getFieldNames(), static fn (string $field): bool => $field !== 'id');
                $targetScalarFormFieldsByName = [];
                foreach ($targetFieldNames as $fieldName) {
                    $doctrineType = $targetEntityMetadata->getTypeOfField($fieldName);
                    $formType = match ($doctrineType) {
                        'string' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
                        'text' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',
                        'integer', 'smallint', 'bigint' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType',
                        'float', 'decimal' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\NumberType',
                        'boolean' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',
                        'date', 'date_immutable' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\DateType',
                        'datetime', 'datetimetz', 'datetime_immutable' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType',
                        'time', 'time_immutable' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TimeType',
                        'json' => \Symfony\Component\Form\Extension\Core\Type\TextareaType::class,
                        'array', 'simple_array' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType',
                        'uuid', 'guid' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
                        default => null,
                    };

                    $options = [];
                    if ($doctrineType === 'array' || $doctrineType === 'simple_array') {
                        $options = [
                            'entry_type' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
                            'allow_add' => true,
                            'allow_delete' => true,
                        ];
                    }

                    $targetScalarFormFieldsByName[$fieldName] = [
                        'name' => $fieldName,
                        'type' => $formType,
                        'options' => $options,
                    ];
                }

                $targetFormContext = [
                    'entity_class' => $targetEntityClass,
                    'form_class_name' => $targetFormClassNameDetails->getShortName(),
                    'form_namespace' => (static function () use ($targetFormTypeClass): string {
                        $pos = strrpos($targetFormTypeClass, '\\');
                        if ($pos === false) {
                            return 'App\\Form';
                        }
                        return substr($targetFormTypeClass, 0, $pos);
                    })(),
                    'fields' => $targetFieldNames,
                    'form_fields' => array_values($targetScalarFormFieldsByName),
                    'resource' => strtolower($targetEntityShortName),
                    'field_keys' => $this->fieldKeys,
                ];

                $generator->generateClass($targetFormClassNameDetails->getFullName(), __DIR__ . '/tpl/NeoxCrudFormType.tpl.php', $targetFormContext);
                $io->text(sprintf('Generated FormType "%s" for OneToMany relation.', $targetFormClassNameDetails->getFullName()));
            } catch (\Throwable $e) {
                $io->warning(sprintf('Failed to generate FormType for entity "%s": %s', $targetEntityClass, $e->getMessage()));
            }
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
                    'field_types' => $fieldTypes,
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

    private function resolveEntityClass(string $entityClassInput): string
    {
        if (!str_contains($entityClassInput, '\\')) {
            return $this->doctrineHelper->getEntityNamespace() . '\\' . $entityClassInput;
        }

        return $entityClassInput;
    }

    /** @return array<string, mixed> */
    private function getRelationsConfig(): array
    {
        $relations = $this->makers['relations'] ?? [];
        if (!is_array($relations)) {
            $relations = [];
        }

        return array_replace([
            'mode' => 'mix',
            'default_render' => 'select',
            'choice_label_priority' => ['name', 'title', 'label', 'id'],
            'nullable_required' => false,
            'order' => 'interleaved',
            'group_relations' => true,
        ], $relations);
    }

    /** @param string[] $priority */
    private function detectChoiceLabel(string $targetEntity, array $priority): ?string
    {
        try {
            $meta = $this->doctrineHelper->getMetadata($targetEntity);
            $fields = $meta->getFieldNames();
        } catch (\Throwable) {
            $fields = [];
        }

        foreach ($priority as $candidate) {
            if (is_string($candidate) && in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function inferFormTypeFromEntity(string $entityClass): string
    {
        // Replace Entity namespace with Form/handlerType namespace and add Type suffix
        // Example: App\Entity\Product -> App\Form\handlerType\ProductType
        $formClass = str_replace('\\Entity\\', '\\Form\\handlerType\\', $entityClass);
        $shortName = (new \ReflectionClass($entityClass))->getShortName();
        $formClass = substr($formClass, 0, -strlen($shortName)) . $shortName . 'Type';

        return $formClass;
    }

    /** @return string[] */
    private function guessSingulars(string $word): array
    {
        $candidates = [];

        if (class_exists(\Symfony\Component\String\Inflector\FrenchInflector::class)) {
            $candidates = array_merge($candidates, (new \Symfony\Component\String\Inflector\FrenchInflector())->singularize($word));
        }
        if (class_exists(\Symfony\Component\String\Inflector\EnglishInflector::class)) {
            $candidates = array_merge($candidates, (new \Symfony\Component\String\Inflector\EnglishInflector())->singularize($word));
        }

        // Fallback simple rules when symfony/string inflectors are unavailable
        if ($candidates === []) {
            if (str_ends_with($word, 'ies')) {
                $candidates[] = substr($word, 0, -3) . 'y';
            }
            if (str_ends_with($word, 'eaux') || str_ends_with($word, 'aux')) {
                $candidates[] = substr($word, 0, -1) . 'l';
            }
            if (str_ends_with($word, 's') && !str_ends_with($word, 'ss') && !str_ends_with($word, 'us')) {
                $candidates[] = substr($word, 0, -1);
            }
        }

        $candidates[] = $word;

        return array_values(array_unique($candidates));
    }

    private function checkEntityCollectionMethods(string $entityClass, string $assocName, ConsoleStyle $io): void
    {
        if (!class_exists($entityClass)) {
            return;
        }

        $reflClass = new \ReflectionClass($entityClass);
        $singulars  = $this->guessSingulars($assocName);

        $addMethod    = null;
        $removeMethod = null;

        foreach ($singulars as $singular) {
            $candidate = 'add' . ucfirst((string) $singular);
            if ($reflClass->hasMethod($candidate)) {
                $addMethod    = $candidate;
                $removeMethod = 'remove' . ucfirst((string) $singular);
                break;
            }
        }

        $missing = [];

        if ($addMethod === null) {
            $expected  = 'add' . ucfirst((string) ($singulars[0] ?? $assocName));
            $missing[] = $expected . '()  [non trouvé]';
        }

        if ($addMethod !== null && $removeMethod !== null && !$reflClass->hasMethod($removeMethod)) {
            $missing[] = $removeMethod . '()  [non trouvé]';
        }

        if ($missing !== []) {
            $io->warning([
                sprintf('La relation "%s" sur %s nécessite ces méthodes :', $assocName, $entityClass),
                ...array_map(static fn (string $m) => '  • ' . $m, $missing),
                'Exécutez :',
                sprintf('  php bin/console make:entity --regenerate "%s"', $entityClass),
            ]);
        }
    }

    /**
     * Heuristic: an entity is a "join entity" (has its own domain fields) when it has
     * more than 1 non-id, non-mobile scalar field. Simple lookup entities (nom, label…)
     * have at most 1 such field and are safe to use with AutocompleteEntityType.
     */
    private function isJoinEntity(string $entityClass): bool
    {
        if (!class_exists($entityClass)) {
            return false;
        }

        try {
            $meta   = $this->doctrineHelper->getMetadata($entityClass);
            $fields = array_filter(
                $meta->getFieldNames(),
                static fn (string $f) => !\in_array($f, ['id', 'uuid'], true)
                    && !str_starts_with($f, 'mobile')
            );

            return \count($fields) > 1;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    private function getOrderedPropertyNames(\ReflectionClass $reflClass): array
    {
        $props = $reflClass->getProperties();
        $names = [];
        foreach ($props as $p) {
            // Skip properties from traits (keep only properties declared in this class)
            if ($p->getDeclaringClass()->getName() === $reflClass->getName()) {
                $names[] = $p->getName();
            }
        }
        return $names;
    }

    /**
     * @return string[]
     */
    private function getOrderedPropertyNamesWithAssociations(\ReflectionClass $reflClass, array $associationNames): array
    {
        $props = $reflClass->getProperties();
        $names = [];
        foreach ($props as $p) {
            // Skip properties from traits (keep only properties declared in this class)
            if ($p->getDeclaringClass()->getName() === $reflClass->getName()) {
                $names[] = $p->getName();
            }
        }
        // Add association names that are not already in the list
        foreach ($associationNames as $assocName) {
            if (!in_array($assocName, $names, true)) {
                $names[] = $assocName;
            }
        }
        return $names;
    }
}
