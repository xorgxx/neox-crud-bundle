<?php
declare(strict_types=1);

namespace App\Crud\Handler;

use App\Entity\Product;
use App\Form\ProductType;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductCrudHandler extends AbstractDoctrineCrudHandler
{
    public function getName(): string
    {
        return 'product';
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    public function getFormType(): string
    {
        return ProductType::class;
    }

    public function getTemplatePrefix(): string
    {
        return 'admin/product';
    }

    public function supportsAction(string $action, string $method): bool
    {
        return $action === 'publish' && $method === 'GET';
    }

    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response {
        $entity = $this->find($id);
        if (!$entity instanceof Product) {
            throw new NotFoundHttpException();
        }

        if ($action === 'publish') {
            $entity->setPublished(true);
            $this->save($entity);
            $controller->addFlash('success', 'Produit publié.');
        }

        return $controller->redirectToRoute('neox_crud_admin_crud_index', [
            'resource' => $this->getName(),
        ]);
    }
}
