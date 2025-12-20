# 📘 Starter Kit — Guide rapide et visuel (FR)

Exemple complet de CRUD pour l’entité Product avec **NeoxCrudBundle**. Clair, simple et prêt à copier-coller.

Ce que vous obtenez immédiatement:
- ✅ Contrôleur CRUD générique prêt à l’emploi
- ✅ Formulaire Symfony classique
- ✅ IDs UUID/ULID/int pris en charge
- ✅ Transactions Doctrine et CSRF automatiques
- ✅ Pagination intégrée via findList()
- ✅ Actions personnalisées (ex: publish)
- ✅ Événements CRUD (Mercure)

---

1) Structure des fichiers

ProductCrudExample
├─ src
│  ├─ Controller
│  │  └─ ProductCrudController.php
│  ├─ Crud
│  │  └─ ProductCrudHandler.php
│  ├─ Entity
│  │  └─ Product.php
│  └─ Form
│     └─ ProductType.php
├─ templates
│  └─ admin
│     └─ product
│        ├─ index.html.twig
│        └─ form.html.twig
└─ config
   └─ routes
      └─ app_product.yaml

Chaque fichier est une brique du pipeline CRUD (contrôleur, handler, entité, formulaire, vues, routes).

---

2) Installation dans votre projet

Copiez les fichiers dans les mêmes emplacements, puis enregistrez le handler:

```yaml
# config/services.yaml
services:
  App\Crud\ProductCrudHandler:
    tags: ['neox_crud.handler']
```

Le bundle détecte automatiquement ce handler.

---

3) Routing

Déclarez le contrôleur CRUD:

```yaml
# config/routes/app_product.yaml
app_product_crud:
  resource: App\Controller\ProductCrudController
  type: attribute
```

Important:
- ⚠️ Ne restreignez pas {id} (pas de {id: \d+})
- Le bundle gère UUID, ULID, string et int

---

4) Sécurité

Le contrôleur générique impose déjà le rôle admin:

```php
#[IsGranted('ROLE_ADMIN')]
```

Protégez le préfixe /admin dans security.yaml:

```yaml
access_control:
  - { path: '^/admin', roles: ROLE_ADMIN }
```

---

5) Entité Product (UUID)

Exemple de clé primaire UUID sous Doctrine:

```php
#[ORM\Id]
#[ORM\Column(type: 'uuid')]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
private ?Uuid $id = null;
```

Rien d’autre à configurer côté bundle.

---

6) FormType Product

Formulaire simple:

```php
$builder
  ->add('title')
  ->add('price')
  ->add('published');
```

---

7) CrudHandler (le cœur)

Le handler:
- déclare la ressource, l’entité, le FormType et le préfixe de templates
- encapsule la logique métier (ex: publish)
- déclenche save()/delete() sous transaction Doctrine
- émet les événements CRUD (compatibles Mercure)

Support d’une action custom publish:

```php
public function supportsAction(string $action, string $method): bool
{
  return $action === 'publish' && $method === 'POST';
}
```

---

8) Action personnalisée: publish

Exemple dans Twig:

```twig
<form method="post" action="{{ path('app_product_custom', { resource: 'product', id: product.id, action: 'publish' }) }}">
  <input type="hidden" name="_token" value="{{ csrf_token('custom_product_' ~ product.id ~ '_publish') }}">
  <button class="btn btn-success btn-sm">Publier</button>
  </form>
```

Le contrôleur générique:
- valide automatiquement le token CSRF avec la convention custom_{resource}_{id}_{action}
- délègue l’exécution au handler

---

9) Templates

- index.html.twig: liste, liens éditer/supprimer, bouton publish, pagination
- form.html.twig: rendu du formulaire + bouton Enregistrer

---

10) Ce que le bundle prend en charge pour vous

Vous n’avez plus à écrire:
- ❌ contrôleur CRUD et routing
- ❌ pagination et suppression (CSRF)
- ❌ transactions Doctrine
- ❌ gestion des IDs (UUID/ULID/int/string)
- ❌ diffusion d’événements/Mercure

Le bundle fournit:
- ✔ contrôleur CRUD générique
- ✔ pagination via findList()
- ✔ CSRF automatique sur delete et actions POST
- ✔ transactions automatiques
- ✔ actions custom extensibles
- ✔ compatibilité Mercure

Tip: démarrez par le starter kit, puis adaptez le handler à votre métier.