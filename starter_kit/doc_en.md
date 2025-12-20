---

# 🇬🇧 **GUIDE EN — Product Example (NeoxCrudBundle)**  
*(à mettre dans `guide_en.md`)*

```markdown
# 📘 Usage Guide — Product Example (NeoxCrudBundle)

This example demonstrates how to build a complete CRUD for a `Product` entity using **NeoxCrudBundle v1.2.0**.

It shows:

- how to register a CRUD handler,
- how to use the GenericCrudController,
- how to build a FormType,
- how to work with UUID identifiers,
- how to create custom actions (`publish`),
- automatic CSRF protection,
- Doctrine transactional write operations,
- automatic pagination through `findList()`,
- Mercure event broadcasting.

---

# 🧩 1. Folder structure

ProductCrudExample/
├── src/Controller/ProductCrudController.php
├── src/Crud/ProductCrudHandler.php
├── src/Form/ProductType.php
├── src/Entity/Product.php
├── templates/admin/product/index.html.twig
├── templates/admin/product/form.html.twig
└── config/routes/app_product.yaml

yaml
Copier le code

Each file represents one part of the CRUD pipeline.

---

# 🛠️ 2. Installation in your Symfony project

Copy these files into your Symfony project.

Then register the handler:

```yaml
services:
    App\Crud\ProductCrudHandler:
        tags: ['neox_crud.handler']
The bundle will automatically detect it.

🔗 3. Routing
yaml
Copier le code
app_product_crud:
    resource: App\Controller\ProductCrudController
    type: attribute
⚠️ Do NOT restrict the {id} parameter
The bundle supports UUID / ULID / string / int.

🔐 4. Security
The generic controller already requires:

php
Copier le code
#[IsGranted('ROLE_ADMIN')]
Make sure /admin is protected:

yaml
Copier le code
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
🧱 5. Entity Product (UUID)
php
Copier le code
#[ORM\Id]
#[ORM\Column(type: 'uuid')]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
private ?Uuid $id = null;
🧰 6. FormType Product
Simple Symfony form:

php
Copier le code
$builder
    ->add('title')
    ->add('price')
    ->add('published');
🛠️ 7. CrudHandler
Defines:

resource name,

entity class,

formType,

template prefix,

custom action support,

transactional writes.

Supports an additional "publish" action:

php
Copier le code
public function supportsAction(string $action, string $method): bool
{
    return $action === 'publish' && $method === 'POST';
}
🎛️ 8. Custom action: publish
In Twig:

twig
Copier le code
<input type="hidden" name="_token"
       value="{{ csrf_token('custom_product_' ~ product.id ~ '_publish') }}">
The generic controller validates the CSRF token and delegates the logic to the handler.

🖥️ 9. Twig templates
index.html.twig
Displays:

list,

edit,

delete (CSRF),

publish (custom action).

form.html.twig
Standard Symfony form rendering.

🔄 10. What NeoxCrudBundle handles for you
No need to write:

❌ CRUD controller
❌ pagination logic
❌ CSRF handling
❌ Doctrine transactions
❌ routing
❌ UUID handling
❌ delete logic
❌ Mercure broadcast
❌ event dispatching

The bundle provides:

✔ Generic CRUD controller
✔ Pagination (findList)
✔ Full CSRF protection
✔ Automatic Doctrine transactions
✔ Mercure broadcasting
✔ Custom actions
✔ UUID/int/string ID support
✔ Clean extensible handlers

🎉 Conclusion
This example is the recommended way to use NeoxCrudBundle.
You get a complete CRUD with minimal code and maximum flexibility.

yaml
Copier le code

---

Si tu veux, je prépare maintenant **un ZIP propre contenant ces deux `.md`** (FR + EN) déjà complets et formatés.











ChatGPT peut commettre des erreurs. Il est recommandé de vérifier les informations 