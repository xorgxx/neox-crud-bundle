Contrôleur CRUD générique (FR)

Cette page documente le contrôleur du bundle `Neox\NeoxCrudBundle\Controller\GenericCrudController`.

Objectif
- Fournir un point d’entrée unique pour toutes les routes CRUD `/admin/{resource}`.
- Résoudre automatiquement le bon handler via `CrudHandlerFactory`.
- Fournir les actions CRUD classiques (index/new/edit/delete) et les actions custom.
- Optionnellement : rendre l’index en mode LiveTable (Pagerfanta + Symfony UX LiveComponent) quand activé.

---

1) Routes exposées

Le contrôleur est basé sur le paramètre de route `resource`.

Routes exposées (par défaut) :
- `/admin/{resource}` (index)
- `/admin/{resource}/new`
- `/admin/{resource}/{id}/edit`
- `/admin/{resource}/{id}/delete`
- `/admin/{resource}/{id}/{action}` (actions personnalisées)

Important
- La variable `{resource}` correspond au nom de ressource renvoyé par votre handler (`CrudHandlerInterface::getName()`).
- La variable `{id}` supporte `int|string` (UUID/ULID).

---

2) Routage Symfony

Vous devez importer les routes du bundle.

Exemple recommandé :
```yaml
# config/routes/neox_crud.yaml
neox_crud:
  resource: '@NeoxCrudBundle/Controller/'
  type: attribute
  prefix: /
```

---

3) Résolution du handler

Le contrôleur ne connaît pas vos entités.
Il résout le handler à partir de `resource` via `CrudHandlerFactory`.

Le handler doit :
- implémenter `CrudHandlerInterface`
- être enregistré comme service (autowire/autoconfigure recommandé)
- fournir :
  - `getName()` (nom de ressource)
  - `getEntityClass()` (FQCN)
  - `getFormType()` (FQCN)

---

4) Flux d’exécution des actions

4.1) Index
- Appelle `findList(Request $request)` sur le handler.
- Rend soit :
  - un template “classique” (liste simple)
  - soit la LiveTable si activée et si les dépendances sont installées.

4.2) New
- Crée l’entité via `createEntity()`.
- Construit le formulaire via `createForm($entity)`.
- Traite la soumission via `handleForm($request, $form)`.
- Persiste via `save($entity)`.

4.3) Edit
- Récupère l’entité via `find($id)`.
- Crée et traite le formulaire (comme `new`).
- Persiste via `save($entity)`.

4.4) Delete
- Récupère l’entité via `find($id)`.
- Vérifie le CSRF si votre template en envoie un.
- Supprime via `delete($entity)`.

4.5) Custom action
- Le contrôleur délègue au handler via `supportsAction($action, $method)` puis `handleAction(...)`.

---

4.6) Garder la main sur le formulaire (validation, champs, options)

Le contrôleur ne construit jamais le formulaire “en dur”.
Il appelle systématiquement votre handler via `createForm($entity)`.

Par défaut (dans `AbstractDoctrineCrudHandler`), `createForm()` fait simplement :

```php
public function createForm(object $entity): FormInterface
{
    return $this->formFactory->create($this->getFormType(), $entity);
}
```

Si vous avez besoin de garder la main sur :
- les groupes de validation,
- des attributs HTML sur le `<form>`,
- des options dynamiques (mode admin/public, création/édition, etc.),

vous pouvez surcharger `createForm()` dans votre handler.

Exemple : forcer des groupes de validation

```php
public function createForm(object $entity): FormInterface
{
    return $this->formFactory->create($this->getFormType(), $entity, [
        'validation_groups' => ['Default', 'admin'],
    ]);
}
```

Explications

- `validation_groups` : indique à Symfony Validator quels groupes de contraintes appliquer.
  Les contraintes définies avec `groups: ['admin']` seront validées uniquement si vous ajoutez ce groupe.
- `attr` : permet d’ajouter des attributs HTML au tag `<form>` (classes, `novalidate`, data-attributes, etc.).
- options “custom” : vous pouvez inventer vos propres options et les utiliser dans votre `FormType` pour afficher/retirer des champs.

Exemple : option custom `mode` consommée par le `FormType`

```php
public function createForm(object $entity): FormInterface
{
    return $this->formFactory->create($this->getFormType(), $entity, [
        'mode' => 'admin',
    ]);
}
```

Dans votre `FormType`, il faut déclarer l’option :

```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'mode' => null,
    ]);
}
```

Puis l’utiliser dans `buildForm()` :

```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder->add('name');

    if ($options['mode'] === 'admin') {
        $builder->add('internalCode');
    }
}
```

Note

