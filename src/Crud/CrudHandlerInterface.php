<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contract for a CRUD handler driven by the GenericCrudController.
 */
interface CrudHandlerInterface extends CrudFinderInterface, CrudEditorInterface, CrudActionInterface
{
    /** Resource name, used in the URL: /admin/{resource} */
    public function getName(): string;

    /** Doctrine entity class (FQCN) */
    public function getEntityClass(): string;

    /** FormType class (FQCN) */
    public function getFormType(): string;

    /** Twig templates prefix, e.g. "admin/product" */
    public function getTemplatePrefix(): string;

    /**
     * Returns the list of fields to display in the index view.
     * By default, returns all entity fields except 'id'.
     * Override this method to customize which fields are displayed.
     *
     * @return string[]
     */
    public function getIndexFields(): array;

    /** List all objects (for index) */
    public function findAll(): iterable;

    /**
     * Return a paginated/filterable list based on the HTTP request.
     * Makes it easy to implement pagination.
     */
    public function findList(Request $request): iterable;

    /** Retrieve an object by id */
    public function find(int|string $id): ?object;

    /** Create a new instance of the entity */
    public function createEntity(): object;

    /** Create the form for an entity */
    public function createForm(object $entity): FormInterface;

    /**
     * Process the request on the form (handleRequest + validation).
     * Returns true if submitted + valid.
     */
    public function handleForm(Request $request, FormInterface $form): bool;

    /** Persist the entity (create or update) */
    public function save(object $entity): void;

    /** Delete the entity */
    public function delete(object $entity): void;

    // --- Pre-flush hooks ---

    public function preCreate(object $entity, Request $request): void;

    public function preUpdate(object $entity, Request $request): void;

    public function preDelete(object $entity, Request $request): void;

    // --- Redirect hooks ---

    /** Route + params after creation */
    public function getRedirectAfterCreate(object $entity): array;

    /** Route + params after update */
    public function getRedirectAfterUpdate(object $entity): array;

    /** Route + params after deletion */
    public function getRedirectAfterDelete(object $entity): array;

    /** Route + params after a custom action */
    public function getRedirectAfterAction(string $action, object $entity): array;

    // --- Custom actions ---

    /** Whether the handler supports a given custom action */
    public function supportsAction(string $action, string $method): bool;

    /**
     * Execute a custom action and return a Response.
     * You are free to redirect or render a template.
     */
    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response;
}
