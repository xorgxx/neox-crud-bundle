<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Maker\Bridge;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Neox\NeoxCrudBundle\Maker\Contract\DoctrineEntityHelperInterface;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;

/**
 * Adapter around Symfony MakerBundle's DoctrineHelper (@internal) so that
 * this bundle does not type-hint it directly.
 */
final class MakerBundleDoctrineHelperAdapter implements DoctrineEntityHelperInterface
{
    public function __construct(private DoctrineHelper $inner)
    {
    }

    public function getEntityNamespace(): string
    {
        return $this->inner->getEntityNamespace();
    }

    /**
     * @param class-string $class
     *
     * @return ClassMetadata<object>
     */
    public function getMetadata(string $class): ClassMetadata
    {
        /** @var ClassMetadata<object> $meta */
        $meta = $this->inner->getMetadata($class);

        return $meta;
    }
}
