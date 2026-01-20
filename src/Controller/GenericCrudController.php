<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Controller;

use Neox\NeoxCrudBundle\Crud\CrudHandlerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Generic controller for all Neox CRUDs.
 *
 * Expose it in your routing:
 *
 * neox_crud:
 *   resource: '@NeoxCrudBundle/Controller/'
 *   type: attribute
 *   prefix: /
 */

#[Route('/admin/{resource}', name: 'neox_crud_admin_crud_')]
class GenericCrudController extends AbstractController
{
    public function __construct(
        private CrudHandlerFactory $factory,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(string $resource, Request $request): Response
    {
        $handler = $this->factory->get($resource);

        $items = $handler->findList($request);

        // Optional UI config (actions, toolbar, bulk) — only if handler provides helpers
        $toolbarButtons = method_exists($handler, 'getToolbarButtons') ? $handler->getToolbarButtons([
            'request'  => $request,
            'resource' => $resource,
        ]) : [];
        $bulkActions = method_exists($handler, 'getBulkActions') ? $handler->getBulkActions() : [];

        // Build a per-entity row actions map lazily to avoid BC impact when unused
        $rowActionsById = [];
        if (method_exists($handler, 'getRowActionsFor')) {
            foreach ($items as $it) {
                if (!\is_object($it)) {
                    // Skip non-object items to satisfy static analysis and avoid runtime issues
                    continue;
                }

                // Try to extract an identifier using available helpers
                $id = null;
                if (method_exists($it, 'getId')) {
                    $id = $it->getId();
                } elseif (property_exists($it, 'id')) {
                    $id = $it->id;
                }
                $rowActionsById[$id ?? spl_object_id($it)] = $handler->getRowActionsFor($it, [
                    'request'  => $request,
                    'resource' => $resource,
                ]);
            }
        }

        $liveEnabled = null;
        if (method_exists($handler, 'getLiveTableEnabled')) {
            $liveEnabled = $handler->getLiveTableEnabled();
        }
        if ($liveEnabled === null) {
            $liveEnabled = (bool) $this->getParameter('neox_crud.live_table.enabled');
        }

        $depsOk = \class_exists('Symfony\\UX\\LiveComponent\\Attribute\\AsLiveComponent')
            && \class_exists('Pagerfanta\\Pagerfanta')
            && (\class_exists('Pagerfanta\\Doctrine\\ORM\\QueryAdapter')
                || \class_exists('Pagerfanta\\Adapter\\Doctrine\\ORM\\QueryAdapter'));

        $baseLayout = $this->getParameter('neox_crud.makers.base_layout');
        if (!\is_string($baseLayout) || $baseLayout === '') {
            $baseLayout = '@NeoxCrud/admin/_layout.html.twig';
        }

        if ($liveEnabled && $depsOk) {
            return $this->render('@NeoxCrud/neox_crud/index_live.html.twig', [
                'items'    => $items,
                'resource' => $resource,
                'fields'   => $handler->getIndexFields(),
                'field_options' => method_exists($handler, 'getIndexFieldOptions') ? $handler->getIndexFieldOptions() : [],
                'handler' => $handler,
                'toolbarButtons' => $toolbarButtons,
                'bulkActions'    => $bulkActions,
                'rowActionsById' => $rowActionsById,
                'base_layout' => $baseLayout,
            ]);
        }

        return $this->render('@NeoxCrud/neox_crud/index_classic.html.twig', [
            'items'    => $items,
            'resource' => $resource,
            'fields'   => $handler->getIndexFields(),
            // Optional: per-field options coming from handler YAML config
            'field_options' => method_exists($handler, 'getIndexFieldOptions') ? $handler->getIndexFieldOptions() : [],
            // Provide the handler for advanced templates (optional)
            'handler' => $handler,
            // Optional UI config variables (empty arrays if not configured)
            'toolbarButtons' => $toolbarButtons,
            'bulkActions'    => $bulkActions,
            'rowActionsById' => $rowActionsById,
            'base_layout' => $baseLayout,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(string $resource, Request $request): Response
    {
        $handler = $this->factory->get($resource);
        $entity  = $handler->createEntity();
        $form    = $handler->createForm($entity);

        if ($handler->handleForm($request, $form)) {
            $handler->preCreate($entity, $request);
            $handler->save($entity);
            $this->addFlash('success', 'Création effectuée.');

            [$route, $params] = $handler->getRedirectAfterCreate($entity);

            return $this->redirectToRoute($route, $params);
        }

        $baseLayout = $this->getParameter('neox_crud.makers.base_layout');
        if (!\is_string($baseLayout) || $baseLayout === '') {
            $baseLayout = '@NeoxCrud/admin/_layout.html.twig';
        }

        return $this->render('@NeoxCrud/neox_crud/form.html.twig', [
            'form'     => $form->createView(),
            'entity'   => $entity,
            'resource' => $resource,
            'base_layout' => $baseLayout,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(string $resource, int|string $id, Request $request): Response
    {
        $handler = $this->factory->get($resource);
        $entity  = $handler->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $form = $handler->createForm($entity);

        if ($handler->handleForm($request, $form)) {
            $handler->preUpdate($entity, $request);
            $handler->save($entity);
            $this->addFlash('success', 'Mise à jour effectuée.');

            [$route, $params] = $handler->getRedirectAfterUpdate($entity);

            return $this->redirectToRoute($route, $params);
        }

        $baseLayout = $this->getParameter('neox_crud.makers.base_layout');
        if (!\is_string($baseLayout) || $baseLayout === '') {
            $baseLayout = '@NeoxCrud/admin/_layout.html.twig';
        }

        return $this->render('@NeoxCrud/neox_crud/form.html.twig', [
            'form'     => $form->createView(),
            'entity'   => $entity,
            'resource' => $resource,
            'base_layout' => $baseLayout,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(string $resource, int|string $id, Request $request): Response
    {
        $handler = $this->factory->get($resource);
        $entity  = $handler->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_' . $id, (string) $request->request->get('_token'))) {
            $handler->preDelete($entity, $request);
            $handler->delete($entity);
            $this->addFlash('success', 'Suppression effectuée.');
        }

        [$route, $params] = $handler->getRedirectAfterDelete($entity);

        return $this->redirectToRoute($route, $params);
    }

    #[Route('/{id}/{action}', name: 'custom', methods: ['GET', 'POST'])]
    public function custom(string $resource, int|string $id, string $action, Request $request): Response
    {
        $handler = $this->factory->get($resource);

        if (!$handler->supportsAction($action, $request->getMethod())) {
            throw $this->createNotFoundException(sprintf(
                'Action "%s" non supportée pour la ressource "%s".',
                $action,
                $resource
            ));
        }

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('custom_' . $resource . '_' . $id . '_' . $action, $token)) {
                throw $this->createAccessDeniedException('Invalid CSRF token for custom CRUD action.');
            }
        }

        return $handler->handleAction($action, $id, $request, $this);
    }
}
