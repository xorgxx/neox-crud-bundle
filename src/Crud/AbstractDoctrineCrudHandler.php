<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Neox\NeoxCrudBundle\Crud\Event\CrudEntityDeletedEvent;
use Neox\NeoxCrudBundle\Crud\Event\CrudEntitySavedEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Base implementation of a CrudHandler backed by Doctrine ORM.
 */
abstract class AbstractDoctrineCrudHandler implements CrudHandlerInterface
{
    protected bool $useTransactions = true;

    private bool $isCreateOperation = false;

    /**
     * Parsed per-field options loaded from handler YAML configuration.
     *
     * Shape: [ fieldName => array options ]
     */
    private array $indexFieldOptions = [];

    /**
     * Cached UI configuration (normalized) loaded from handler YAML.
     * Keys: actions, bulk_actions, toolbar_buttons, append_default_actions
     */
    private ?array $uiConfig = null;

    public function __construct(
        protected EntityManagerInterface $em,
        protected FormFactoryInterface $formFactory,
        protected EventDispatcherInterface $dispatcher,
    ) {
    }

    abstract public function getName(): string;
    abstract public function getEntityClass(): string;
    abstract public function getFormType(): string;
    abstract public function getTemplatePrefix(): string;

    /**
     * Returns all entity fields except 'id' by default.
     * Override this method to customize displayed fields in index view.
     */
    public function getIndexFields(): array
    {
        // 1) Optional per-handler YAML override (opt-in, BC safe)
        //    Look for a config file located next to the concrete handler class
        //    and read the "index_fields" key when present.
        if (($configFields = $this->loadIndexFieldsFromConfig()) !== null) {
            return $configFields;
        }

        // 2) Fallback to Doctrine metadata (previous default behavior)
        $metadata = $this->em->getClassMetadata($this->getEntityClass());
        return array_filter(
            $metadata->getFieldNames(),
            static fn (string $field): bool => $field !== 'id'
        );
    }

    /**
     * Returns the per-field options loaded from configuration.
     *
     * The returned array is keyed by field name and contains the options array
     * defined in YAML for each field. When no configuration is present, an
     * empty array is returned. This method does not affect BC as the index view
     * can continue using only getIndexFields().
     */
    public function getIndexFieldOptions(): array
    {
        // Ensure configuration loading happens if not called yet
        if ($this->indexFieldOptions === []) {
            $this->loadIndexFieldsFromConfig();
        }

        return $this->indexFieldOptions;
    }

    /**
     * Attempt to load index fields from an adjacent YAML file.
     *
     * Supported locations (first match wins):
     *  - <HandlerDir>/config.yaml
     *  - <HandlerDir>/<ClassName>.yaml
     *  - <HandlerDir>/config/crud.yaml
     *
     * Supported keys:
     *  - index_fields: ["name", "createdAt"]
     *  - neox_crud: { index_fields: [...] }
     *
     * Returns null when no valid configuration is found.
     */
    protected function loadIndexFieldsFromConfig(): ?array
    {
        try {
            $refl = new \ReflectionClass($this);
            $dir  = \dirname((string) $refl->getFileName());

            $candidates = [
                $dir . DIRECTORY_SEPARATOR . 'config.yaml',
                $dir . DIRECTORY_SEPARATOR . $refl->getShortName() . '.yaml',
                $dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'crud.yaml',
            ];

            foreach ($candidates as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $data = Yaml::parseFile($file);
                if (!\is_array($data)) {
                    continue;
                }

                // Always try to hydrate UI config when available (opt-in, BC)
                $this->hydrateUiConfig($data);

                $fields = null;
                if (isset($data['index_fields'])) {
                    $fields = $data['index_fields'];
                } elseif (isset($data['neox_crud']['index_fields']) && \is_array($data['neox_crud'])) {
                    $fields = $data['neox_crud']['index_fields'] ?? null;
                }

                if ($fields === null) {
                    continue;
                }

                // Normalize into [names list] and fill $this->indexFieldOptions as a side-effect
                $resultNames = $this->normalizeIndexFields($fields);
                if ($resultNames !== []) {
                    return $resultNames;
                }
            }
        } catch (\Throwable) {
            // Silently ignore configuration errors to preserve BC
        }

        return null;
    }

