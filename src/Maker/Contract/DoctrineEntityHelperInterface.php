<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Maker\Contract;

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Small abstraction to avoid relying on MakerBundle's internal DoctrineHelper.
 *
 * Minimal surface used by this bundle's makers.
 */
interface DoctrineEntityHelperInterface
{
    /**
     * Returns the default namespace used for entities when a short class name is provided.
     */
    public function getEntityNamespace(): string;

    /**
     * Resolve Doctrine metadata for an entity class.
     *
     * @param class-string $class
     *
     * @return ClassMetadata<object>
     */
    public function getMetadata(string $class): ClassMetadata;
}
