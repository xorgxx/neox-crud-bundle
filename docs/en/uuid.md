# NeoxCrudBundle – Usage Guide (EN)

## 1. Overview

**NeoxCrudBundle** provides a generic controller and a handler system to quickly build Doctrine-based CRUD in Symfony:

- a single **GenericCrudController**;
- one **CrudHandler** per resource;
- **custom actions** support (`/admin/{resource}/{id}/{action}`);
- a **starter kit** (Product entity);
- a **Maker** to generate handlers and views;
- CRUD events;
- optional **Mercure** integration.

From the patched version onward, the bundle natively supports **non-integer identifiers** (UUID, ULID, string, …).

---

## 2. Installation

### 2.1. Composer

```bash
composer require neox/neox-crud-bundle
```

Make sure the bundle’s own `composer.json` autoload section is correct:

```json
"autoload": {
  "psr-4": {
    "Neox\\NeoxCrudBundle\\": "src/"
  }
}
```

### 2.2. Bundle registration

If you use Symfony Flex, the bundle should be auto-registered.

Otherwise in `config/bundles.php`:

```php
return [
    // ...
    Neox\NeoxCrudBundle\NeoxCrudBundle::class => ['all' => true],
];
```

---

## 3. Routing & Generic Controller

The generic controller is located at:

```php
Neox\NeoxCrudBundle\Controller\GenericCrudController
```

Expose it via attributes routing:

```yaml
# config/routes/neox_crud.yaml
neox_crud_admin:
    resource: '@NeoxCrudBundle/Controller/GenericCrudController.php'
    type: attribute
    prefix: /admin
```

Main routes (simplified):

- `/admin/{resource}` – index
- `/admin/{resource}/new` – create
- `/admin/{resource}/{id}/edit` – edit
- `/admin/{resource}/{id}/delete` – delete
- `/admin/{resource}/{id}/{action}` – custom action

> `{resource}` must match `getName()` of your `CrudHandler`.

---

## 4. Non-integer ID support (UUID / ULID / string)

### 4.1. Goal

The bundle supports:

- **UUID** (Doctrine `uuid` type);
- **ULID** (Doctrine `ulid` type);
- **GUID** or any string ID;
- **custom string identifiers** (hash, slug, etc.);
- any Doctrine ID type compatible with `int|string`.

### 4.2. Internal signatures

All important signatures now accept **`int|string`**.

#### Generic Controller

```php
public function edit(string $resource, int|string $id, Request $request): Response;
public function delete(string $resource, int|string $id, Request $request): Response;
public function custom(string $resource, int|string $id, string $action, Request $request): Response;
```

#### Handler interface

```php
interface CrudHandlerInterface
{
    // ...

    /** Retrieve an object by id */
    public function find(int|string $id): ?object;

    /**
     * Execute a custom action and return a Response.
     */
    public function handleAction(
        string $action,
        int|string $id,
        Request $request,
        AbstractController $controller
    ): Response;
}
```

#### Base implementation

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

#### Maker-generated handlers

Handler templates (`CrudHandler.tpl.php`, `NeoxCrudHandler.tpl.php`) now generate:

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

### 4.3. Doctrine examples

#### UUID

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

#### ULID

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

#### Custom string

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

### 4.4. Routing constraints (⚠ important)

Do **not** restrict `{id}` to digits only.

❌ Don’t:

```yaml
requirements:
    id: '\d+'
```

✔ Instead:

```yaml
# no requirements
# or
requirements: ~
```

This way, Symfony will accept:

- `/admin/product/42/edit`;
- `/admin/product/550e8400-e29b-41d4-a716-446655440000/edit`;
- `/admin/order/01HZYFQ5AXXXXXXXJ1K9Q5XXYV/edit`.

---

## 5. Custom Handlers & Custom Actions

### 5.1. Example: ProductCrudHandler

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
            $controller->addFlash('success', 'Product published.');
        }

        return $controller->redirectToRoute('neox_crud_admin_crud_index', [
            'resource' => $this->getName(),
        ]);
    }
}
```

---

## 6. CRUD Events & Mercure

When `save()` or `delete()` is called, the handler dispatches:

- `CrudEntitySavedEvent` (create / update);
- `CrudEntityDeletedEvent` (delete).

The **CrudMercureSubscriber** can publish these events to Mercure, on topics such as:

- `"{topicPrefix}/{resource}/{id}"` (entity topic);
- `"{topicPrefix}/{resource}"` (list topic).

Example payload for `entity.saved`:

```json
{
  "type": "entity.saved",
  "resource": "product",
  "entityClass": "App\\Entity\\Product",
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "operation": "create"
}
```

Mercure publishing is wrapped in a `try/catch` block so that any failure on the hub side does **not** break the CRUD HTTP flow.

---

## 7. Maker & CRUD generation

The bundle ships with a **Maker** command:

```bash
php bin/console make:neox:crud Product
# or
php bin/console make:neox:crud App\Entity\Product
```

The Maker:

- generates a dedicated **CrudHandler**;
- generates Twig templates (index & form);
- uses the `int|string $id` signature;
- is compatible with entities using UUID / ULID / string IDs.

---

## 8. Summary

- All CRUD logic is centralized into `CrudHandler` classes.
- The `GenericCrudController` delegates every CRUD operation to handlers.
- Identifiers are fully flexible (`int|string`) – UUID / ULID / string are supported.
- CRUD events can be broadcast over Mercure.
- The Maker accelerates boilerplate generation.

Please also check the root `README.md` for a quick overview and links to FR / EN documentation.

---

See also
- [Full guide and use cases](./guide.md)
- [Configuration reference](./config.md)
- [Configuration examples](./config-examples.md)
- [CLI / Maker reference](./cli.md)
