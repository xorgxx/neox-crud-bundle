<?= "<?php\n" ?>
declare(strict_types=1);

namespace App\Form;

use <?= $entity_class ?>;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FormType généré par make:neox:crud.
 * Les labels / placeholders / helps sont liés aux clés de traduction générées.
 */
class <?= $form_class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
<?php foreach ($form_fields as $field): ?>
        $builder->add('<?= $field['name'] ?>', <?= $field['type'] !== null ? ('\'' . $field['type'] . '\'') : 'null' ?>, [
<?php if (!empty($field['options']) && is_array($field['options'])): ?>
<?php foreach ($field['options'] as $optKey => $optValue): ?>
<?php
    $isBool  = is_bool($optValue);
    $isNull  = $optValue === null;
    $isArray = is_array($optValue);
    if ($isBool) {
        $phpValue = $optValue ? 'true' : 'false';
    } elseif ($isNull) {
        $phpValue = 'null';
    } elseif ($isArray) {
        // Only support empty array literal emission for now (sufficient for empty_data => [])
        $phpValue = empty($optValue) ? '[]' : '[]';
    } else {
        $phpValue = '\'' . $optValue . '\'';
    }
    ?>
            '<?= $optKey ?>' => <?= $phpValue ?>,
<?php endforeach; ?>
<?php endif; ?>
<?php if (in_array('label', $field_keys, true)): ?>
            'label' => '<?= $resource ?>.field.<?= $field['name'] ?>.label',
<?php endif; ?>
            'attr' => [
<?php if (in_array('placeholder', $field_keys, true)): ?>
                'placeholder' => '<?= $resource ?>.field.<?= $field['name'] ?>.placeholder',
<?php endif; ?>
<?php foreach ($field_keys as $key): if (!in_array($key, ['label', 'placeholder', 'help'], true)): ?>
                '<?= $key ?>' => '<?= $resource ?>.field.<?= $field['name'] ?>.<?= $key ?>',
<?php endif; endforeach; ?>
            ],
<?php if (in_array('help', $field_keys, true)): ?>
            'help' => '<?= $resource ?>.field.<?= $field['name'] ?>.help',
<?php endif; ?>
        ]);
<?php endforeach; ?>
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Use fully-qualified class to avoid namespace resolution like App\\Form\\App\\Entity\\Foo
            'data_class' => \<?= $entity_class ?>::class,
            'translation_domain' => '<?= $resource ?>',
        ]);
    }
}
