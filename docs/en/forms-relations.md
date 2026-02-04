# Forms & relations (EN)

This page explains how to handle Doctrine relations in Symfony forms, and how to integrate them cleanly with NeoxCrudBundle (FormType + CrudHandler + generic controller).

Goal
- Clarify where logic belongs (FormType vs CrudHandler vs Controller)
- Provide reliable patterns for ManyToOne, ManyToMany, OneToOne (select and inline)
- Explain which hooks to use for `new/edit/delete`

---

## 1) Responsibility split in NeoxCrudBundle

- FormType
  - defines fields (including relation fields)
  - handles mapping (binding) and validation (Symfony Validator)
  - can consume dynamic options passed by the handler

- CrudHandler
  - recommended place for CRUD/business logic
  - builds the form via `createForm()` (default) or your override
  - prepares the entity for `new` (`createEntity()`)
  - allows adjustments before `edit` (`preUpdate()`) and before `delete` (`beforeDelete()`)

- Controller
  - should stay thin
  - the bundle generic controller orchestrates the flow (index/new/edit/delete)
  - a dedicated controller should be used only for specific needs (security, extra routes, etc.)

---

## 2) How the generic controller runs new/edit/delete

See docs/en/controller.md for the full flow.

Summary
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
  - (CSRF if your template uses it)
  - `delete($entity)`

---

## 3) ManyToOne / OneToOne (select an existing entity)

Use case
- You want a select/autocomplete to choose an existing related entity.

FormType pattern
- Use `Symfony\Bridge\Doctrine\Form\Type\EntityType`
- Provide `class` and `choice_label` (or rely on `__toString()`)

Example
```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder->add('category', EntityType::class, [
    'class' => Category::class,
    'choice_label' => 'name',
    'placeholder' => '—',
    'required' => false,
]);
```

OneToOne note
- Database constraints often enforce uniqueness (a related entity can only be linked once).
- Symfony will not filter out "already used" items by default.
- To avoid Doctrine/SQL errors, filter the list via `query_builder`.

### Complete example: OneToOne select (filter choices) — FormType + CrudHandler

Goal
- The form only proposes "available" entities.
- On edit, the already linked entity must remain selectable.

Recommended pattern
- The handler passes an option to the FormType through `createForm()`.
- The FormType uses a `query_builder` to filter.

CrudHandler example
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

FormType example (excerpt)
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

## 4) ManyToMany (multiple selection)

FormType pattern
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
- `by_reference => false` is often required so Doctrine calls your `add/remove` methods.

---

## 5) OneToOne inline (edit the related object in the same form)

Use case
- You want to edit the related entity fields directly in `new/edit`.

Pattern
- Create a nested form type: `AddressType`, `ProfileType`, etc.
- In the parent form, add it as a field:

```php
$builder->add('address', AddressType::class, [
    'required' => true,
]);
```

Key point
- A nested form needs a non-null object to bind.
- Therefore, you must initialize the relation before rendering the form.

### 5.1) Recommended hook: createEntity()

Use `createEntity()` to initialize the OneToOne for the create screen.

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

### 5.2) Recommended hook: preUpdate()

Use `preUpdate()` to secure the edit screen (legacy data where the relation is null).

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

### Complete example: OneToOne inline (new + edit) — copy/paste-friendly CrudHandler

Goal
- On `new`: the related object must exist so the nested form can bind.
- On `edit`: if the related object is `null` (legacy data), recreate it.

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

## 6) Owner vs inverse side (OneToOne)

Doctrine reminder
- The "owning" side is the one with the `JoinColumn`.
- That side is responsible for updating the FK in the database.

Recommendation
- Implement setters that keep both sides in sync.
- Otherwise, at least enforce consistency in `beforeSave()`.

---

## 7) Delete behavior with OneToOne

Two common strategies
- Also delete the related entity
  - Doctrine: `cascade: ['remove']` on the owning side

- Keep the related entity
  - In `beforeDelete(object $entity)`:
    - unlink the relation (`setXxx(null)`), and optionally sync the other side

### Example: keep the related entity (unlink in beforeDelete)

Use case
- You delete the main entity, but you do not want to delete the related entity.
- Unlink the relation before calling `delete()`.

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

## 8) When to use a dedicated controller

In most cases, you do not need one.

Use a dedicated controller if
- you want different security rules per resource
- you need extra, non-standard routes
- you want to reuse the handler in more complex pages

In any case
- keep business logic in the `CrudHandler`.
