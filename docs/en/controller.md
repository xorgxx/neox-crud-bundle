Generic CRUD Controller (EN)

This page documents the bundle controller `Neox\NeoxCrudBundle\Controller\GenericCrudController`.

Goal
- Provide a single entry point for all CRUD routes `/admin/{resource}`.
- Resolve the right handler through `CrudHandlerFactory`.
- Expose standard CRUD actions (index/new/edit/delete) and custom actions.
- Optionally render the index as a LiveTable (Pagerfanta + Symfony UX LiveComponent) when enabled.

---

1) Exposed routes

The controller relies on the `resource` route parameter.

Default routes:
- `/admin/{resource}` (index)
- `/admin/{resource}/new`
- `/admin/{resource}/{id}/edit`
- `/admin/{resource}/{id}/delete`
- `/admin/{resource}/{id}/{action}` (custom actions)

Notes
- `{resource}` must match your handler name (`CrudHandlerInterface::getName()`).
- `{id}` supports `int|string` identifiers (UUID/ULID).

---

2) Symfony routing

You must import the bundle routes.

Recommended example:
```yaml
# config/routes/neox_crud.yaml
neox_crud:
  resource: '@NeoxCrudBundle/Controller/'
  type: attribute
  prefix: /
```

---

3) Handler resolution

The controller is entity-agnostic.
It resolves the handler from `resource` using `CrudHandlerFactory`.

Your handler must:
- implement `CrudHandlerInterface`
- be registered as a service (autowire/autoconfigure recommended)
- provide:
  - `getName()` (resource name)
  - `getEntityClass()` (FQCN)
  - `getFormType()` (FQCN)

---

4) Execution flow

4.1) Index
- Calls `findList(Request $request)` on the handler.
- Renders either:
  - a “classic” index template
  - or the LiveTable when enabled and dependencies are installed.

4.2) New
- Creates the entity via `createEntity()`.
- Builds the form via `createForm($entity)`.
- Handles submission via `handleForm($request, $form)`.
- Persists via `save($entity)`.

4.3) Edit
- Loads the entity via `find($id)`.
- Builds/handles the form (same as `new`).
- Persists via `save($entity)`.

4.4) Delete
- Loads the entity via `find($id)`.
- Validates CSRF if your templates send a token.
- Deletes via `delete($entity)`.

4.5) Custom action
- The controller delegates to the handler via `supportsAction($action, $method)` then `handleAction(...)`.

---

5) LiveTable vs classic rendering

The controller can render the index as a LiveTable if:
- `neox_crud.live_table.enabled` is `true` (global), or the handler enables LiveTable via its YAML.
- LiveTable dependencies are installed (Pagerfanta + Doctrine ORM adapter + Symfony UX LiveComponent).

Otherwise, it renders the classic index.

---

5.1) Embed the LiveTable in any Twig page

The LiveTable is a **Symfony UX LiveComponent**. You can use it outside of the generic controller, in any Twig template, as long as Symfony UX LiveComponent is installed and your assets (Stimulus) are loaded.

Example:
```twig
<twig:neox_crud_index_table resource="product" />
```

Notes
- `resource` must match your handler name (`CrudHandlerInterface::getName()`).
- If no handler matches, `CrudHandlerFactory` will throw an exception.

---

6) Using the CRUD without LiveTable (and even without bundle templates)

Key idea: the handler encapsulates the CRUD logic.
You can reuse handlers in your own controllers.

Example (list):
```php
public function dashboard(CrudHandlerFactory $factory): Response
{
    $handler = $factory->get('product');

    return $this->render('admin/dashboard.html.twig', [
        'items' => $handler->findAll(),
    ]);
}
```

Example (edit):
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

7) Security

- You can protect the controller with an attribute like `#[IsGranted(...)]`.
- Non-GET actions (delete/custom POST) should be protected with CSRF tokens in your templates.

---

8) Controller-related configuration

- LiveTable global enable: `neox_crud.live_table.enabled`
- LiveTable pagination options:
  - `neox_crud.live_table.default_per_page`
  - `neox_crud.live_table.max_per_page`
  - `neox_crud.live_table.pagination_position`

See also
- docs/en/config.md
- docs/en/cli.md

---

9) Twig customization (override templates)

NeoxCrudBundle renders LiveTable using bundle templates:
- `@NeoxCrud/neox_crud/index_live.html.twig`
- `@NeoxCrud/neox_crud/index_classic.html.twig` (classic fallback)
- `@NeoxCrud/neox_crud/form.html.twig` (new/edit form)
- `@NeoxCrud/components/neox_crud_index_table.html.twig` (LiveComponent template)

To customize them **without modifying the bundle**, override the `NeoxCrud` Twig namespace in your application.

Step 1 — register a local path for the `NeoxCrud` namespace
```yaml
# config/packages/twig.yaml
twig:
  paths:
    '%kernel.project_dir%/templates/neox_crud': 'NeoxCrud'
```

Step 2 — copy the template(s) you want to override
- Override the LiveTable page wrapper:
  - from the bundle: `templates/neox_crud/index_live.html.twig`
  - to your app: `templates/neox_crud/neox_crud/index_live.html.twig`

- Override the classic index fallback:
  - from the bundle: `templates/neox_crud/index_classic.html.twig`
  - to your app: `templates/neox_crud/neox_crud/index_classic.html.twig`

- Override the CRUD form (new/edit):
  - from the bundle: `templates/neox_crud/form.html.twig`
  - to your app: `templates/neox_crud/neox_crud/form.html.twig`

- Override the LiveTable component template:
  - from the bundle: `templates/components/neox_crud_index_table.html.twig`
  - to your app: `templates/neox_crud/components/neox_crud_index_table.html.twig`

Notes
- Twig will resolve `@NeoxCrud/...` to your application templates first, then fallback to the bundle.
- This mechanism also works for any other bundle template under the `@NeoxCrud` namespace.
