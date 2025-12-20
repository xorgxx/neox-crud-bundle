# NeoxCrudBundle – Guide d’utilisation (FR)

## 1. Présentation

**NeoxCrudBundle** fournit un contrôleur générique et un système de *handlers* pour construire rapidement des CRUD Symfony basés sur Doctrine, avec :

- un **GenericCrudController** unique ;
- des **CrudHandler** par ressource ;
- un support des **actions personnalisées** (`/admin/{resource}/{id}/{action}`) ;
- un **starter kit** (entité `Product`) pour démarrer ;
- un **Maker** pour générer automatiquement les handlers et vues ;
- un **dispatch d’événements** CRUD ;
- une intégration optionnelle avec **Mercure**.

À partir de la version patchée, le bundle supporte nativement les **identifiants non-entiers** (UUID, ULID, string…).

---

## 2. Installation

### 2.1. Via Composer

Dans ton projet Symfony :

```bash
composer require neox/neox-crud-bundle
```

Vérifie que l’autoload de `composer.json` du bundle est bien configuré (dans le bundle lui-même) :

```json
"autoload": {
  "psr-4": {
    "Neox\\NeoxCrudBundle\\": "src/"
  }
}
```

### 2.2. Activation du bundle

Si tu utilises Symfony Flex, le bundle est normalement auto-enregistré.

Sinon, dans `config/bundles.php` :

```php
return [
    // ...
    Neox\NeoxCrudBundle\NeoxCrudBundle::class => ['all' => true],
];
```

---

## 3. Routing & Contrôleur générique

Le contrôleur générique se trouve dans :

```php
Neox\NeoxCrudBundle\Controller\GenericCrudController
```

Expose-le via les attributs de routing :

```yaml
# config/routes/neox_crud.yaml
neox_crud_admin:
    resource: '@NeoxCrudBundle/Controller/GenericCrudController.php'
    type: attribute
    prefix: /admin
```

Les routes disponibles (simplifiées) :

- `/admin/{resource}` – index
- `/admin/{resource}/new` – création
- `/admin/{resource}/{id}/edit` – édition
- `/admin/{resource}/{id}/delete` – suppression
- `/admin/{resource}/{id}/{action}` – action custom

> 💡 Le paramètre `{resource}` doit correspondre à `getName()` de ton `CrudHandler`.

---

## 4. Support des identifiants non-entiers (UUID / ULID / string)

### 4.1. Objectif

Le bundle supporte désormais automatiquement les identifiants :

- **UUID** (type Doctrine `uuid`) ;
- **ULID** (type Doctrine `ulid`) ;
- **GUID** ou autre type string ;
- **string custom** (hash, slug, etc.) ;
- tout type géré par Doctrine pour `id` tant qu’il est compatible avec `int|string`.

### 4.2. Signatures internes

Toutes les signatures importantes acceptent **`int|string`**.

#### Contrôleur générique

```php
public function edit(string $resource, int|string $id, Request $request): Response;
public function delete(string $resource, int|string $id, Request $request): Response;
public function custom(string $resource, int|string $id, string $action, Request $request): Response;
```

#### Interface du handler

```php
interface CrudHandlerInterface
{
    // ...

    /** Récupère un objet par id */
    public function find(int|string $id): ?object;

    /**
     * Exécute une action custom et renvoie une Response.
     */
    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response;
}
```

#### Implémentation de base

```php
class AbstractDoctrineCrudHandler implements CrudHandlerInterface
{
    public function find(int|string $id): ?object
    {
        return $this->em->getRepository($this->getEntityClass())->find($id);
    }

    // ...
}
```

#### Handlers générés par le Maker

Les templates de handler (`CrudHandler.tpl.php`, `NeoxCrudHandler.tpl.php`) génèrent :

```php
public function handleAction(
    string $action,
    int|string $id,
    Request $request,
    AbstractController $controller
): Response
{
    // ...
}
```

---

### 4.3. Exemple Doctrine avec UUID

```php
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $id = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }
}
```

---

### 4.4. Exemple Doctrine avec ULID

```php
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private ?Ulid $id = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }
}
```

---

### 4.5. Exemple Doctrine avec string custom

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ApiClient
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $id;

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(16));
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

---

### 4.6. Attention au routing (⚠ important)

Ne **restreins plus** l’ID avec une regex numérique dans tes routes.

❌ À ne pas faire :

```yaml
requirements:
    id: '\d+'
```

✔ À faire :

```yaml
# pas de requirements
# ou
requirements: ~
```

Ainsi, Symfony acceptera :

- `/admin/product/42/edit` ;
- `/admin/product/550e8400-e29b-41d4-a716-446655440000/edit` ;
- `/admin/order/01HZYFQ5AXXXXXXXJ1K9Q5XXYV/edit`.

---

## 5. Custom handlers & actions personnalisées

### 5.1. Exemple de CrudHandler (Product)

```php
namespace App\Crud\Handler;

use App\Entity\Product;
use App\Form\ProductType;
use Neox\NeoxCrudBundle\Crud\AbstractDoctrineCrudHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ProductCrudHandler extends AbstractDoctrineCrudHandler
{
    public function getName(): string
    {
        return 'product';
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    public function getFormType(): string
    {
        return ProductType::class;
    }

    public function getTemplatePrefix(): string
    {
        return 'admin/product';
    }

    public function supportsAction(string $action, string $method): bool
    {
        return $action === 'publish' && $method === 'GET';
    }

    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response {
        $entity = $this->find($id);
        if (!$entity instanceof Product) {
            throw $controller->createNotFoundException();
        }

        if ($action === 'publish') {
            $entity->setPublished(true);
            $this->save($entity);
            $controller->addFlash('success', 'Produit publié.');
        }

        return $controller->redirectToRoute('neox_crud_admin_crud_index', [
            'resource' => $this->getName(),
        ]);
    }
}
```

---

## 6. Événements CRUD & Mercure

Lorsque `save()` ou `delete()` sont utilisés, le handler émet des événements :

- `CrudEntitySavedEvent` (create / update) ;
- `CrudEntityDeletedEvent` (delete).

Le **CrudMercureSubscriber** peut publier ces événements vers Mercure, sur des topics de la forme :

- `"{topicPrefix}/{resource}/{id}"` (topic entité) ;
- `"{topicPrefix}/{resource}"` (topic liste).

Exemple de payload pour un `entity.saved` :

```json
{
  "type": "entity.saved",
  "resource": "product",
  "entityClass": "App\\Entity\\Product",
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "operation": "create"
}
```

Les appels au hub Mercure sont entourés d’un `try/catch` pour ne **jamais casser** la requête HTTP CRUD en cas d’erreur réseau ou hub.

---

## 7. Maker & génération de CRUD

Le bundle fournit un **Maker** pour générer rapidement un handler et les vues associées :

```bash
php bin/console make:neox:crud Product
# ou
php bin/console make:neox:crud App\Entity\Product
```

Le Maker :

- génère un **CrudHandler** dédié ;
- génère des templates Twig (index & form) ;
- respecte la signature `int|string $id` ;
- est compatible avec les entités utilisant UUID / ULID / string.

---

## 8. Résumé

- Le bundle centralise toute la logique CRUD dans des `CrudHandler`.
- Le `GenericCrudController` délègue toutes les opérations aux handlers.
- Les identifiants sont entièrement flexibles (`int|string`) : support UUID / ULID / string.
- Les événements CRUD peuvent être diffusés via Mercure.
- Le Maker accélère la génération de boilerplate.

Consulte également le `README.md` à la racine du bundle pour un aperçu rapide et les liens vers la documentation FR / EN.
