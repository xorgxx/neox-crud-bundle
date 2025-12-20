<?php
declare(strict_types=1);

namespace App\Crud;

use App\Entity\Product;
use App\Form\ProductType;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductCrudHandler extends AbstractDoctrineCrudHandler
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

    public function preCreate(object $entity, Request $request): void
    {
        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }
    }

    public function supportsAction(string $action, string $method): bool
    {
        return $action === 'publish' && $method === 'POST';
    }

    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response {
        /** @var Product|null $product */
        $product = $this->find($id);

        if (!$product) {
            throw new NotFoundHttpException();
        }

        if ($action === 'publish') {
            if (method_exists($product, 'setPublished')) {
                $product->setPublished(true);
            }
            $this->save($product);

            $controller->addFlash('success', 'Produit publié !');

            return $controller->redirectToRoute('app_product_index', [
                'resource' => 'product',
            ]);
        }

        throw new NotFoundHttpException();
    }
}
