<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Twig\Components;

use Neox\NeoxCrudBundle\Crud\CrudHandlerFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent('neox_crud.collection_live', template: '@NeoxCrud/components/CollectionLiveComponent.html.twig')]
final class CollectionLiveComponent
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public string $resource;

    #[LiveProp]
    public string $entityId;

    #[LiveProp]
    public string $fieldName;

    public function __construct(
        private CrudHandlerFactory $factory,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        $handler = $this->factory->get($this->resource);
        $entity  = $handler->find($this->entityId);

        return $handler->createForm($entity);
    }

    /**
     * Override LiveCollectionTrait::removeCollectionItem to preserve original array indices.
     *
     * The default implementation uses array_splice() which re-indexes the array.
     * Re-indexing breaks the parent form submission: Symfony Form matches submitted
     * keys to entity collection keys by position. If key 1 is deleted and the array
     * is re-indexed [0, 1, 2], key 1 in the parent form gets entity 2's data instead
     * of being removed. Using unset() keeps gaps (e.g. [0, 2, 3]) so the parent form
     * correctly identifies the missing index as the item to delete.
     */
    #[LiveAction]
    public function removeCollectionItem(#[LiveArg] string $name, #[LiveArg] int $index): void
    {
        if (isset($this->formValues[$name]) && \is_array($this->formValues[$name])) {
            unset($this->formValues[$name][$index]);
        }
    }
}