    /**
     * Load actions, bulk_actions and toolbar_buttons if present in data.
     * This is called opportunistically when reading the YAML file for index_fields.
     */
    private function hydrateUiConfig(array $data): void
    {
        // If we already parsed it once, keep the cached version
        if ($this->uiConfig !== null) {
            return;
        }

        $root = $data;
        if (isset($data['neox_crud']) && \is_array($data['neox_crud'])) {
            // Nested variant takes precedence when present
            $root = $data['neox_crud'] + $data; // ensure fallback to flat keys
        }

        $actions                = $root['actions']         ?? null;
        $bulkActions            = $root['bulk_actions']    ?? null;
        $toolbarButtons         = $root['toolbar_buttons'] ?? null;
        $appendDefaultRowAction = isset($root['append_default_actions']) ? (bool) $root['append_default_actions'] : false;

        $normalize = function (mixed $list, bool $isBulk = false): array {
            if (!\is_array($list)) {
                return [];
            }

            $out = [];

            foreach ($list as $entry) {
                if (!\is_array($entry)) {
                    continue;
                }

                $name = $entry['name'] ?? null;
                if (!\is_string($name) || $name === '') {
                    continue;
                }

                $item = [
                    'name'  => $name,
                    'label' => isset($entry['label']) && \is_string($entry['label']) ? $entry['label'] : $name,
                    'icon'  => isset($entry['icon'])  && \is_string($entry['icon']) ? $entry['icon'] : null,
                    // Either a Symfony route name or a direct path/URL
                    'route'    => isset($entry['route'])    && \is_string($entry['route']) ? $entry['route'] : null,
                    'path'     => isset($entry['path'])     && \is_string($entry['path']) ? $entry['path'] : null,
                    'method'   => isset($entry['method'])   && \is_string($entry['method']) ? strtoupper($entry['method']) : 'GET',
                    'confirm'  => isset($entry['confirm'])  && \is_string($entry['confirm']) ? $entry['confirm'] : null,
                    'class'    => isset($entry['class'])    && \is_string($entry['class']) ? $entry['class'] : null,
                    'priority' => isset($entry['priority']) && is_numeric($entry['priority']) ? (int) $entry['priority'] : 0,
                    'params'   => isset($entry['params'])   && \is_array($entry['params']) ? $entry['params'] : [],
                    'if'       => isset($entry['if'])       && (\is_string($entry['if']) || \is_bool($entry['if'])) ? $entry['if'] : null,
                ];

                // voters: string or list of strings
                if (isset($entry['voters'])) {
                    $v = $entry['voters'];
                    if (\is_string($v)) {
                        $v = [$v];
                    }
                    if (\is_array($v)) {
                        $item['voters'] = array_values(array_filter(array_map(static fn ($x) => \is_string($x) ? $x : null, $v)));
                    }
                }

                if ($isBulk) {
                    $item['selection_required'] = isset($entry['selection_required']) ? (bool) $entry['selection_required'] : true;
                }

                $out[] = $item;
            }

            // Order by priority desc, then by name asc for stability
            usort($out, static function (array $a, array $b): int {
                $pa = $a['priority'] ?? 0;
                $pb = $b['priority'] ?? 0;
                if ($pa === $pb) {
                    return strcmp((string) $a['name'], (string) $b['name']);
                }
                return $pb <=> $pa; // higher priority first
            });

            return $out;
        };

        $this->uiConfig = [
            'actions'                => $normalize($actions, false),
            'bulk_actions'           => $normalize($bulkActions, true),
            'toolbar_buttons'        => $normalize($toolbarButtons, false),
            'append_default_actions' => $appendDefaultRowAction,
        ];
    }

    /**
     * Expose normalized toolbar buttons (no filtering performed here).
     */
    public function getToolbarButtons(array $context = []): array
    {
        if ($this->uiConfig === null) {
            // Trigger config discovery lazily
            $this->loadIndexFieldsFromConfig();
        }

        return $this->uiConfig['toolbar_buttons'] ?? [];
    }

