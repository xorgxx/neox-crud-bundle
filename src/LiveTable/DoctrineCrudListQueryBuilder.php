<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\LiveTable;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Neox\NeoxCrudBundle\Crud\CrudHandlerInterface;

final class DoctrineCrudListQueryBuilder
{
    public function __construct(
        private EntityManagerInterface $em,
        private IndexFieldsNormalizer $fieldsNormalizer,
        private DoctrineJoinResolver $joinResolver,
    ) {
    }

    public function createForIndex(CrudHandlerInterface $handler, array $columns, ?string $sort, string $direction, ?string $search, array $filters): QueryBuilder
    {
        $repo = $this->em->getRepository($handler->getEntityClass());
        $qb = $repo->createQueryBuilder('e');

        $search = $search !== null ? trim($search) : '';
        if ($search !== '') {
            $expr = $qb->expr()->orX();

            foreach ($this->fieldsNormalizer->getSearchableNames($columns) as $fieldName) {
                $path = $this->fieldsNormalizer->getQueryPath($columns, $fieldName);
                if (!$path) {
                    continue;
                }

                $join = $this->fieldsNormalizer->getJoinType($columns, $fieldName);
                $dqlField = $this->joinResolver->resolveField($qb, 'e', $path, $join);
                $expr->add('LOWER(' . $dqlField . ') LIKE :neox_search');
            }

            if ($expr->count() > 0) {
                $qb->andWhere($expr);
                $qb->setParameter('neox_search', '%' . mb_strtolower($search) . '%');
            }
        }

        if ($filters !== []) {
            foreach ($this->fieldsNormalizer->getFilterableNames($columns) as $fieldName) {
                if (!array_key_exists($fieldName, $filters)) {
                    continue;
                }

                $cfg = $this->fieldsNormalizer->getFilterConfig($columns, $fieldName);
                if (!\is_array($cfg) || !isset($cfg['type']) || !\is_string($cfg['type'])) {
                    continue;
                }

                $type = strtolower($cfg['type']);
                $path = $this->fieldsNormalizer->getQueryPath($columns, $fieldName);
                if (!$path) {
                    continue;
                }

                $join = $this->fieldsNormalizer->getJoinType($columns, $fieldName);
                $dqlField = $this->joinResolver->resolveField($qb, 'e', $path, $join);

                if ($type === 'boolean') {
                    $raw = $filters[$fieldName];
                    if ($raw === '' || $raw === null) {
                        continue;
                    }
                    $val = null;
                    if ($raw === '1' || $raw === 1 || $raw === true) {
                        $val = true;
                    }
                    if ($raw === '0' || $raw === 0 || $raw === false) {
                        $val = false;
                    }
                    if ($val === null) {
                        continue;
                    }
                    $param = 'neox_f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
                    $qb->andWhere($dqlField . ' = :' . $param);
                    $qb->setParameter($param, $val);
                    continue;
                }

                if ($type === 'choice') {
                    $raw = $filters[$fieldName];
                    if (!\is_scalar($raw) || (string) $raw === '') {
                        continue;
                    }
                    $param = 'neox_f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
                    $qb->andWhere($dqlField . ' = :' . $param);
                    $qb->setParameter($param, (string) $raw);
                    continue;
                }

                if ($type === 'date') {
                    $raw = $filters[$fieldName];
                    if (!\is_array($raw)) {
                        continue;
                    }

                    $fromRaw = isset($raw['from']) && \is_scalar($raw['from']) ? trim((string) $raw['from']) : '';
                    $toRaw = isset($raw['to']) && \is_scalar($raw['to']) ? trim((string) $raw['to']) : '';

                    if ($fromRaw !== '') {
                        try {
                            $from = new \DateTimeImmutable($fromRaw);
                            $param = 'neox_f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName) . '_from';
                            $qb->andWhere($dqlField . ' >= :' . $param);
                            $qb->setParameter($param, $from);
                        } catch (\Throwable) {
                        }
                    }
                    if ($toRaw !== '') {
                        try {
                            $to = new \DateTimeImmutable($toRaw);
                            $param = 'neox_f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName) . '_to';
                            $qb->andWhere($dqlField . ' <= :' . $param);
                            $qb->setParameter($param, $to);
                        } catch (\Throwable) {
                        }
                    }

                    continue;
                }
            }
        }

        if ($sort && $this->fieldsNormalizer->isSortable($columns, $sort)) {
            $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

            $path = $this->fieldsNormalizer->getQueryPath($columns, $sort);
            if ($path) {
                $join = $this->fieldsNormalizer->getJoinType($columns, $sort);
                $dqlField = $this->joinResolver->resolveField($qb, 'e', $path, $join);
                $qb->addOrderBy($dqlField, $direction);
            }
        }

        return $qb;
    }
}
