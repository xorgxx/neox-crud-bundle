<?= "<?php\n" ?>
declare(strict_types=1);

namespace App\Twig\Components;

use <?= $entity_class ?>;
use App\Form\<?= $entity_short_name ?>Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class <?= ucfirst($field_name) ?>Collection extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: '<?= $field_name ?>')]
    public ?<?= $entity_short_name ?> $<?= strtolower($entity_short_name) ?> = null;

    /**
     * @return FormInterface<<?= $entity_short_name ?>>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            <?= $entity_short_name ?>Type::class,
            $this-><?= strtolower($entity_short_name) ?>
        );
    }
}
