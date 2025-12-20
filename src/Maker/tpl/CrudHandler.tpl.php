<?= "<?php\n" ?>
declare(strict_types=1);

namespace <?= $namespace ?>;

use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use <?= $entity_class ?>;
use <?= $form_type ?>;

/**
 * CRUD Handler generated for resource "<?= $resource ?>".
 *
 * How it works (options you can customize here):
 * - getName(): resource slug used in routes, translations, etc.
 * - getTemplatePrefix(): Twig directory prefix (e.g. "admin/<?= $resource ?>")
 * - getEntityClass(): entity FQCN
 * - getFormType(): FormType FQCN
 * - getIndexFields(): choose which fields appear in index table
 * - findList(Request): implement listing, filtering, sorting, pagination
 * - createEntity(): provide a new entity with defaults for "new" action
 * - createForm() / handleForm(): build and process forms
 * - save() / delete(): persistence (already implemented in AbstractDoctrineCrudHandler)
 * - Hooks: preCreate, preUpdate, preDelete, beforeSave, afterSave, beforeDelete, afterDelete
 * - Custom actions: supportsAction() + handleAction()
 * - Redirects: getRedirectAfterCreate/Update/Delete()
 *
 * The controller stays thin and delegates to this handler. Keep business logic here.
 */
#[AutoconfigureTag('neox_crud.handler')]
class <?= $class_name ?> extends AbstractDoctrineCrudHandler
{
    /**
     * Resource slug used in URLs and translation domain.
     */
    public function getName(): string
    {
        return '<?= $resource ?>';
    }

    /**
     * The entity class handled by this CRUD.
     */
    public function getEntityClass(): string
    {
        return \<?= $entity_class ?>::class;
    }

    /**
     * The Symfony FormType used for create/edit forms.
     */
    public function getFormType(): string
    {
        return \<?= $form_type ?>::class;
    }

    /**
     * Twig templates directory (without the leading "templates/").
     * Example: returns "admin/<?= $resource ?>" to target
     * templates/admin/<?= $resource ?>/index.html.twig and form.html.twig
     */
    public function getTemplatePrefix(): string
    {
        return '<?= $template_prefix ?>';
    }

    /**
     * Choose which fields appear in the index view table.
     * By default, the abstract handler returns all fields except 'id'.
     * Uncomment to filter or reorder fields to your needs.
     *
     * New (optional): you can externalize this configuration in a YAML file
     * located next to this handler class. First match wins among:
     *   - <HandlerDir>/config.yaml
     *   - <HandlerDir>/<ClassName>.yaml
     *   - <HandlerDir>/config/crud.yaml
     * with either of these keys:
     *   index_fields: ['id', 'name', 'createdAt']
     *   neox_crud:
     *     index_fields: ['id', 'name', 'createdAt']
     * If such a file exists, it will override the default without code change.
     */
    // public function getIndexFields(): array
    // {
    //     return ['id', 'name', 'createdAt'];
    // }

    /**
     * List provider for the index view: implement pagination, filters, sorting here.
     * Must return an iterable (array, paginator, Doctrine result set, ...).
     *
     * Example implementation (Doctrine ORM):
     */
    // public function findList(Request $request): iterable
    // {
    //     $page  = max(1, (int) $request->query->get('page', 1));
    //     $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
    //     $q     = trim((string) $request->query->get('q', ''));
    //     $sort  = (string) $request->query->get('sort', 'e.id');
    //     $order = strtoupper((string) $request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    //
    //     $qb = $this->getRepository()->createQueryBuilder('e');
    //
    //     if ($q !== '') {
    //         $qb->andWhere('e.name LIKE :q')->setParameter('q', '%'.$q.'%');
    //     }
    //
    //     // Whitelist sort fields for safety
    //     $allowedSorts = ['e.id', 'e.name', 'e.createdAt'];
    //     if (!in_array($sort, $allowedSorts, true)) {
    //         $sort = 'e.id';
    //     }
    //
    //     $qb->orderBy($sort, $order)
    //        ->setFirstResult(($page - 1) * $limit)
    //        ->setMaxResults($limit);
    //
    //     return $qb->getQuery()->getResult();
    // }

    /**
     * Provide a new entity instance for the "new" form.
     * You may pre-fill defaults depending on the current user or request.
     */
    // public function createEntity(): object
    // {
    //     $entity = new \<?= $entity_class ?>();
    //     // $entity->setPublished(true);
    //     return $entity;
    // }

    /**
     * Customize the created form (options, translation domain, etc.).
     */
    // public function createForm(object $entity): \Symfony\Component\Form\FormInterface
    // {
    //     return $this->formFactory->create($this->getFormType(), $entity, [
    //         'translation_domain' => $this->getName(),
    //     ]);
    // }

    /**
     * Process the form submission. Return true when submitted and valid.
     */
    // public function handleForm(Request $request, \Symfony\Component\Form\FormInterface $form): bool
    // {
    //     $form->handleRequest($request);
    //     return $form->isSubmitted() && $form->isValid();
    // }

    /**
     * Redirect targets after successful operations.
     */
    // public function getRedirectAfterCreate(object $entity): array
    // {
    //     return ['neox_crud_admin_crud_edit', [
    //         'resource' => $this->getName(),
    //         'id' => $this->getId($entity),
    //     ]];
    // }
    //
    // public function getRedirectAfterUpdate(object $entity): array
    // {
    //     return ['neox_crud_admin_crud_edit', [
    //         'resource' => $this->getName(),
    //         'id' => $this->getId($entity),
    //     ]];
    // }
    //
    // public function getRedirectAfterDelete(object $entity): array
    // {
    //     return ['neox_crud_admin_crud_index', [
    //         'resource' => $this->getName(),
    //     ]];
    // }

    /**
     * Custom actions
     * - supportsAction(): declare which actions/methods are allowed
     * - handleAction(): execute and return a Response
     */
    public function supportsAction(string $action, string $method): bool
    {
        // Example: enable GET preview
        // return $action === 'preview' && $method === 'GET';
        return false;
    }

    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response {
        // Example implementation for a "preview" action:
        // if ($action === 'preview' && $request->isMethod('GET')) {
        //     $entity = $this->find($id) ?? $this->notFound();
        //     return $controller->render($this->getTemplatePrefix().'/preview.html.twig', [
        //         'entity' => $entity,
        //         'resource' => $this->getName(),
        //     ]);
        // }

        throw new NotFoundHttpException(sprintf(
            'Action "%s" not supported for resource "%s".',
            $action,
            $this->getName()
        ));
    }

    // -----------------------------
    // Hooks — lifecycle customization points
    // -----------------------------
    // protected function beforeSave(object $entity): void {}
    // public function afterSave(object $entity): void {}
    // public function beforeDelete(object $entity): void {}
    // public function afterDelete(object $entity): void {}
    // public function preCreate(object $entity, Request $request): void {}
    // public function preUpdate(object $entity, Request $request): void {}
    // public function preDelete(object $entity, Request $request): void {}
}
