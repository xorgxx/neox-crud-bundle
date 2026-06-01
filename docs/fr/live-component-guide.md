# Guide Live Component pour les relations OneToMany

## Vue d'ensemble

NeoxCrudBundle fournit un Live Component générique `neox_crud.collection_live` pour gérer les relations OneToMany avec Symfony UX Live Component. Ce composant permet d'ajouter et de supprimer des items dans une collection **sans écrire de JavaScript**.

---

## Prérequis obligatoires

### Méthodes add/remove sur l'entité parente

Symfony Forms nécessite impérativement les méthodes `addX()` **et** `removeX()` sur l'entité parente pour les collections avec `allow_add: true` / `allow_delete: true`.

```php
// src/Entity/Produit.php
public function addConsommable(ProduitConsommable $pc): self
{
    if (!$this->consommables->contains($pc)) {
        $this->consommables->add($pc);
        $pc->setProduit($this);
    }
    return $this;
}

public function removeConsommable(ProduitConsommable $pc): self
{
    if ($this->consommables->removeElement($pc)) {
        if ($pc->getProduit() === $this) {
            $pc->setProduit(null);
        }
    }
    return $this;
}
```

> **Le Maker le détecte automatiquement.** Si ces méthodes sont manquantes, il affiche un avertissement lors de `make:neox:crud-maker` :
>
> ```
> [WARNING] La relation "consommables" sur App\Entity\Produit nécessite ces méthodes :
>             • removeConsommable()  [non trouvé]
>           Exécutez :
>             php bin/console make:entity --regenerate "App\Entity\Produit"
> ```

---

## Fonctionnement

### 1. Génération par le Maker

Lors de `make:neox:crud-maker`, pour chaque relation OneToMany :

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

En choisissant `[2]`, le Maker :

- Configure le `CollectionType` avec les attributs `data-neox-live-collection`
- Génère le FormType cible (`ProduitConsommableType`) si inexistant
- Vérifie que `addX()` / `removeX()` existent sur l'entité parente

### 2. Architecture du composant

Le composant `neox_crud.collection_live` reçoit **uniquement des scalaires** comme `LiveProp` :

| LiveProp    | Type     | Exemple          | Rôle                              |
|-------------|----------|------------------|-----------------------------------|
| `resource`  | `string` | `"produit"`      | Nom du CrudHandler                |
| `entityId`  | `string` | `"42"`           | ID de l'entité parente            |
| `fieldName` | `string` | `"consommables"` | Nom du champ collection dans le FormType |

> **Pourquoi des scalaires ?** LiveComponent ne peut pas sérialiser un objet `FormInterface` entre les requêtes AJAX. Le composant recrée le formulaire lui-même via `CrudHandlerFactory` dans `instantiateForm()`.

### 3. Appel dans le template

Les templates `form.html.twig` et `form_modal.html.twig` du bundle détectent automatiquement les champs marqués `data-neox-live-collection` et génèrent l'appel :

```twig
{{ component('neox_crud.collection_live', {
    resource:  resource,
    entityId:  entity.id,
    fieldName: child.vars.name
}) }}
{% do child.setRendered() %}
```

> `{% do child.setRendered() %}` est indispensable : il marque le champ comme rendu pour éviter que `form_end(form)` ne le re-rende une seconde fois via `form_rest`.

### 4. Principe de rendu : existant = lecture seule, nouveau = éditable

Le composant générique applique la règle suivante :