    /**
     * Expose normalized bulk actions (no selection resolution here).
     */
    public function getBulkActions(): array
    {
        if ($this->uiConfig === null) {
            $this->loadIndexFieldsFromConfig();
        }

        return $this->uiConfig['bulk_actions'] ?? [];
    }

    /**
     * Resolve per-row actions for a given entity (params placeholders supported).
     * Supported placeholders in params values:
     *  - "entity.<propPath>" (e.g., entity.id, entity.user.email)
     *  - "context.<key>"
     * A minimal "if" evaluator is applied when provided:
     *  - "entity.foo" → truthy check
     *  - "!entity.foo" → falsy check
     *  - "context.bar" / "!context.bar"
     */
    public function getRowActionsFor(object $entity, array $context = []): array
    {
        if ($this->uiConfig === null) {
            $this->loadIndexFieldsFromConfig();
        }

        $actions = $this->uiConfig['actions'] ?? [];
        if ($actions === []) {
            return [];
        }

        // Optionally append default Edit/Delete actions after developer-defined ones, without overriding them
        if (!empty($this->uiConfig['append_default_actions'])) {
            $names     = array_map(static fn (array $a) => (string)($a['name'] ?? ''), $actions);
            $hasEdit   = in_array('edit', $names, true);
            $hasDelete = in_array('delete', $names, true);

            if (!$hasEdit) {
                $actions[] = [
                    'name'     => 'edit',
                    'label'    => 'Éditer',
                    'icon'     => 'bi bi-pencil',
                    'route'    => 'neox_crud_admin_crud_edit',
                    'method'   => 'GET',
                    'params'   => [ 'id' => 'entity.id' ],
                    'priority' => -100, // keep at the end
                ];
            }
            if (!$hasDelete) {
                $actions[] = [
                    'name'     => 'delete',
                    'label'    => 'Supprimer',
                    'icon'     => 'bi bi-trash',
                    'route'    => 'neox_crud_admin_crud_delete',
                    'method'   => 'DELETE',
                    'params'   => [ 'id' => 'entity.id' ],
                    'priority' => -110, // keep after edit
                ];
            }
        }

        $resolver = function ($value) use ($entity, $context) {
            if (!\is_string($value)) {
                return $value;
            }
            if (str_starts_with($value, 'entity.')) {
                return $this->readPropertyPath($entity, substr($value, 7));
            }
            if (str_starts_with($value, 'context.')) {
                $k = substr($value, 8);
                return $context[$k] ?? null;
            }
            return $value;
        };

        $passes = function ($cond) use ($entity, $context): bool {
            if ($cond === null) {
                return true;
            }
            if (\is_bool($cond)) {
                return $cond;
            }
            if (!\is_string($cond)) {
                return true;
            }
            $neg  = false;
            $expr = trim($cond);
            if (str_starts_with($expr, '!')) {
                $neg  = true;
                $expr = ltrim(substr($expr, 1));
            }
            $val = null;
            if (str_starts_with($expr, 'entity.')) {
                $val = $this->readPropertyPath($entity, substr($expr, 7));
            } elseif (str_starts_with($expr, 'context.')) {
                $val = $context[substr($expr, 8)] ?? null;
            } else {
                // Unknown expression: do not filter out
                return true;
            }
            $bool = (bool) $val;
            return $neg ? !$bool : $bool;
        };

        $resolved = [];
        foreach ($actions as $a) {
            if (!$passes($a['if'] ?? null)) {
                continue;
            }
            $item = $a;
            if (!empty($item['params'])) {
                foreach ($item['params'] as $k => $v) {
                    $item['params'][$k] = $resolver($v);
                }
            }
            $resolved[] = $item;
        }

        return $resolved;
    }

