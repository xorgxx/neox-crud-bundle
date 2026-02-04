# NeoxCrudBundle — Documentation principale (FR/EN)

[FR docs](docs/fr/guide.md) | [EN docs](docs/en/guide.md)


NeoxCrudBundle fournit une couche CRUD générique et factorisée pour Symfony. Son objectif est simple: arrêter de dupliquer le même code CRUD dans vos contrôleurs et formulaires, tout en conservant une grande extensibilité via des hooks, des événements et des actions personnalisées.

Features clés:
- Contrôleur CRUD générique pour toutes les routes `/admin/{resource}`
- CrudHandlers par ressource (Doctrine, formulaires, hooks, actions custom)
- Factory pour résoudre automatiquement le bon handler
- Hooks pre/post create/update/delete
- Événements pour Mercure, audit, logs, etc.
- Maker(s) pour générer handler + form + templates + traductions
- Support des identifiants int|string (UUID/ULID)

Ressources Docs:
- FR: docs/fr/guide.md, docs/fr/config.md, docs/fr/cli.md, docs/fr/uuid.md
- EN: docs/en/guide.md, docs/en/config.md, docs/en/cli.md, docs/en/uuid.md
- Controller: docs/fr/controller.md (FR) / docs/en/controller.md (EN)
- Forms & relations: docs/fr/forms-relations.md (FR) / docs/en/forms-relations.md (EN)

Sommaire rapide:
- Installation (FR/EN)
- Configuration (FR/EN)
- CLI / Maker (FR/EN)
- Cas d’usage
- Sécurité & événements
- Templates & traductions
- Starter kit

---

Section FR — Installation

1) Installer le bundle

```bash
composer require neox/crud-bundle
```

Puis enregistrer le bundle dans `config/bundles.php` si nécessaire:

```php
return [
    // ...
    Neox\NeoxCrudBundle\NeoxCrudBundle::class => ['all' => true],
];
```

2) Routing générique (config/routes/neox_crud.yaml)

```yaml
neox_crud:
  resource: '@NeoxCrudBundle/Controller/'
  type: attribute
  prefix: /
```

Routes exposées automatiquement:
- `/admin/{resource}`
- `/admin/{resource}/new`
- `/admin/{resource}/{id}/edit`
- `/admin/{resource}/{id}/delete`
- `/admin/{resource}/{id}/{action}` (actions custom)

3) Templates du bundle (optionnel)

```yaml
twig:
  paths:
    '%kernel.project_dir%/vendor/neox/crud-bundle/templates': NeoxCrud
```

4) Configuration (aperçu)

Voir la référence complète: docs/fr/config.md

```yaml
neox_crud:
  translations:
    field_keys: ['label', 'placeholder', 'help']
    patterns:
      placeholder: 'Saisir votre %field_label%'
      help: 'Aide pour %field_label%'
  mercure:
    enabled: true
    topic_prefix: '/crud'
```

5) CLI / Maker (aperçu)

Voir la référence complète: docs/fr/cli.md

```bash
php bin/console make:crud-handler product App\Entity\Product App\Form\ProductType
php bin/console make:neox:crud Product
```

Cas d’usage rapides (FR)
- Lister: utilisez `index` du contrôleur générique, `findList()` pour paginer
- Créer/Éditer: `new`/`edit` et `AbstractDoctrineCrudHandler::createForm()` + `handleForm()`
- Supprimer: `delete` protège par CSRF, événements déclenchés
- Action custom: implémentez `supportsAction()` + `handleAction()` dans votre handler

