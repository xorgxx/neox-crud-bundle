# Forms & relations (FR)

Cette page explique comment gérer les relations Doctrine dans vos formulaires Symfony, et comment les intégrer proprement avec NeoxCrudBundle (FormType + CrudHandler + contrôleur générique).

Objectif
- Clarifier où placer la logique (FormType vs CrudHandler vs Controller)
- Donner des patterns fiables pour ManyToOne, ManyToMany, OneToOne (select et inline)
- Expliquer les hooks à utiliser pour `new/edit/delete`

---

## 1) Rappel: qui fait quoi dans NeoxCrudBundle

- FormType
  - définit les champs (y compris les champs de relation)
  - gère le mapping (binding) et la validation (avec Symfony Validator)
  - peut utiliser des options dynamiques (passées par le handler)

- CrudHandler
  - contient la logique métier du CRUD (recommandé)
  - construit le formulaire via `createForm()` (par défaut) ou via une surcharge
  - prépare l'entité pour `new` (`createEntity()`)
  - permet des ajustements avant `edit` (`preUpdate()`) et avant `delete` (`beforeDelete()`)

- Controller
  - idéalement thin
  - le contrôleur générique du bundle orchestre le flux (index/new/edit/delete)
  - un contrôleur dédié ne devrait servir qu'à: sécurité, routes spécifiques, ou pages extra

---

## 2) Comment le contrôleur générique exécute new/edit/delete

Voir docs/fr/controller.md pour le détail du flux.

Résumé
- New
  - `createEntity()`
  - `createForm($entity)`
  - `handleForm($request, $form)`
  - `save($entity)`

- Edit
  - `find($id)`
  - `createForm($entity)`
  - `handleForm($request, $form)`
  - `save($entity)`

- Delete
  - `find($id)`
  - (CSRF si template)
  - `delete($entity)`

---

## 3) ManyToOne / OneToOne (sélection d'une entité existante)

Cas
- Vous voulez un select / autocomplete pour choisir une entité existante.

Pattern FormType
- Utiliser `Symfony\Bridge\Doctrine\Form\Type\EntityType`
- Renseigner `class` et `choice_label` (ou `__toString()` sur l'entité)

Exemple
```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder->add('category', EntityType::class, [
    'class' => Category::class,
    'choice_label' => 'name',
    'placeholder' => '—',
    'required' => false,
]);
```

Remarque OneToOne
- La base impose souvent l'unicité (une entité liée ne peut être associée qu'une seule fois).
- Symfony ne filtre pas tout seul les "déjà utilisés".
- Pour éviter une erreur Doctrine/SQL, filtrez via `query_builder`.

### Exemple complet: OneToOne en select (filtrer les choix) — FormType + CrudHandler

Objectif
- Le formulaire propose une liste d'entités "disponibles".
- En mode édition, il faut aussi autoriser l'entité déjà liée à l'objet courant.

Pattern recommandé
- Le handler passe une option au FormType via `createForm()`.
- Le FormType utilise `query_builder` pour filtrer.

Exemple CrudHandler
```php
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\Form\FormInterface;

final class UserProfileCrudHandler extends AbstractDoctrineCrudHandler
{
    public function getName(): string
    {
        return 'user-profile';
    }

    public function getEntityClass(): string
    {
        return \App\Entity\UserProfile::class;
    }

    public function getFormType(): string
    {
        return \App\Form\UserProfileType::class;
    }

    public function createForm(object $entity): FormInterface
    {
        return $this->formFactory->create($this->getFormType(), $entity, [
            'current_profile' => $entity,
        ]);
    }
}
```

Exemple FormType (extrait)
```php
use App\Entity\User;
use App\Entity\UserProfile;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $current = $options['current_profile'];

    $builder->add('user', EntityType::class, [
        'class' => User::class,
        'choice_label' => 'email',
        'required' => false,
        'query_builder' => function (UserRepository $repo) use ($current) {
            $qb = $repo->createQueryBuilder('u')
                ->leftJoin('u.profile', 'p');

            if ($current instanceof UserProfile && $current->getUser() !== null) {
                $qb->andWhere('p IS NULL OR u = :currentUser')
                   ->setParameter('currentUser', $current->getUser());
            } else {
                $qb->andWhere('p IS NULL');
            }

            return $qb;
        },
    ]);
}

public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => UserProfile::class,
        'current_profile' => null,
    ]);
}
```

