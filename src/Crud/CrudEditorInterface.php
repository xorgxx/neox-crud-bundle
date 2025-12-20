<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud;

/**
 * Write part of a CRUD handler: persistence and deletion.
 */
interface CrudEditorInterface
{
    /**
     * Persist an entity (create or update).
     */
    public function save(object $entity): void;

    /**
     * Delete an entity.
     */
    public function delete(object $entity): void;
}