Sécurité & événements (FR)
- Par défaut, les routes CRUD sont protégées (ex: #[IsGranted('ROLE_ADMIN')])
- Événements émis: CrudEntitySavedEvent, CrudEntityDeletedEvent
- Intégrations: `CrudNotifierInterface` (implémentation par défaut via Mercure)

Templates & traductions (FR)
- Les FormTypes utilisent un `translation_domain` par ressource
- Clés standardisées: `product.field.name.label|placeholder|help`
- Des templates par défaut sont fournis et surchargeables
- Le Maker devine automatiquement le type de champ Symfony à partir du type Doctrine (FQCN en chaîne quand reconnu, sinon `null` pour laisser Symfony deviner). UUID/GUID → TextType par défaut.

Starter kit (FR)
- Exemple complet `Product` disponible dans `starter_kit/`
- Voir starter_kit/symfony_project_README.md

Plus d’infos (FR)
- [Guide complet: ](docs/fr/guide.md)
- [Config détaillée: ](docs/fr/config.md)
- [Exemples de config: ](docs/fr/config-exemples.md)
- [CLI détaillée: ](docs/fr/cli.md)
- [UUID/ULID: ](docs/fr/uuid.md)
- [Contrôleur CRUD générique: ](docs/fr/controller.md)

---

EN Section — Installation

1) Install the bundle

```bash
composer require neox/crud-bundle
```

Register in `config/bundles.php` if needed:

```php
return [
    // ...
    Neox\NeoxCrudBundle\NeoxCrudBundle::class => ['all' => true],
];
```

2) Generic routing (config/routes/neox_crud.yaml)

```yaml
neox_crud:
  resource: '@NeoxCrudBundle/Controller/'
  type: attribute
  prefix: /
```

Auto-exposed routes:
- `/admin/{resource}`
- `/admin/{resource}/new`
- `/admin/{resource}/{id}/edit`
- `/admin/{resource}/{id}/delete`
- `/admin/{resource}/{id}/{action}` (custom actions)

3) Bundle templates (optional)

```yaml
twig:
  paths:
    '%kernel.project_dir%/vendor/neox/crud-bundle/templates': NeoxCrud
```

4) Configuration (overview)

See full reference: docs/en/config.md

```yaml
neox_crud:
  translations:
    field_keys: ['label', 'placeholder', 'help']
    patterns:
      placeholder: 'Enter your %field_label%'
      help: 'Help for %field_label%'
  mercure:
    enabled: true
    topic_prefix: '/crud'
```

5) CLI / Maker (overview)

See full reference: docs/en/cli.md

```bash
php bin/console make:crud-handler product App\Entity\Product App\Form\ProductType
php bin/console make:neox:crud Product
```

Quick use cases (EN)
- List: generic controller `index`, pagination via `findList()`
- Create/Edit: `new`/`edit` and `AbstractDoctrineCrudHandler::createForm()` + `handleForm()`
- Delete: `delete` with CSRF, emits events
- Custom action: implement `supportsAction()` + `handleAction()` in your handler

Security & events (EN)
- By default, CRUD routes are protected (e.g. #[IsGranted('ROLE_ADMIN')])
- Emitted events: CrudEntitySavedEvent, CrudEntityDeletedEvent
- Integrations: `CrudNotifierInterface` (default implementation uses Mercure)

Templates & translations (EN)
- FormTypes use a per-resource `translation_domain`
- Standard keys: `product.field.name.label|placeholder|help`
- Default templates are provided and can be overridden
- The Maker auto-guesses Symfony Form Types from Doctrine types (writes FQCN as string when known, otherwise `null` so Symfony can guess). UUID/GUID → TextType by default.

Starter kit (EN)
- Complete `Product` example in `starter_kit/`
- See starter_kit/symfony_project_README.md

More (EN)
- [Full guide:](docs/en/guide.md)
- [Config reference:](docs/en/config.md)
- [Config examples:](docs/en/config-examples.md)
- [CLI reference: ](docs/en/cli.md)
- [UUID/ULID: ](docs/en/uuid.md)
- [Generic CRUD controller: ](docs/en/controller.md)

---

Notes importantes
- Support des identifiants int|string (UUID/ULID)
- Pas de BC break: respecter les signatures d’interface fournies
- Voir CHANGELOG.md pour les détails de version et migrations
