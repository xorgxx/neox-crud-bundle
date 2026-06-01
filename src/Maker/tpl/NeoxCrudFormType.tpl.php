<?= "<?php\n" ?>
declare(strict_types=1);

namespace <?= $form_namespace ?? 'App\\Form' ?>;

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
<?php if (isset($field['live_component']) && $field['live_component']): ?>
        // CollectionType with Live Component (Symfony UX Live Component)
        // Use {{ component('neox_crud.collection_live', { form: form.<?= $field['name'] ?> }) }} in your Twig template
<?php endif; ?>
        $builder->add('<?= $field['name'] ?>', <?= $field['type'] !== null ? ('\'' . $field['type'] . '\'') : 'null' ?>, [
<?php if (!empty($field['options']) && is_array($field['options'])): ?>
<?php foreach ($field['options'] as $optKey => $optValue): ?>
<?php if (in_array($optKey, ['attr', 'row_attr'], true)) { continue; } ?>
<?php
    $isBool  = is_bool($optValue);
    $isNull  = $optValue === null;
    $isArray = is_array($optValue);
    $isScalar = is_string($optValue) || is_int($optValue) || is_float($optValue);

    $emitArray = static function (array $arr) use (&$emitArray): string {
        $isList = array_keys($arr) === range(0, count($arr) - 1);

        $parts = [];
        foreach ($arr as $k => $v) {
            if (is_bool($v)) {
                $vPhp = $v ? 'true' : 'false';
            } elseif ($v === null) {
                $vPhp = 'null';
            } elseif (is_array($v)) {
                $vPhp = $emitArray($v);
            } elseif (is_int($v) || is_float($v)) {
                $vPhp = (string) $v;
            } else {
                $vPhp = '\'' . str_replace("'", "\\'", (string) $v) . '\'';
            }

            if ($isList) {
                $parts[] = $vPhp;
            } else {
                $kPhp = '\'' . str_replace("'", "\\'", (string) $k) . '\'';
                $parts[] = $kPhp . ' => ' . $vPhp;
            }
        }

        return '[' . implode(', ', $parts) . ']';
    };
    if ($isBool) {
        $phpValue = $optValue ? 'true' : 'false';
    } elseif ($isNull) {
        $phpValue = 'null';
    } elseif ($isArray) {
        $phpValue = $emitArray($optValue);
    } elseif ($isScalar) {
        $phpValue = is_string($optValue)
            ? ('\'' . str_replace("'", "\\'", $optValue) . '\'')
            : (string) $optValue;
    } else {
        $phpValue = '\'' . str_replace("'", "\\'", (string) $optValue) . '\'';
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
<?php if (!empty($field['options']['attr']) && is_array($field['options']['attr'])): ?>
<?php foreach ($field['options']['attr'] as $attrKey => $attrValue): ?>
<?php
    if (is_bool($attrValue)) {
        $attrPhp = $attrValue ? 'true' : 'false';
    } elseif ($attrValue === null) {
        $attrPhp = 'null';
    } elseif (is_int($attrValue) || is_float($attrValue)) {
        $attrPhp = (string) $attrValue;
    } else {
        $attrPhp = '\'' . str_replace("'", "\\'", (string) $attrValue) . '\'';
    }
?>
                '<?= $attrKey ?>' => <?= $attrPhp ?>,
<?php endforeach; ?>
<?php endif; ?>
<?php foreach ($field_keys as $key): if (!in_array($key, ['label', 'placeholder', 'help'], true)): ?>
                '<?= $key ?>' => '<?= $resource ?>.field.<?= $field['name'] ?>.<?= $key ?>',
<?php endif; endforeach; ?>
            ],
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