    /**
     * Read a simple dotted property path from an object using getters or public props.
     */
    private function readPropertyPath(object $object, string $path): mixed
    {
        $segments = array_filter(explode('.', $path), static fn ($s) => $s !== '');
        $current  = $object;
        foreach ($segments as $seg) {
            $getter = 'get' . ucfirst($seg);
            $isser  = 'is' . ucfirst($seg);
            if (\is_object($current)) {
                if (method_exists($current, $getter)) {
                    $current = $current->{$getter}();
                    continue;
                }
                if (method_exists($current, $isser)) {
                    $current = $current->{$isser}();
                    continue;
                }
                if (isset($current->{$seg})) {
                    $current = $current->{$seg};
                    continue;
                }
            }
            // Not found
            return null;
        }
        return $current;
    }

    /**
     * @param mixed $fields
     * @return array<int,string> Normalized list of field names; also sets $this->indexFieldOptions
     */
    private function normalizeIndexFields(mixed $fields): array
    {
        if (!\is_array($fields)) {
            return [];
        }

        $names          = [];
        $optionsByField = [];

        $isAssoc = static function (array $arr): bool {
            return $arr !== [] && array_keys($arr) !== range(0, count($arr) - 1);
        };

        // Case A: associative map: fieldName => options (or scalar true)
        if ($isAssoc($fields)) {
            foreach ($fields as $name => $opts) {
                if (!\is_string($name) || $name === '') {
                    continue;
                }
                $names[] = $name;
                if (\is_array($opts)) {
                    $optionsByField[$name] = $this->filterOptions($opts);
                } else {
                    $optionsByField[$name] = [];
                }
            }
        } else {
            // Case B: list of entries: either strings, or maps with { name: field, ...options }
            foreach ($fields as $entry) {
                if (\is_string($entry) && $entry !== '') {
                    $names[]                = $entry;
                    $optionsByField[$entry] = $optionsByField[$entry] ?? [];
                    continue;
                }
                if (\is_array($entry)) {
                    $name = null;
                    if (isset($entry['name']) && \is_string($entry['name'])) {
                        $name = $entry['name'];
                    } elseif (isset($entry['field']) && \is_string($entry['field'])) {
                        $name = $entry['field'];
                    }
                    if ($name) {
                        $names[] = $name;
                        unset($entry['name'], $entry['field']);
                        $optionsByField[$name] = $this->filterOptions($entry);
                    }
                }
            }
        }

        // Persist options (only if there is something meaningful)
        if ($optionsByField !== []) {
            $this->indexFieldOptions = $optionsByField;
        }

        // Unique and in order
        $names = array_values(array_unique(array_filter($names, static fn ($n) => \is_string($n) && $n !== '')));

        return $names;
    }

    /**
     * Whitelist-known option keys and ensure scalar/array shapes are safe.
     * Unknown keys are kept to allow forward compatibility.
     */
    private function filterOptions(array $opts): array
    {
        // Normalize some common keys provided by users
        if (isset($opts['voter'])) {
            // Allow single 'voter' as alias of 'voters'
            $opts['voters'] = $opts['voters'] ?? $opts['voter'];
            unset($opts['voter']);
        }
        // Ensure voters is always an array of strings
        if (isset($opts['voters'])) {
            $v = $opts['voters'];
            if (\is_string($v)) {
                $v = [$v];
            }
            if (!\is_array($v)) {
                unset($opts['voters']);
            } else {
                $opts['voters'] = array_values(array_filter(array_map(static fn ($x) => \is_string($x) ? $x : null, $v)));
                if ($opts['voters'] === []) {
                    unset($opts['voters']);
                }
            }
        }

        // Pass through other options like 'format', 'type', 'boolean_icon', 'image', 'class' etc.
        return $opts;
    }

    public function findAll(): iterable
    {
        return $this->em->getRepository($this->getEntityClass())->findAll();
    }

