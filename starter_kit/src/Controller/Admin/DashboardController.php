<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Neox\NeoxCrudBundle\Crud\CrudHandlerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(CrudHandlerFactory $factory): Response
    {
        $productHandler = $factory->get('product');
        $products = $productHandler->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'products' => $products,
        ]);
    }
}
