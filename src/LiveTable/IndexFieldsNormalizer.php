<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\LiveTable;

final class IndexFieldsNormalizer
{
    public function normalize(array $fields, array $fieldOptions): array
    {
        $out = [];

        foreach ($fields as $name) {
            if (!\is_string($name) || $name == '') {
                continue;
            }

            $opts = $fieldOptions[$name] ?? [];
            if (!\is_array($opts)) {
                $opts = [];
            }

            $label = isset($opts['label']) && \is_string($opts['label']) && $opts['label'] !== '' ? $opts['label'] : $name;
            $queryPath = isset($opts['query_path']) && \is_string($opts['query_path']) && $opts['query_path'] !== '' ? $opts['query_path'] : $name;

            $sortable = isset($opts['sortable']) ? (bool) $opts['sortable'] : false;
            $searchable = isset($opts['searchable']) ? (bool) $opts['searchable'] : false;
            $filter = $opts['filter'] ?? null;

            $join = 'left';
            if (isset($opts['join']) && \is_string($opts['join'])) {
                $j = strtolower($opts['join']);
                if ($j === 'inner' || $j === 'left') {
                    $join = $j;
                }
            }

            $out[] = [
                'name' => $name,
                'label' => $label,
                'options' => $opts,
                'query_path' => $queryPath,
                'sortable' => $sortable,
                'searchable' => $searchable,
                'filter' => $filter,
                'join' => $join,
            ];
        }

        return $out;
    }

    public function isSortable(array $columns, string $name): bool
    {
        foreach ($columns as $col) {
            if (($col['name'] ?? null) === $name) {
                return (bool) ($col['sortable'] ?? false);
            }
        }

        return false;
    }

    public function isSearchable(array $columns, string $name): bool
    {
        foreach ($columns as $col) {
            if (($col['name'] ?? null) === $name) {
                return (bool) ($col['searchable'] ?? false);
            }
        }

        return false;
    }

    public function getSearchableNames(array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            if (!empty($col['searchable']) && isset($col['name']) && \is_string($col['name']) && $col['name'] !== '') {
                $out[] = $col['name'];
            }
        }

        return array_values(array_unique($out));
    }

    public function getFilterConfig(array $columns, string $name): ?array
    {
        foreach ($columns as $col) {
            if (($col['name'] ?? null) === $name) {
                $f = $col['filter'] ?? null;
                return \is_array($f) ? $f : null;
            }
        }

        return null;
    }

    public function getFilterableNames(array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            if (isset($col['filter']) && \is_array($col['filter']) && isset($col['name']) && \is_string($col['name']) && $col['name'] !== '') {
                $out[] = $col['name'];
            }
        }

        return array_values(array_unique($out));
    }

    public function getQueryPath(array $columns, string $name): ?string
    {
        foreach ($columns as $col) {
            if (($col['name'] ?? null) === $name) {
                $qp = $col['query_path'] ?? null;
                return \is_string($qp) && $qp !== '' ? $qp : null;
            }
        }

        return null;
    }

    public function getJoinType(array $columns, string $name): string
    {
        foreach ($columns as $col) {
            if (($col['name'] ?? null) === $name) {
                $j = $col['join'] ?? 'left';
                return $j === 'inner' ? 'inner' : 'left';
            }
        }

        return 'left';
    }
}
