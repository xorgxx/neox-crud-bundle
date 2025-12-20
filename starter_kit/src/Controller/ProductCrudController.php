<?php
declare(strict_types=1);

namespace App\Controller;

use Neox\NeoxCrudBundle\Controller\GenericCrudController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/product', name: 'app_product_')]
final class ProductCrudController extends GenericCrudController
{
}
