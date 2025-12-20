<?= "<?php\n" ?>
declare(strict_types=1);

namespace App\Controller;

use Neox\NeoxCrudBundle\Controller\GenericCrudController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/<?= $resource ?>', name: 'neox_crud_admin_crud_')]
/** #[IsGranted('ROLE_ADMIN')] **/
final class <?= $class_name ?> extends GenericCrudController
{
    // Rien d’autre: toute la logique est dans le <?= $resource?>CrudHandler
}
