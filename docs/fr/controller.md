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

5) LiveTable vs rendu classique

Le contrôleur peut rendre l’index en LiveTable si :
- `neox_crud.live_table.enabled` est à `true` (global), ou si le handler active la LiveTable via son YAML.
- Les dépendances LiveTable sont présentes (Pagerfanta + adapter Doctrine ORM + Symfony UX LiveComponent).

Sinon, il rend l’index “classique”.

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
