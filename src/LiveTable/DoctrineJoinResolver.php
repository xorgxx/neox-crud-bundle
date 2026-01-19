<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\LiveTable;

use Doctrine\ORM\QueryBuilder;

final class DoctrineJoinResolver
{
    public function resolveField(QueryBuilder $qb, string $rootAlias, string $path, string $joinType = 'left'): string
    {
        if (!str_contains($path, '.')) {
            return $rootAlias . '.' . $path;
        }

        $segments = array_values(array_filter(explode('.', $path), static fn ($s) => $s !== ''));
        if (count($segments) < 2) {
            return $rootAlias . '.' . $path;
        }

        $currentAlias = $rootAlias;
        $currentPathSegments = [];

        $associationSegments = array_slice($segments, 0, -1);
        $field = $segments[count($segments) - 1];

        foreach ($associationSegments as $seg) {
            $currentPathSegments[] = $seg;
            $alias = implode('_', $currentPathSegments);

            $joinExpr = $currentAlias . '.' . $seg;

            if (!$this->hasJoinAlias($qb, $alias, $joinExpr)) {
                if ($joinType === 'inner') {
                    $qb->innerJoin($joinExpr, $alias);
                } else {
                    $qb->leftJoin($joinExpr, $alias);
                }
            }

            $currentAlias = $alias;
        }

        return $currentAlias . '.' . $field;
    }

    private function hasJoinAlias(QueryBuilder $qb, string $alias, string $joinExpr): bool
    {
        $joins = $qb->getDQLPart('join');
        if (!\is_array($joins) || $joins === []) {
            return false;
        }

        foreach ($joins as $rootAlias => $rootJoins) {
            foreach ($rootJoins as $j) {
                if (($j->getAlias() ?? null) === $alias) {
                    return true;
                }
                if (($j->getJoin() ?? null) === $joinExpr && ($j->getAlias() ?? null) === $alias) {
                    return true;
                }
            }
        }

        return false;
    }
}