---

## 4) ManyToMany (sélection multiple)

Pattern FormType
```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder->add('tags', EntityType::class, [
    'class' => Tag::class,
    'choice_label' => 'name',
    'multiple' => true,
    'expanded' => false,
    'by_reference' => false,
    'required' => false,
]);
```

Notes
- `by_reference => false` est souvent nécessaire pour que Doctrine appelle vos `addTag/removeTag`.

---

## 5) OneToOne inline (édition de l'objet lié dans le même form)

Cas
- Vous voulez éditer les champs de l'entité liée directement dans `new/edit`.

Pattern
- Créer un sous-form `AddressType`, `ProfileType`, etc.
- Dans le FormType parent, utiliser le sous-form:

```php
$builder->add('address', AddressType::class, [
    'required' => true,
]);
```

Point clé
- Le sous-form a besoin d'un objet (non null) pour binder.
- Donc il faut initialiser la relation avant d'afficher le form.

### 5.1) Hook recommandé: createEntity()

Utiliser `createEntity()` pour initialiser le OneToOne en création.

```php
public function createEntity(): object
{
    $entity = new Coproperty();

    if ($entity->getAddress() === null) {
        $entity->setAddress(new Address());
    }

    return $entity;
}
```

### 5.2) Hook recommandé: preUpdate()

Utiliser `preUpdate()` pour sécuriser l'édition (données existantes où la relation est null).

```php
public function preUpdate(object $entity, Request $request): void
{
    if (!$entity instanceof Coproperty) {
        return;
    }

    if ($entity->getAddress() === null) {
        $entity->setAddress(new Address());
    }
}
```

### Exemple complet: OneToOne inline (new + edit) — CrudHandler quasi copiable

Objectif
- En `new`: l'objet lié doit exister pour que le sous-form puisse binder.
- En `edit`: si l'objet lié est `null` (données legacy), on le recrée.

```php
use App\Entity\Address;
use App\Entity\Coproperty;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\HttpFoundation\Request;

final class CopropertyCrudHandler extends AbstractDoctrineCrudHandler
{
    public function getName(): string
    {
        return 'coproperty';
    }

    public function getEntityClass(): string
    {
        return Coproperty::class;
    }

    public function getFormType(): string
    {
        return \App\Form\CopropertyType::class;
    }

    public function createEntity(): object
    {
        $entity = new Coproperty();

        if ($entity->getAddress() === null) {
            $entity->setAddress(new Address());
        }

        return $entity;
    }

    public function preUpdate(object $entity, Request $request): void
    {
        if (!$entity instanceof Coproperty) {
            return;
        }

        if ($entity->getAddress() === null) {
            $entity->setAddress(new Address());
        }
    }
}
```

---

## 6) Propriétaire vs inverse (OneToOne)

Rappel Doctrine
- Le côté "propriétaire" du OneToOne est celui qui porte la `JoinColumn`.
- C'est ce côté qui pilote la mise à jour de la FK en base.

Conseil
- Ayez des setters qui synchronisent les deux côtés.
- Sinon, au minimum, dans `beforeSave()` forcez la cohérence avant flush.

---

## 7) Delete et OneToOne

Deux stratégies
- Supprimer aussi l'entité liée
  - Doctrine: `cascade: ['remove']` côté propriétaire

- Conserver l'entité liée
  - Dans `beforeDelete(object $entity)`:
    - casser la relation (`setXxx(null)`), et éventuellement synchroniser l'autre côté

### Exemple: conserver l'entité liée (unlink dans beforeDelete)

Cas
- Vous supprimez l'entité principale, mais vous ne voulez pas supprimer l'entité liée.
- Vous cassez le lien avant l'appel à `delete()`.

```php
use App\Entity\Unit;

protected function beforeDelete(object $entity): void
{
    if (!$entity instanceof Unit) {
        return;
    }

    $entity->setOwnerAddress(null);
}
```

---

## 8) Quand utiliser un controller dédié

Dans la majorité des cas, ce n'est pas nécessaire.

Utiliser un controller dédié si
- vous voulez des règles de sécurité différentes selon la ressource
- vous voulez ajouter des routes non standards
- vous voulez réutiliser le handler dans des pages plus complexes

Dans tous les cas
- garder la logique métier dans le `CrudHandler`.
