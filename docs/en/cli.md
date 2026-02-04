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

make:neox:crud — Full CRUD

Command
```
php bin/console make:neox:crud <entity-class> [--slug=<slug>] [--with-trans] [--locale=<fr|en|...>] [--twig-namespace=<NsOrPath>] [--twig-base-layout=<path>] [--with-controller] [--with-bulk-ui]
```

Argument
- entity-class: FQCN or Doctrine shortcut (e.g. App\Entity\Product or Product)

Options
- --slug: the resource slug (preferred) (default: lowercased short entity name)
- --with-trans: generate a translation YAML for the resource
- --locale: language for the translation file (default: fr)
- --twig-namespace: Either a Twig namespace (e.g. `Admin`, `NeoxCrud`) or a full Twig template path (e.g. `@Admin/Partial/_layout.html.twig` or `Admin/Partial/_layout.html.twig`). Used to resolve the base layout for bundle templates. Overrides configuration `neox_crud.makers.templates_namespace`.
- --twig-base-layout: Explicit Twig base layout path (e.g. `@App/admin/_layout.html.twig` or `/admin/_layout.html.twig`). Overrides both `--twig-namespace` and configuration.
- --with-controller: also generate a dedicated controller extending `GenericCrudController` for this resource (disabled by default).
- --with-bulk-ui: no longer generates Twig templates, so this flag does not affect generated files.

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
# Generate a full CRUD for Product, resource slug "product"
php bin/console make:neox:crud Product

# Generate with a custom slug and an English translation file
php bin/console make:neox:crud Product --slug=catalog-item --with-trans --locale=en

# Generate using a custom Twig namespace for layout inheritance
php bin/console make:neox:crud Product --twig-namespace=NeoxCrud

# Generate using a full template path via --twig-namespace
php bin/console make:neox:crud Product --twig-namespace=@Admin/Partial/_layout-administrator.html.twig

# Generate using an explicit base layout (takes precedence)
php bin/console make:neox:crud Product --twig-base-layout=/admin/_layout.html.twig

# Also generate a dedicated controller
php bin/console make:neox:crud Product --with-controller

# Generate index with selection column and bulk actions UI (opt-in)
php bin/console make:neox:crud Product --with-bulk-ui
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
  
Notes about bulk UI (opt-in)
- The LiveTable (when enabled) supports selection + bulk actions in the component UI.
- In classic mode, the bundle classic template focuses on a simple table + actions.

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
