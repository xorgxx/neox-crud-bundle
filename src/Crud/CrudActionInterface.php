<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Crud;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom actions support for a CRUD handler.
 */
interface CrudActionInterface
{
    /**
     * Whether the handler supports a given custom action for a given HTTP method.
     */
    public function supportsAction(string $action, string $method): bool;

    /**
     * Execute a custom action and return a Response.
     *
     * @param string $action
     * @param int|string $id
     * @param Request $request
     * @param AbstractController $controller
     * @return Response
     */
    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response;
}
