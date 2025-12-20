<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud;

/**
 * Read-only part of a CRUD handler: listing and single-entity retrieval.
 */
interface CrudFinderInterface
{
    /**
     * Return a list of entities to display in the index.
     *
     * Implementations are encouraged to paginate the results.
     */
    public function findAll(): iterable;

    /**
     * Find a single entity by its identifier.
     *
     * @param int|string $id
     */
    public function find(int|string $id): ?object;
}