- Pour garder la main sur l’ordre/la mise en page des champs, vous pouvez aussi surcharger les templates Twig du bundle (`@NeoxCrud/neox_crud/form.html.twig` et `@NeoxCrud/neox_crud/form_modal.html.twig`) et rendre les champs avec `form_row()` au lieu de `form_widget(form)`.

5) LiveTable vs rendu classique

Le contrôleur peut rendre l’index en LiveTable si :
- `neox_crud.live_table.enabled` est à `true` (global), ou si le handler active la LiveTable via son YAML.
- Les dépendances LiveTable sont présentes (Pagerfanta + adapter Doctrine ORM + Symfony UX LiveComponent).

Sinon, il rend l’index “classique”.

---

5.1) Intégrer la LiveTable dans n’importe quelle page Twig

La LiveTable est un **Symfony UX LiveComponent**. Vous pouvez l’utiliser hors du contrôleur générique, dans n’importe quel template Twig, tant que Symfony UX LiveComponent est installé et que vos assets (Stimulus) sont chargés.

Exemple :
```twig
<twig:neox_crud_index_table resource="product" />
```

Notes
- `resource` doit correspondre au nom de ressource renvoyé par votre handler (`CrudHandlerInterface::getName()`).
- Si aucun handler ne correspond, `CrudHandlerFactory` lèvera une exception.

---

6) Utiliser le CRUD sans LiveTable (et même sans les templates du bundle)

Le point clé : le handler encapsule la logique CRUD.
Vous pouvez réutiliser les handlers dans vos propres contrôleurs.

Exemple (liste) :
```php
public function dashboard(CrudHandlerFactory $factory): Response
{
    $handler = $factory->get('product');

    return $this->render('admin/dashboard.html.twig', [
        'items' => $handler->findAll(),
    ]);
}
```

Exemple (édition) :
```php
$handler = $factory->get('product');
$entity = $handler->find($id);

$form = $handler->createForm($entity);
if ($handler->handleForm($request, $form)) {
    $handler->save($entity);

    return $this->redirectToRoute('admin_dashboard');
}
```

---

7) Sécurité

- Le contrôleur peut être protégé via un attribut `#[IsGranted(...)]`.
- Les actions non-GET (delete/custom POST) doivent être protégées par CSRF dans vos templates.

---

8) Configuration liée au contrôleur

- Activation LiveTable (globale) : `neox_crud.live_table.enabled`
- Options pagination LiveTable :
  - `neox_crud.live_table.default_per_page`
  - `neox_crud.live_table.max_per_page`
  - `neox_crud.live_table.pagination_position`

Voir aussi
- docs/fr/config.md
- docs/fr/cli.md

---

9) Personnalisation Twig (override des templates)

Le bundle rend la LiveTable via des templates Twig du bundle :
- `@NeoxCrud/neox_crud/index_live.html.twig`
- `@NeoxCrud/neox_crud/index_classic.html.twig` (fallback classique)
- `@NeoxCrud/neox_crud/form.html.twig` (formulaire new/edit)
- `@NeoxCrud/components/neox_crud_index_table.html.twig` (template du LiveComponent)

Pour les personnaliser **sans modifier le bundle**, vous pouvez surcharger le namespace Twig `NeoxCrud` dans votre application.

Étape 1 — déclarer un chemin local pour le namespace `NeoxCrud`
```yaml
# config/packages/twig.yaml
twig:
  paths:
    '%kernel.project_dir%/templates/neox_crud': 'NeoxCrud'
```

Étape 2 — copier le(s) template(s) à surcharger
- Surcharger la page LiveTable (wrapper) :
  - depuis le bundle : `templates/neox_crud/index_live.html.twig`
  - vers l’app : `templates/neox_crud/neox_crud/index_live.html.twig`

- Surcharger l’index classique (fallback) :
  - depuis le bundle : `templates/neox_crud/index_classic.html.twig`
  - vers l’app : `templates/neox_crud/neox_crud/index_classic.html.twig`

- Surcharger le formulaire CRUD (new/edit) :
  - depuis le bundle : `templates/neox_crud/form.html.twig`
  - vers l’app : `templates/neox_crud/neox_crud/form.html.twig`

- Surcharger le template du composant LiveTable :
  - depuis le bundle : `templates/components/neox_crud_index_table.html.twig`
  - vers l’app : `templates/neox_crud/components/neox_crud_index_table.html.twig`

Notes
- Twig résout `@NeoxCrud/...` d’abord vers vos templates applicatifs, puis retombe sur ceux du bundle.
- Le même mécanisme fonctionne pour n’importe quel autre template exposé via le namespace `@NeoxCrud`.
