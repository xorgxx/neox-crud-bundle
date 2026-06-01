# Live Component Guide for OneToMany Relations

## Overview

NeoxCrudBundle provides a generic Live Component `neox_crud.collection_live` to handle OneToMany relations with Symfony UX Live Component. This component allows adding and removing items in a collection without writing JavaScript.

## How it Works

### 1. Generation by the Maker

When you choose the `live-component` option for a OneToMany relation:

```
Relation "consommables" (OneToMany, targetEntity: App\Entity\ProduitConsommable)
FormType cible déduit : App\Form\handlerType\ProduitConsommableType
Type d'intégration ?
  [1] CollectionType (inline editing with entry_type, requires custom JavaScript)
  [2] Live Component CollectionType (zero JavaScript, requires symfony/ux-live-component)
  [3] UX Autocomplete (AutocompleteEntityType with multiple=true)
  [4] Custom/Complex (skip, implement manually)
  [5] Skip this relation
```

The Maker generates:

- **Main FormType** (`src/Form/ProduitType.php`) with configured CollectionType
- **Target FormType** (`src/Form/handlerType/ProduitConsommableType.php`) if it doesn't exist
- **Main template** automatically uses `{{ component('neox_crud.collection_live', { form: form.consommables }) }}`

### 2. Generic Live Component

The Live Component `neox_crud.collection_live` is provided by NeoxCrudBundle:

- **PHP**: `NeoxCrudBundle\Twig\Components\CollectionLiveComponent`
- **Twig**: `NeoxCrudBundle/templates/components/CollectionLiveComponent.html.twig`

It works with any CollectionType and uses `LiveCollectionTrait` to handle adding/removing items.

## Customization Points

### 1. Choose fields to display

The generated target FormType contains all scalar fields of the entity. You can modify this FormType to display only certain fields:

```php
// src/Form/handlerType/ProduitConsommableType.php
class ProduitConsommableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite')
            ->add('consommable')
            // ->add('dateAjout')  // Commented = not displayed
            // ->add('commentaire'); // Commented = not displayed
    }
}
```

### 2. Customize field rendering

You can modify the options of each field in the target FormType:

```php
$builder
    ->add('quantite', IntegerType::class, [
        'label' => 'Quantity',
        'attr' => ['min' => 1, 'max' => 100],
    ])
    ->add('consommable', EntityType::class, [
        'class' => Consommable::class,
        'choice_label' => 'nom',
        'placeholder' => 'Choose a consumable',
    ]);
```

### 3. Customize the Live Component template

If you need specific rendering, you can override the Live Component template in your application:

```twig
{# templates/components/CollectionLiveComponent.html.twig #}
{% extends '@NeoxCrudBundle/components/CollectionLiveComponent.html.twig' %}

{% block collection_item %}
    <tr>
        <td class="col-md-8">
            {{ form_row(item_form, { label: false, row_attr: {class: 'mb-1'} }) }}
        </td>
        <td class="col-md-4">
            {{ form_row(item_form.vars.button_delete, {label: 'Delete', attr: {class: 'btn btn-danger'}}) }}
        </td>
    </tr>
{% endblock %}
```

### 4. Customize buttons

You can modify button labels and CSS classes by overriding the template:

```twig
{{ form_widget(child.vars.button_add, {label: 'Add item', attr: {class: 'btn btn-success'}}) }}
{{ form_row(item_form.vars.button_delete, {label: 'Delete', attr: {class: 'btn btn-danger'}}) }}
```

### 5. Add validations

Add validation constraints in the target FormType:

```php
$builder
    ->add('quantite', IntegerType::class, [
        'constraints' => [
            new NotBlank(),
            new GreaterThan(0),
        ],
    ]);
```

### 6. Customize the main template

If you need specific logic for a particular collection, you can modify the generated template:

```twig
{# templates/admin/produit/edit.html.twig #}
{{ form_start(form) }}
  {{ form_row(form.nom) }}
  
  {# Collection with live-component #}
  <div class="special-collection">
    {{ component('neox_crud.collection_live', { form: form.consommables }) }}
  </div>
{{ form_end(form) }}
```

## Architecture

```
NeoxCrudBundle
├── src/Twig/Components/CollectionLiveComponent.php (generic)
└── templates/components/CollectionLiveComponent.html.twig (generic)

Generated application
├── src/Form/ProduitType.php (configured CollectionType)
├── src/Form/handlerType/ProduitConsommableType.php (customizable)
└── templates/admin/produit/edit.html.twig (uses component())
```

## Dependencies

- `symfony/ux-live-component`: required for Live Component
- `symfony/ux-twig-component`: required for Twig components

## Migration from classic CollectionType

If you already have a form with classic CollectionType and want to switch to Live Component:

1. Choose the `live-component` option when regenerating the CRUD
2. The Maker will automatically configure the CollectionType
3. The template will automatically use the generic Live Component
4. Customize the target FormType according to your needs

## Limitations

- The generic Live Component only handles simple collections
- For complex cases (nested collections, specific business logic), you will need to create your own Live Component
