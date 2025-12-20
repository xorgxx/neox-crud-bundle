# Changelog — NeoxCrudBundle

Toutes les modifications notables du bundle sont listées dans ce fichier.  
Ce projet suit les principes de versionnement sémantique (SemVer).

---

## [Unreleased]
### Nothing yet

## [1.2.1] — 2025-12-11
### Added
- Bundle-provided routes file `Resources/config/routes.yaml` to expose the Generic CRUD controller routes. Projects can now import `@NeoxCrudBundle/Resources/config/routes.yaml` instead of pointing directly to the Controller directory. (BC: optional, no behavior change unless imported)
- feat(crud): Maker base layout is now configurable via `neox_crud.makers.base_layout` and overridable via CLI `--twig-base-layout`. Precedence: CLI > config > `--twig-namespace` (derived as `@<ns>/admin/_layout.html.twig`) > default `'/admin/_layout.html.twig'`. Twig templates updated to safely fallback to `'/admin/_layout.html.twig'`. (No BC break)
- feat(crud): NeoxCrudMaker gains an optional `--with-controller` flag (disabled by default) to generate a dedicated controller extending `GenericCrudController`. Backward compatible: when not passed, no controller is generated and the generic controller continues to work as before.
- feat(crud): Optional `--with-bulk-ui` flag (disabled by default) to generate an index template variant with a selection column and CSRF‑protected rendering of configured `bulk_actions`. Fully backward compatible (no UI change unless flag is used). FR/EN docs and unit tests updated.
- feat(crud): Opt‑in YAML flag `append_default_actions` (per handler) to append the default CRUD buttons (Edit/Delete) after developer‑defined row actions without overwriting them. Disabled by default to preserve BC. Works with flat keys or under `neox_crud:`.
- feat(crud): Per-field attributes support for `index_fields` in per-handler YAML. In addition to a simple list of field names, handlers may now declare:
  - list of maps (e.g. `{ name: 'createdAt', format: 'Y-m-d' }`, `{ name: 'enabled', boolean_icon: true }`, `{ name: 'image', type: 'image', class: 'thumb-48' }`, `{ name: 'roles', voters: ['ROLE_ADMIN'] }`), or
  - an associative map (`createdAt: { format: 'Y-m-d' }`, `enabled: { boolean_icon: true }`, ...).
  This is fully opt-in and backward compatible: `getIndexFields()` still returns names only, and a new method `getIndexFieldOptions()` exposes the parsed options for templates. The generic controller now passes `field_options` to Twig. FR/EN docs and unit tests added.