    public function findList(Request $request): iterable
    {
        $repository = $this->em->getRepository($this->getEntityClass());

        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 25);
        if ($limit < 1) {
            $limit = 25;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        if (!method_exists($repository, 'createQueryBuilder')) {
            // Fallback: no advanced pagination support.
            return $this->findAll();
        }

        $qb = $repository->createQueryBuilder('e');
        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function find(int|string $id): ?object
    {
        return $this->em->getRepository($this->getEntityClass())->find($id);
    }

    /**
     * Execute the given callback, optionally wrapped in a Doctrine transaction.
     */
    protected function transactional(Closure $callback): void
    {
        if (!$this->useTransactions) {
            $callback();

            return;
        }

        $em = $this->em;

        if (method_exists($em, 'wrapInTransaction')) {
            $em->wrapInTransaction($callback);
        } else {
            $em->beginTransaction();
            try {
                $callback();
                $em->flush();
                $em->commit();
            } catch (\Throwable $e) {
                $em->rollback();
                throw $e;
            }
        }
    }

    public function createEntity(): object
    {
        $class = $this->getEntityClass();

        return new $class();
    }

    public function createForm(object $entity): FormInterface
    {
        return $this->formFactory->create($this->getFormType(), $entity);
    }

    public function handleForm(Request $request, FormInterface $form): bool
    {
        $form->handleRequest($request);

        return $form->isSubmitted() && $form->isValid();
    }

    // --- Pre-flush hooks ---

    public function preCreate(object $entity, Request $request): void
    {
        $this->isCreateOperation = true;
    }

    public function preUpdate(object $entity, Request $request): void
    {
        $this->isCreateOperation = false;
    }

    public function preDelete(object $entity, Request $request): void
    {
        // Override if needed
    }

    // --- Simple generic hooks ---

    protected function beforeSave(object $entity): void
    {
    }
    protected function afterSave(object $entity): void
    {
    }
    protected function beforeDelete(object $entity): void
    {
    }
    protected function afterDelete(object $entity): void
    {
    }

    // --- Persistence / removal + event dispatch ---

    public function save(object $entity): void
    {
        $this->beforeSave($entity);

        $this->em->persist($entity);
        $this->em->flush();

        // Generic CRUD event
        $this->dispatcher->dispatch(
            new CrudEntitySavedEvent(
                resourceName: $this->getName(),
                entityClass: $this->getEntityClass(),
                entityId: $this->getEntityIdentifier($entity),
                entity: $entity,
                operation: $this->isCreateOperation
                    ? CrudEntitySavedEvent::OPERATION_CREATE
                    : CrudEntitySavedEvent::OPERATION_UPDATE,
            )
        );

        $this->afterSave($entity);
    }

    public function delete(object $entity): void
    {
        $this->beforeDelete($entity);

        $id = $this->getEntityIdentifier($entity);

        $this->em->remove($entity);
        $this->em->flush();

        // Generic CRUD event
        $this->dispatcher->dispatch(
            new CrudEntityDeletedEvent(
                resourceName: $this->getName(),
                entityClass: $this->getEntityClass(),
                entityId: $id,
                entity: $entity,
            )
        );

        $this->afterDelete($entity);
    }

    /**
     * Retrieve the entity identifier.
     * Simple version based on getId(), then fallback to Doctrine metadata.
     */
    protected function getEntityIdentifier(object $entity): mixed
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }

        $meta   = $this->em->getClassMetadata($this->getEntityClass());
        $values = $meta->getIdentifierValues($entity);

        return count($values) === 1 ? reset($values) : $values;
    }

    // --- Default redirects ---

    public function getRedirectAfterCreate(object $entity): array
    {
        return ['neox_crud_admin_crud_index', ['resource' => $this->getName()]];
    }

    public function getRedirectAfterUpdate(object $entity): array
    {
        return ['neox_crud_admin_crud_index', ['resource' => $this->getName()]];
    }

    public function getRedirectAfterDelete(object $entity): array
    {
        return ['neox_crud_admin_crud_index', ['resource' => $this->getName()]];
    }

    public function getRedirectAfterAction(string $action, object $entity): array
    {
        return ['neox_crud_admin_crud_index', ['resource' => $this->getName()]];
    }

    // --- Custom actions: not supported by default ---

    public function supportsAction(string $action, string $method): bool
    {
        return false;
    }

    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response {
        throw new NotFoundHttpException(sprintf(
            'Action "%s" non supportée pour la ressource "%s".',
            $action,
            $this->getName()
        ));
    }
}
