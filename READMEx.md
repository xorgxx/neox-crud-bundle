
# NeoxCrudBundle — Documentation Complète

[...]

Voir aussi
- docs/fr/controller.md — Contrôleur CRUD générique (routes, flux, LiveTable vs classique)
- docs/fr/config.md — Référence de configuration (dont LiveTable)

## 6. Utilisation avancée dans vos propres controllers

### Récupérer un handler via la Factory

```php
public function index(CrudHandlerFactory $factory): Response
{
    $handler = $factory->get('product');
    $items = $handler->findAll();

    return $this->render('admin/dashboard.html.twig', [
        'items' => $items,
    ]);
}
```

### Éditer un élément en utilisant le handler

```php
$handler = $factory->get('product');
$entity = $handler->find($id);

$form = $handler->createForm($entity);
if ($handler->handleForm($request, $form)) {
    $handler->save($entity);
    return $this->redirectToRoute('admin_dashboard');
}
```

Note
- Cette approche est indépendante du `GenericCrudController` : tu réutilises la même logique CRUD (via le handler) dans tes propres endpoints.
- Le `GenericCrudController` est surtout utile pour exposer rapidement toutes les routes CRUD standards sans écrire de contrôleur.

## 7. Hooks CRUD

Les hooks permettent d'injecter de la logique métier simple avant/après les opérations.

```php
public function preCreate(object $entity, Request $request): void {}
public function preUpdate(object $entity, Request $request): void {}
public function preDelete(object $entity, Request $request): void {}

protected function beforeSave(object $entity): void {}
protected function afterSave(object $entity): void {}
protected function beforeDelete(object $entity): void {}
protected function afterDelete(object $entity): void {}
```

## 8. Actions personnalisées

```php
public function supportsAction(string $action, string $method): bool
{
    return $action === 'publish';
}

public function handleAction(string $action, int|string $id, Request $request, AbstractController $controller): Response
{
    $entity = $this->find($id);
    $entity->setPublished(true);
    $this->save($entity);

    return $controller->redirectToRoute('neox_crud_admin_crud_index', [
        'resource' => $this->getName()
    ]);
}
```

## 9. Voters & Sécurité

Vous pouvez intégrer vos propres règles d’accès.

```php
public function preUpdate(object $entity, Request $request): void
{
    if (!$this->security->isGranted('PRODUCT_EDIT', $entity)) {
        throw new AccessDeniedException();
    }
}
```

## 10. Événements & Mercure

Le bundle déclenche automatiquement :

- `CrudEntitySavedEvent`
- `CrudEntityDeletedEvent`

Vous pouvez écouter ces événements :

```php
public static function getSubscribedEvents(): array
{
    return [
        CrudEntitySavedEvent::class => 'onSaved',
    ];
}
```

## 11. Thème Twig & Traductions FormTypes

Chaque FormType utilise automatiquement :

```php
'translation_domain' => '{{ resource }}'
```

et toutes les clés sont du type :

```
product.field.name.label
product.field.name.placeholder
product.field.name.help
```

## 12. Actions globales du CRUD

- `/admin/{resource}`  
- `/admin/{resource}/new`  
- `/admin/{resource}/{id}/edit`  
- `/admin/{resource}/{id}/delete`  
- `/admin/{resource}/{id}/{action}` → actions spéciales

Routage (rappel)

Pour activer ces routes, importe les routes du bundle :
```yaml
# config/routes/neox_crud.yaml
neox_crud:
  resource: '@NeoxCrudBundle/Controller/'
  type: attribute
  prefix: /
```