- feat(crud): Per-handler YAML override for index table fields. Handlers extending `AbstractDoctrineCrudHandler` can define `index_fields` in a YAML file located next to the handler class (first match among: `config.yaml`, `<ClassName>.yaml`, or `config/crud.yaml`). This allows customizing the columns shown in the index view without overriding PHP. Fully opt-in and backward compatible. Documentation FR/EN updated and unit tests added.
- feat(maker): CLI now generates a commented per-handler `config.yaml` next to each new handler directory (both `make:crud-handler` and `make:neox:crud-maker`). This file documents all supported options (currently `index_fields`) and can be edited to customize columns without PHP changes. Idempotent: not overwritten if already present. FR/EN docs updated and unit test added.
- feat(maker): The generated per-handler `config.yaml` now proposes all detected entity fields (Doctrine) for the target entity. It includes a commented list of fields and a ready-to-uncomment `index_fields` line pre-filled with those fields. Idempotent and fully backward compatible. FR/EN docs updated and a unit test added.
- feat(crud): Opt‑in, YAML‑driven UI actions/buttons for the index view. Handlers can now declare `actions` (per‑row), `bulk_actions` (selection), and `toolbar_buttons` (next to "New") in their adjacent YAML (flat or under `neox_crud:`). The `AbstractDoctrineCrudHandler` parses and normalizes these keys; `GenericCrudController` passes `toolbarButtons`, `bulkActions`, and `rowActionsById` to Twig. Starter template updated to render toolbar and row actions (CSRF‑safe for delete/custom). Fully backward compatible. EN/FR docs and unit tests added.
### Fixed
- Maker `make:neox:crud`: replace Twig-style placeholders in `src/Maker/tpl/NeoxCrudFormType.tpl.php` with PHP short-echo placeholders to fix "syntax error, unexpected token "{"" during generation.
- Maker `make:neox:crud` and `make:neox:crud-handler`: convert PHP templates (`NeoxCrudFormType.tpl.php`, `CrudHandler.tpl.php`) to Maker emitter style by outputting the opening tag (`<?= "<?php\n" ?>`) and injecting variables with short-echo placeholders. This prevents PHP parse errors like "unexpected token '<'" when Maker executes the templates.
- Handlers: replace calls to `$controller->createNotFoundException(...)` by throwing `NotFoundHttpException` directly in `AbstractDoctrineCrudHandler::handleAction()` and in the handler template. This avoids visibility errors on Symfony 7 where the helper is not publicly callable from outside the controller.
- Handler template: `getFormType()` now returns the FQCN using `::class` (and imports the FormType with `use`) instead of a quoted string. This aligns with `getEntityClass()` style and improves IDE/refactor safety. (BC: unchanged at runtime, still a string)
- Twig index template: removed usage of `??` with complex expressions and the `defined` test behavior; translation fallbacks now compare the translated value to its key. Fixes error in generated `admin/<resource>/index.html.twig` (e.g. line 5) where "defined" only worked with simple variables.
### Tooling / Quality
- Add composer scripts: `validate`, `cs:check`, `cs:fix`, `stan`, `test`.
- Add `.php-cs-fixer.dist.php` with PSR-12, ordered imports, no unused imports, strict_types rule.
- Add `phpstan.neon.dist` (level=max) and wire `composer stan`.
- Update docs FR/EN (guides) with contributor quality commands.

### Documentation
- Refonte complète de la documentation FR/EN :
  - README racine bilingue (installation, features, cas d’usage, liens).
  - docs/fr/cli.md et docs/en/cli.md (référence CLI/Maker).
  - docs/fr/config.md et docs/en/config.md (référence de configuration).
  - docs/fr/config-exemples.md et docs/en/config-examples.md (exemples pratiques de configuration).
  - Guide EN étoffé (docs/en/guide.md) au niveau du guide FR.
  - NOTE: la génération du FormType indique désormais automatiquement un type de champ Symfony (FQCN) lorsque le type Doctrine est connu, sinon `null`. Le template NeoxCrudFormType.tpl.php a été ajusté en conséquence (utilisation du FQCN sous forme de chaîne).
  - CLI help: Added a concise routing snippet and example URL to access the generic CRUD page. FR/EN docs now include the same `config/routes/neox_crud.yaml` block and browser URL examples.
  - Ajout FR/EN de la référence pour les clés par handler `actions`, `bulk_actions`, `toolbar_buttons` (sécurité CSRF, placeholders `entity.*` / `context.*`, ordre par priorité, rétrocompatibilité).

### Modifié
- Maker make:neox:crud: le FormType généré pré-remplit les champs avec des types de formulaire Symfony devinés à partir des types Doctrine (TextType, TextareaType, IntegerType, NumberType, CheckboxType, DateType, DateTimeType, TimeType, etc.). Pour `uuid`/`guid`, fallback sur TextType. Repli sur `null` si le type n’est pas reconnu.

---

## [1.2.0] — 2025-11-24
### 🚀 Version complète : UUID/ULID + pagination + transactions + sécurité + tests

#### ✨ Ajouté
- Support **natif** des identifiants `int|string` dans tout le pipeline CRUD :
  - `GenericCrudController`
  - `CrudHandlerInterface`
  - `AbstractDoctrineCrudHandler`
  - handlers générés par le Maker
  - starter kit (Product)
- Nouvelles interfaces de responsabilités :
  - `CrudFinderInterface`
  - `CrudEditorInterface`
  - `CrudActionInterface`
  - `CrudHandlerInterface` les étend.
- Méthode `findList(Request $request): iterable` dans `CrudHandlerInterface` et implémentation par défaut dans `AbstractDoctrineCrudHandler` pour la **pagination**.
- Abstraction de notification CRUD :
  - `CrudNotifierInterface` (Mercure en implémentation par défaut).
- Fichier `phpunit.xml.dist` + premier test :
  - `tests/Crud/CrudHandlerFactoryTest.php`.

#### 🔧 Modifié
- `GenericCrudController::index()` utilise `findList()` et accepte `Request $request`.
- Ajout de `#[IsGranted('ROLE_ADMIN')]` sur `GenericCrudController` pour protéger toutes les routes CRUD par défaut.
- Actions `custom` en `POST` protégées par un token CSRF :
  - nom : `custom_{resource}_{id}_{action}`.
- `AbstractDoctrineCrudHandler::save()` et `delete()` :
  - exécutés dans une transaction Doctrine (via `wrapInTransaction()` si disponible, fallback transaction manuelle).
- `CrudMercureSubscriber` :
  - utilisation d’un `topicPrefix` configurable (par défaut `'crud'`) ;
  - encapsulation des `publish()` dans un `try/catch` pour éviter de casser la requête en cas de problème Mercure ;
  - implémente `CrudNotifierInterface` et expose `notifyEntitySaved()` / `notifyEntityDeleted()`.

#### 🐛 Corrigé
- Bug dans l’entité du starter kit :
  - `return $$this->published;` → `return $this->published;`.
- Harmonisation des signatures (UUID/ULID/string) entre :
  - interface
  - handler abstrait
  - contrôleur générique
  - Maker
  - starter kit.

---

## [1.1.0] — 2025-11-20
### Support UUID/ULID + documentation FR/EN

#### Ajouté
- Support des identifiants `int|string` dans les signatures CRUD.
- Documentation complète :
  - `docs/fr/guide.md`
  - `docs/en/guide.md`
  - `html/index.html`
- Bloc “Important — Support UUID / ULID / string” dans le `README.md`.

#### Modifié
- Ajout de `declare(strict_types=1);` dans l’ensemble des fichiers PHP.
- Sécurisation de la `CrudHandlerFactory` :
  - détection des doublons de handlers (`getName()`).
- Renforcement du `CrudMercureSubscriber` (gestion d’erreurs Mercure).

---

## [1.0.1] — 2025-11-10
### Patch mineur

#### Modifié
- Correction de namespaces PSR-4.
- Ajustements sur le Maker et le starter kit.

#### Corrigé
- Problèmes de chargement des services lors de l’installation.
- Incohérences entre chemins du starter kit et ceux utilisés par le Maker.

---

## [1.0.0] — 2025-11-01
### 🎉 Première version stable

#### Ajouté
- `GenericCrudController` :
  - index / new / edit / delete / custom action.
- `CrudHandlerInterface` :
  - standardisation des handlers CRUD.
- `AbstractDoctrineCrudHandler` :
  - implémentation CRUD Doctrine + hooks (`preCreate`, `preUpdate`, `preDelete`, etc.).
- Maker :
  - génération CRUD de base.
- Starter kit (entité `Product`, FormType, handler).
- Intégration Mercure :
  - publication d’événements CRUD.
- Événements :
  - `CrudEntitySavedEvent`
  - `CrudEntityDeletedEvent`.

---

## [0.9.0] — 2025-10-20
### 🧪 Version Bêta

- Prototype du contrôleur générique.
- Premières versions du Maker.
- Architecture initiale des handlers CRUD.
- Publication Mercure basique.

---

## [0.1.0] — 2025-10-10
### 🏗 Prototype initial

- Structure du bundle.
- Autoload PSR-4.
- Généricité du contrôleur CRUD.

---

## Notes de migration

### Vers ≥ 1.1.0
- Supprimer toute contrainte `{ id: \d+ }` dans les routes CRUD.
- Re-générer l’autoload : `composer dump-autoload`.

### Vers ≥ 1.2.0
- Vérifier que les templates `index` utilisent bien la variable `items` (provenant désormais de `findList()`).
- Pour les actions `custom` en `POST` :
  - ajouter `_token` dans les formulaires avec le bon ID : `custom_{resource}_{id}_{action}`.

# Changelog

## [Unreleased]
### Added
- `CrudHandlerInterface::getIndexFields()`: new method to customize which fields are displayed in index view. By default, returns all entity fields except 'id'. Developers can override this method in their handlers to filter or reorder displayed fields.


### Fixed
- Maker: Generated CRUD handler methods `getEntityClass()` and `getFormType()` now return fully-qualified class names with a leading backslash (e.g. `\App\Entity\Foo::class`). This prevents edge cases with imported aliases and aligns with recommended FQCN style. (no BC break)

## Unreleased

### Added
- feat(crud): Maker now accepts either a Twig namespace (e.g. `Admin`, `NeoxCrud`) or a full template path (e.g. `@Admin/Partial/_layout-administrator.html.twig` or `Admin/Partial/_layout-administrator.html.twig`) for the base layout. Backward compatibility preserved: existing `templates_namespace` continues to work unchanged, and CLI option `--twig-namespace` keeps the same name but now also accepts a full path. Generated templates use a new variable `twig_base_layout` with a safe fallback to `admin/_layout.html.twig`.

### Fixed
- maker/templates: Form template (`NeoxCrudForm.tpl.twig`) no longer uses the `defined` test on expressions and avoids null-coalescing on translated expressions. It now compares the translated value to the key, preventing "The defined test only works with simple variables".
- maker/templates: FormType template (`NeoxCrudFormType.tpl.php`) now sets `'data_class'` using a fully-qualified class reference (leading backslash), preventing mistakes like `App\\Form\\App\\Entity\\Foo`. (no BC break)

## Unreleased

### Fixes
- Safer defaults for array-like fields in generated FormTypes:
  - Doctrine `array`/`simple_array` now generate a `CollectionType` with `entry_type` `TextType`, `allow_add/delete`, `by_reference=false`, and `empty_data=[]`.
  - Doctrine `json` keeps `TextareaType` by default, but if the PHP property is typed as `array` (or the field is named `roles`), it generates a `CollectionType` with the same options. This prevents errors like: `Expected argument of type "array", "null" given at property path "roles"`.

No public API changes; behavior only affects newly generated code or `.sav` suggestions.
