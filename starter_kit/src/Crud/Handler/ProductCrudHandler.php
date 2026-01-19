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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        if ($action === 'publish' && $method === 'GET') {
            return true;
        }

        if ($action === 'bulk_delete' && $method === 'POST') {
            return true;
        }

        return false;
    }

    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response {
        if ($action === 'bulk_delete') {
            if (!$controller->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Access denied for bulk delete.');
            }

            $idsRaw = $request->request->get('ids');

            $ids = [];
            if (\is_string($idsRaw) && $idsRaw !== '') {
                $decoded = json_decode($idsRaw, true);
                if (\is_array($decoded)) {
                    $ids = $decoded;
                }
            } elseif (\is_array($idsRaw)) {
                $ids = $idsRaw;
            }

            $ids = array_values(array_unique(array_filter(array_map(static function ($x) {
                if (\is_int($x) || \is_string($x)) {
                    $s = (string) $x;
                    return $s !== '' ? $s : null;
                }
                return null;
            }, $ids))));

            $deleted = 0;
            foreach ($ids as $oneId) {
                $entity = $this->find($oneId);
                if (!$entity instanceof Product) {
                    continue;
                }
                $this->delete($entity);
                $deleted++;
            }

            $controller->addFlash('success', sprintf('%d produit(s) supprimé(s).', $deleted));

            return $controller->redirectToRoute('neox_crud_admin_crud_index', [
                'resource' => $this->getName(),
            ]);
        }

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
