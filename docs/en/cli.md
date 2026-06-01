CLI / Maker Reference (EN)

This page details the Maker commands provided by NeoxCrudBundle to speed up your CRUD setup.

Summary
- make:crud-handler — Generate only the CRUD Handler
- make:neox:crud — Generate a full CRUD (handler + form + templates + optional i18n)
- Best practices and security notes

---

Terminology and naming — resource vs namespace/FQCN

- resource (slug)
  - What it is: a short, URL/template-friendly identifier (kebab-case recommended), e.g. product or catalog-item.
  - Where it’s used: routes/URLs, template directory names (templates/admin/<resource>/...), translation file names and keys.
  - Not a PHP name and not a class; it never contains backslashes.

- entity-class (FQCN)
  - What it is: a PHP fully-qualified class name (namespace + class), e.g. App\\Entity\\Product.
  - Shortcut accepted: Doctrine short name like Product, which the maker resolves to your entity FQCN.

- form-type-class (FQCN)
  - What it is: a PHP fully-qualified FormType class name, e.g. App\\Form\\ProductType.

Naming proposal (documentation only)

To avoid confusion between slugs and PHP namespaces, we will refer to these arguments as:
- entity-fqcn instead of entity-class
- form-type-fqcn instead of form-type-class

Note: The actual command options stay the same for backward compatibility. This page simply uses the clearer wording in explanations and examples.

Examples
- Using explicit FQCNs and a custom slug:
  - php bin/console make:crud-handler catalog-item App\\Entity\\Product App\\Form\\ProductType
  - php bin/console make:neox:crud App\\Entity\\Product --slug=catalog-item --with-trans --locale=en

What gets generated/used
- Handler class under App\\Crud\\Handler\\<Entity>CrudHandler (namespaced PHP class)
- FormType under App\\Form\\<Entity>Type (namespaced PHP class)
- Twig templates are provided by the bundle under the `@NeoxCrud` namespace (customizable via overriding)

---

make:crud-handler — Handler only

Command
```
php bin/console make:crud-handler <resource> <entity-class> <form-type-class>
```

Arguments
- resource: resource slug, e.g. product
- entity-class: FQCN or Doctrine shortcut, e.g. App\Entity\Product or Product
- form-type-class: FormType FQCN, e.g. App\Form\ProductType

Behavior
- Generates a class App\Crud\Handler\<Resource>CrudHandler extending AbstractDoctrineCrudHandler
- Wires the resource name, the entity FQCN, and the FormType automatically
- Twig rendering is handled by bundle templates under `@NeoxCrud` (override via `twig.paths` if you need customization)
- Creates a commented per-handler `config.yaml` next to the handler with a list of detected Doctrine fields and a ready-to-uncomment `index_fields` example pre-filled with those fields.
  The `index_fields` key now also supports per-field attributes (optional) such as `format`, `boolean_icon`, `type: image`, and `voters`. See docs/en/config.md → "Advanced attributes". Templates keep using `fields` (names) and may read `field_options` for attributes.

LiveTable (enable)
- LiveTable is enabled per resource via the handler `config.yaml`.
- CLI option: `--enable-live-table` generates the handler `config.yaml` with the `neox_crud.live_table` block already enabled.
- Otherwise, uncomment the `neox_crud.live_table` block in the handler `config.yaml`.
- See docs/en/config.md → "Enable LiveTable".

Example
```
php bin/console make:crud-handler product App\Entity\Product App\Form\ProductType
```

---

make:neox:crud-maker — Full CRUD

Command
```
php bin/console make:neox:crud-maker <entity-class> [--slug=<slug>] [--with-trans] [--locale=<fr|en|...>] [--twig-namespace=<NsOrPath>] [--twig-base-layout=<path>] [--with-controller] [--enable-live-table]
```

Argument
- entity-class: FQCN or Doctrine shortcut (e.g. App\Entity\Product or Product)

Interactive mode

If no flags are provided, the Maker asks these questions before generating:

```
 Generate a translation file? (yes/no) [no]:
 Locale [fr]:
 Generate a dedicated controller (GenericCrudController)? (yes/no) [no]:
 Enable LiveTable? (yes/no) [no]:
```

If a flag is explicitly passed (e.g. `--with-trans`), the corresponding question is skipped.