| Item | `id` | Rendu | Soumission |
|------|------|-------|------------|
| Existant (déjà persisté) | non null | Widget `disabled` (visible, non modifiable) | `<input type="hidden">` avec la valeur actuelle |
| Nouveau (en cours d'ajout) | `null` | Widget normal (éditable) | Champ de formulaire classique |

> **Pourquoi ?** La collection n'a pas pour rôle de modifier un item existant — seulement d'en ajouter ou d'en retirer. Pour modifier un item, l'utilisateur doit passer par son propre formulaire d'édition.

> **`disabled` ne soumet pas** : le navigateur n'envoie pas la valeur d'un champ `disabled`. C'est pourquoi un `<input type="hidden">` est systématiquement ajouté pour chaque champ d'un item existant afin de préserver sa valeur lors de la soumission.

### 5. Cycle de vie lors d'un add/remove

```
Clic "Ajouter"
  → data-action="live#action" + data-live-action-param="addCollectionItem"
  → LiveCollectionTrait::addCollectionItem(name: "consommables")
  → formValues["consommables"][n] = []
  → instantiateForm() recrée le FormType complet via CrudHandlerFactory
  → template re-rendu : items existants en lecture seule + nouvel item éditable

Clic "×" sur un item
  → data-live-action-param="removeCollectionItem" + data-live-index-param="n"
  → CollectionLiveComponent::removeCollectionItem(name: "consommables", index: n)
  → unset(formValues["consommables"][n])  ← indices préservés avec un trou
  → template re-rendu sans la ligne supprimée
  → lors de la soumission du formulaire parent : Symfony voit l'index n absent
    → appelle removeX() sur l'entité → orphanRemoval: true → DELETE en DB
```

> **Pourquoi `unset` et pas `array_splice` ?** `array_splice` réindexe le tableau ([0,1,2,3] → [0,1,2]). Le formulaire parent utilise les indices pour identifier les entités. Avec réindexation, l'index 2 du formulaire parent reçoit les données de l'entité 3 → corruption de données. `unset` préserve les indices ([0,2,3]), le formulaire parent voit l'index 1 absent et supprime la bonne entité.

---

## Pièges courants

### IntegerType : null interdit

Quand un nouvel item est ajouté, ses champs sont vides. `IntegerType` convertit une valeur vide en `null`. Si le setter PHP est typé `int` (non nullable), cela provoque une `InvalidTypeException`.

**Fix** : ajouter `'empty_data' => 1` (ou `0`) dans le FormType cible :

```php
->add('quantiteParProduit', IntegerType::class, [
    'empty_data' => 1,
    'attr'       => ['min' => 1],
])
```

> Le Maker ajoute automatiquement `empty_data => 0` pour tous les champs `integer/smallint/bigint`.

### CheckboxType : non soumis ≠ false

Un checkbox décoché n'est pas soumis par le navigateur → Symfony reçoit `null` → si le setter est typé `bool`, erreur. Toujours ajouter `'required' => false` :

```php
->add('actif', CheckboxType::class, [
    'required' => false,
])
```

> Le Maker ajoute automatiquement `required => false` pour tous les champs `boolean`.

### CheckboxType dans les items existants (hidden input)

Dans le template, les items existants sont soumis via `<input type="hidden" name="..." value="{{ field.vars.value }}">`. Pour un `CheckboxType`, `field.vars.value` est toujours `1` (la valeur de l'attribut HTML `value`, pas l'état coché/décoché). Pour envoyer l'état réel :

```twig
{% if field.vars.block_prefixes|last == 'checkbox' %}
    {% if field.vars.checked %}
        <input type="hidden" name="{{ field.vars.full_name }}" value="1">
    {% endif %}
    {# Décoché = ne rien envoyer → Symfony interprète comme false #}
{% else %}
    <input type="hidden" name="{{ field.vars.full_name }}" value="{{ field.vars.value }}">
{% endif %}
```

---

## Personnalisation

### Choisir les champs affichés

Modifiez le FormType cible pour limiter les champs :

```php
// src/Form/handlerType/ProduitConsommableType.php
$builder
    ->add('consommable', EntityType::class, [
        'class'        => Consommable::class,
        'choice_label' => 'nom',
        'placeholder'  => 'Choisir un consommable',
    ])
    ->add('quantiteParProduit', IntegerType::class, [
        'label' => 'Qté',
        'attr'  => ['min' => 1],
    ]);
    // Les autres champs (actif, assemblage...) ne sont pas ajoutés = non affichés
```

### Afficher un champ d'une entité liée (table jointure)

Dans une relation OneToMany avec table de jointure (ex: `ProduitConsommable` qui lie `Produit` → `Consommable`), les champs du FormType cible sont par défaut les champs scalaires de la table de jointure. Pour afficher un champ de l'entité liée (ex: `nom` de `Consommable`), utilisez `EntityType` :

```php
// src/Form/handlerType/ProduitConsommableType.php
use App\Entity\Consommable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ProduitConsommableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('consommable', EntityType::class, [
                'class'        => Consommable::class,
                'choice_label' => 'nom',        // affiche le champ "nom" de Consommable
                'placeholder'  => '-- Choisir un consommable --',
                'label'        => 'Consommable',
            ])
            ->add('quantiteParProduit', IntegerType::class, [
                'label' => 'Quantité',
                'attr'  => ['min' => 1, 'style' => 'width: 80px'],
            ]);
    }
}
```

**Résultat** : chaque ligne de la collection affiche un `<select>` avec les noms des consommables et un champ quantité.

#### Afficher plusieurs champs de l'entité liée dans le label

Pour un label combinant plusieurs champs (ex: `réf + nom`), utilisez `choice_label` avec une closure :

```php
->add('consommable', EntityType::class, [
    'class'        => Consommable::class,
    'choice_label' => fn (Consommable $c) => sprintf('[%s] %s', $c->getReference(), $c->getNom()),
    'placeholder'  => '-- Choisir --',
])
```

#### Filtrer les choix disponibles

Pour limiter les consommables à ceux actifs :

```php
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

->add('consommable', EntityType::class, [
    'class'         => Consommable::class,
    'choice_label'  => 'nom',
    'query_builder' => fn (EntityRepository $er): QueryBuilder => $er
        ->createQueryBuilder('c')
        ->where('c.actif = true')
        ->orderBy('c.nom', 'ASC'),
])
```

#### Afficher en lecture seule (pas d'input, pas de select)

Si vous voulez afficher la valeur d'un champ lié **sans permettre sa modification**, deux approches :

**Option A — Champ `disabled` (valeur soumise quand même)**

```php
->add('consommable', EntityType::class, [
    'class'        => Consommable::class,
    'choice_label' => 'nom',
    'attr'         => ['disabled' => true],
])
```

> Le champ est grisé visuellement mais la valeur est quand même soumise.

**Option B — Texte pur dans le template Twig (aucun input généré)**

Surchargez le template du composant et accédez directement à l'objet via `item_form.vars.data` :

```twig
{# templates/components/CollectionLiveComponent.html.twig #}
<div {{ attributes }}>
    {% set collection = form[fieldName] %}

    {% for item_form in collection %}
        <div class="row g-2 align-items-end mb-2">

            {# Afficher le nom du consommable en texte pur, sans widget visible #}
            <div class="col">
                <span class="form-control-plaintext fw-semibold">
                    {{ item_form.vars.data.consommable.nom|default('—') }}
                </span>
                {# Rendre le widget caché pour que la valeur soit soumise avec le formulaire #}
                {# display:none soumet la valeur (contrairement à disabled) #}
                {{ form_widget(item_form.consommable, { attr: { style: 'display:none' } }) }}
            </div>

            {# Rendre les autres champs normalement #}
            <div class="col">
                {{ form_row(item_form.quantiteParProduit, { label: false }) }}
            </div>

            {# form_rest rend les champs restants non encore affichés (hidden, _token, etc.) #}
            {{ form_rest(item_form) }}

            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-action="live#action"
                        data-live-action-param="removeCollectionItem"
                        data-live-name-param="{{ fieldName }}"
                        data-live-index-param="{{ loop.index0 }}">
                    &times;
                </button>
            </div>
        </div>
    {% endfor %}

    {{ form_rest(collection) }}

    <button type="button" class="btn btn-sm btn-outline-primary mt-1"
            data-action="live#action"
            data-live-action-param="addCollectionItem"
            data-live-name-param="{{ fieldName }}">
        + Ajouter
    </button>
</div>
```

> **Points clés de l'Option B :**
> - `item_form.vars.data` retourne l'objet `ProduitConsommable` courant — accès direct à toutes ses propriétés et relations.
> - Les champs sont rendus **explicitement** (pas de `{% for field in item_form %}`), ce qui évite tout double rendu.
> - `form_widget(..., { attr: { style: 'display:none' } })` soumet la valeur sans l'afficher. Ne pas utiliser `disabled` car un champ disabled n'est **pas soumis** par le navigateur.
> - `form_rest(item_form)` attrape les éventuels champs non encore rendus.

**Option C — Champ `mapped: false` (lecture seule, valeur non soumise)**

```php
->add('consommableNom', TextType::class, [
    'mapped' => false,
    'label'  => 'Consommable',
    'attr'   => ['readonly' => true, 'class' => 'form-control-plaintext'],
    'data'   => $options['data']?->getConsommable()?->getNom() ?? '',
])
```

> `mapped: false` signifie que ce champ n'est pas lié à l'entité — il affiche une info mais n'est jamais persisté.

---

#### Utiliser UX Autocomplete pour les grandes listes

Si la liste des consommables est longue, remplacez `EntityType` par `AutocompleteEntityType` :

```php
use Symfony\UX\Autocomplete\Form\AutocompleteEntityType;

->add('consommable', AutocompleteEntityType::class, [
    'class'        => Consommable::class,
    'choice_label' => 'nom',
    'placeholder'  => 'Rechercher un consommable...',
])
```

> Requiert `symfony/ux-autocomplete` installé.

---

### Personnaliser le rendu des lignes

Surchargez le template dans votre application :

```twig
{# templates/components/CollectionLiveComponent.html.twig #}
<div {{ attributes }}>
    {% set collection = form[fieldName] %}
    {{ form_errors(collection) }}

    {% for item_form in collection %}
        <div class="row g-2 align-items-end mb-2 border-bottom pb-2">
            {% for field in item_form %}
                {% if 'hidden' not in field.vars.block_prefixes %}
                    <div class="col">
                        {{ form_row(field, { label: false, row_attr: {class: 'mb-0'} }) }}
                    </div>
                {% else %}
                    {{ form_widget(field) }}
                {% endif %}
            {% endfor %}
            {{ form_rest(item_form) }}
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-danger"
                        data-action="live#action"
                        data-live-action-param="removeCollectionItem"
                        data-live-name-param="{{ fieldName }}"
                        data-live-index-param="{{ loop.index0 }}">
                    Supprimer
                </button>
            </div>
        </div>
    {% endfor %}

    {{ form_rest(collection) }}

    <button type="button" class="btn btn-sm btn-success mt-2"
            data-action="live#action"
            data-live-action-param="addCollectionItem"
            data-live-name-param="{{ fieldName }}">
        + Ajouter
    </button>
</div>
```

---

## Architecture complète

```
NeoxCrudBundle
├── src/Twig/Components/CollectionLiveComponent.php
│     LiveProps: resource (string), entityId (string), fieldName (string)
│     instantiateForm() → CrudHandlerFactory::get(resource)->find(entityId)->createForm()
│
└── templates/components/CollectionLiveComponent.html.twig
      form[fieldName] → accède à la collection dans le formulaire recréé

Application
├── src/Form/ProduitType.php
│     consommables: CollectionType [data-neox-live-collection, allow_add, allow_delete, by_reference: false]
│
├── src/Form/handlerType/ProduitConsommableType.php  ← personnalisable
│
└── src/Entity/Produit.php
      addConsommable() + removeConsommable()  ← OBLIGATOIRES
```

---

## Dépendances requises

```json
"symfony/ux-live-component": "^2.0",
"symfony/ux-twig-component": "^2.0"
```

---

## Limitations

- Le composant générique fonctionne pour les collections simples (1 niveau).
- Pour des collections imbriquées ou une logique métier spécifique, créez votre propre Live Component en héritant de `CollectionLiveComponent` ou en implémentant `LiveCollectionTrait` manuellement.
