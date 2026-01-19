<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\LiveComponent;

use Neox\NeoxCrudBundle\Crud\CrudHandlerFactory;
use Neox\NeoxCrudBundle\LiveTable\DoctrineCrudListQueryBuilder;
use Neox\NeoxCrudBundle\LiveTable\IndexFieldsNormalizer;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('neox_crud_index_table', template: '@NeoxCrud/components/neox_crud_index_table.html.twig')]
final class CrudIndexTableComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $resource;

    #[LiveProp(writable: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public int $perPage = 25;

    #[LiveProp(writable: true)]
    public ?string $sort = null;

    #[LiveProp(writable: true)]
    public string $direction = 'asc';

    #[LiveProp(writable: true, onUpdated: 'resetPage')]
    public string $search = '';

    #[LiveProp(writable: true, onUpdated: 'resetPage')]
    public array $filters = [];

    #[LiveProp(writable: true)]
    public array $selectedIds = [];

    public function __construct(
        private CrudHandlerFactory $factory,
        private DoctrineCrudListQueryBuilder $queryBuilder,
        private IndexFieldsNormalizer $fieldsNormalizer,
        private ParameterBagInterface $params,
        private RequestStack $requestStack,
    ) {
    }

    public function getColumns(): array
    {
        $handler = $this->factory->get($this->resource);
        $fields = $handler->getIndexFields();
        $options = method_exists($handler, 'getIndexFieldOptions') ? $handler->getIndexFieldOptions() : [];

        return $this->fieldsNormalizer->normalize($fields, $options);
    }

    public function getPager(): Pagerfanta
    {
        $handler = $this->factory->get($this->resource);

        $adapterClass = null;
        if (\class_exists('Pagerfanta\\Doctrine\\ORM\\QueryAdapter')) {
            $adapterClass = 'Pagerfanta\\Doctrine\\ORM\\QueryAdapter';
        } elseif (\class_exists('Pagerfanta\\Adapter\\Doctrine\\ORM\\QueryAdapter')) {
            $adapterClass = 'Pagerfanta\\Adapter\\Doctrine\\ORM\\QueryAdapter';
        }
        if ($adapterClass === null) {
            throw new \RuntimeException('Missing dependency: install "pagerfanta/doctrine-orm-adapter" to use the NeoxCrud live table.');
        }

        $defaultPerPage = (int) $this->params->get('neox_crud.live_table.default_per_page');
        $maxPerPage = (int) $this->params->get('neox_crud.live_table.max_per_page');

        if ($this->perPage < 1) {
            $this->perPage = $defaultPerPage;
        }
        if ($this->perPage > $maxPerPage) {
            $this->perPage = $maxPerPage;
        }
        if ($this->page < 1) {
            $this->page = 1;
        }

        $cols = $this->getColumns();
        $filters = \is_array($this->filters) ? $this->filters : [];
        $qb = $this->queryBuilder->createForIndex($handler, $cols, $this->sort, $this->direction, $this->search, $filters);

        $pager = new Pagerfanta(new $adapterClass($qb));
        $pager->setMaxPerPage($this->perPage);
        $pager->setCurrentPage($this->page);

        return $pager;
    }

    public function getToolbarButtons(): array
    {
        $handler = $this->factory->get($this->resource);
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [];
        }

        return method_exists($handler, 'getToolbarButtons')
            ? $handler->getToolbarButtons([
                'request'  => $request,
                'resource' => $this->resource,
            ])
            : [];
    }

    public function getBulkActions(): array
    {
        $handler = $this->factory->get($this->resource);

        return method_exists($handler, 'getBulkActions')
            ? $handler->getBulkActions()
            : [];
    }

    public function getRowActionsById(): array
    {
        $handler = $this->factory->get($this->resource);
        $request = $this->requestStack->getCurrentRequest();

        if (!method_exists($handler, 'getRowActionsFor')) {
            return [];
        }

        $out = [];
        foreach ($this->getPager()->getCurrentPageResults() as $it) {
            if (!\is_object($it)) {
                continue;
            }

            $id = null;
            if (method_exists($it, 'getId')) {
                $id = $it->getId();
            } elseif (property_exists($it, 'id')) {
                $id = $it->id;
            }

            $key = $id !== null ? (string) $id : (string) spl_object_id($it);
            $context = [
                'resource' => $this->resource,
            ];
            if ($request) {
                $context['request'] = $request;
            }
            $out[$key] = $handler->getRowActionsFor($it, $context);
        }

        return $out;
    }

    public function getSelectionCount(): int
    {
        return count($this->selectedIds);
    }

    #[LiveAction]
    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    #[LiveAction]
    public function selectAllCurrentPage(): void
    {
        $ids = [];
        foreach ($this->getPager()->getCurrentPageResults() as $it) {
            if (!\is_object($it)) {
                continue;
            }

            $id = null;
            if (method_exists($it, 'getId')) {
                $id = $it->getId();
            } elseif (property_exists($it, 'id')) {
                $id = $it->id;
            }
            if ($id === null) {
                continue;
            }
            $ids[] = (string) $id;
        }

        $this->selectedIds = array_values(array_unique(array_merge(
            array_values(array_map(static fn ($x) => (string) $x, $this->selectedIds)),
            $ids
        )));
    }

    #[LiveAction]
    public function sortBy(#[LiveArg] string $field): void
    {
        $handler = $this->factory->get($this->resource);

        $fields = $handler->getIndexFields();
        $options = method_exists($handler, 'getIndexFieldOptions') ? $handler->getIndexFieldOptions() : [];
        $cols = $this->fieldsNormalizer->normalize($fields, $options);

        if (!$this->fieldsNormalizer->isSortable($cols, $field)) {
            return;
        }

        if ($this->sort === $field) {
            $this->direction = strtolower($this->direction) === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }

        $this->page = 1;
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
    }

    #[LiveAction]
    public function resetPage(): void
    {
        $this->page = 1;
    }

    #[LiveAction]
    public function clearSearch(): void
    {
        $this->search = '';
        $this->page = 1;
    }
}