Options
- `--slug`: the resource slug (default: lowercased short entity name)
- `--with-trans`: generate a translation YAML for the resource (asked interactively if not set)
- `--locale`: language for the translation file, default `fr` (asked if `--with-trans` is active)
- `--twig-namespace`: a Twig namespace (e.g. `Admin`) or a full template path (e.g. `@Admin/Partial/_layout.html.twig`). Overrides `neox_crud.makers.templates_namespace`. Usually configured globally — not asked interactively.
- `--twig-base-layout`: explicit Twig base layout path. Takes precedence over `--twig-namespace`. Usually configured globally — not asked interactively.
- `--with-controller`: generate a dedicated controller extending `GenericCrudController` (asked interactively if not set)
- `--enable-live-table`: enable LiveTable in the handler `config.yaml` (asked interactively if not set)

Backward compatibility
- --resource is still accepted as an alias but is deprecated in favor of --slug.

Behavior
- Generates:
  - a FormType App\Form\<Entity>Type
    - SAFETY: if the FormType already exists, it will never be modified; a suggested version is written to src/Form/<Entity>Type.php.sav for manual merge
  - a Handler App\Crud\Handler\<Entity>CrudHandler
  - (no Twig templates are generated; the bundle provides defaults under `@NeoxCrud`)
  - (optional) a translation YAML for field keys aligned with neox_crud.translations.field_keys

LiveTable (enable)
- LiveTable is enabled per resource via the handler `config.yaml`.
- CLI option: `--enable-live-table` generates the handler `config.yaml` with the `neox_crud.live_table` block already enabled.
- Otherwise, uncomment the `neox_crud.live_table` block in the handler `config.yaml`.

Notes
- The generated FormType now auto-guesses Symfony Form Types from Doctrine types. When recognized, the field is declared with the FormType FQCN as a string; otherwise the type is left to null to let Symfony guess at runtime. UUID/GUID default to TextType to avoid extra deps.

Examples
```
# Fully interactive (Maker asks all questions)
php bin/console make:neox:crud-maker Product

# All flags on the command line (no questions asked)
php bin/console make:neox:crud-maker Product --with-trans --locale=en --with-controller --enable-live-table

# Custom slug and English translation file
php bin/console make:neox:crud-maker Product --slug=catalog-item --with-trans --locale=en

# Custom Twig namespace for layout inheritance
php bin/console make:neox:crud-maker Product --twig-namespace=NeoxCrud

# Explicit base layout (takes precedence over --twig-namespace)
php bin/console make:neox:crud-maker Product --twig-base-layout=/admin/_layout.html.twig
```

Expose the routes (so the generic CRUD controller is reachable)
```
# config/routes/neox_crud.yaml
neox_crud:
    resource: '@NeoxCrudBundle/Controller/'
    type: attribute
    prefix: /
```

Open your CRUD page in the browser
- Index: `http://localhost/admin/<resource>`
- Example for Product (slug "product"): `http://localhost/admin/product`

Generated files (example)
- src/Form/ProductType.php (or .php.sav if already present)
- src/Crud/Handler/ProductCrudHandler.php
- src/Crud/Handle/Product/config.yaml  (commented; includes detected fields list and `index_fields` example)
- translations/product.<locale>.yaml (if --with-trans)
- src/Controller/ProductCrudController.php (if --with-controller)
  
Twig customization (LiveTable)
- LiveTable uses bundle templates under the `@NeoxCrud` namespace.
- To customize LiveTable rendering without touching the bundle, override `NeoxCrud` via `twig.paths`.
  See docs/en/controller.md → "Twig customization (override templates)".

---

Best practices
- Do not change the public signatures of handlers; they are used by the generic controller and the factory
- Use neox_crud.translations to define the field keys to generate (label, placeholder, help, etc.)
- Custom actions should be exposed via supportsAction()/handleAction() in the handler
- For pagination, implement or override findList(Request $request) in your handler

Security
- Generic CRUD routes are typically protected (e.g. #[IsGranted('ROLE_ADMIN')])
- Custom actions in POST must use a CSRF token of the form: custom_{resource}_{id}_{action}

See also
- [Configuration reference](./config.md)
- [Full guide and use cases](./guide.md)
- [Configuration examples](./config-examples.md)
- [Identifier support (UUID/ULID)](./uuid.md)
