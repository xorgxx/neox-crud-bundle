# Référence CLI / Maker (FR)

Cette page détaille les commandes Maker fournies par NeoxCrudBundle pour accélérer la création de vos CRUDs.

Sommaire
- make:neox:crud-handler — Générer uniquement le Handler CRUD
- make:neox:crud-maker — Générer un CRUD complet (handler + form + templates + i18n optionnelle)
- Fichier config.yaml généré par handler
- Bonnes pratiques et remarques de sécurité

---

Terminologie et nommage — ressource (slug) vs espace de noms/FQCN

- resource (slug)
  - Définition: identifiant court, adapté aux URLs/templates (kebab-case recommandé), p. ex. product ou catalog-item.
  - Emploi: routes/URLs, dossiers de templates (templates/admin/<resource>/...), noms et clés de traduction.
  - Ce n’est pas un nom PHP ni une classe; il ne contient jamais de backslashes.

- entity-class (FQCN)
  - Définition: nom de classe PHP pleinement qualifié (namespace + classe), p. ex. App\\Entity\\Product.
  - Raccourci accepté: nom court Doctrine, p. ex. Product, que le maker résout en FQCN d’entité.

- form-type-class (FQCN)
  - Définition: FQCN de la classe FormType, p. ex. App\\Form\\ProductType.

Proposition de nommage (documentation)

Pour éviter la confusion entre un slug et un namespace PHP, nous emploierons ici des libellés plus explicites dans les explications:
- entity-fqcn au lieu de entity-class
- form-type-fqcn au lieu de form-type-class

Note: Les options/arguments réels des commandes restent inchangés pour la compatibilité. Cette page adopte seulement un libellé plus clair dans les textes et exemples.

Exemples
- Avec FQCN explicites et un slug personnalisé:
  - php bin/console make:crud-handler catalog-item App\\Entity\\Product App\\Form\\ProductType
  - php bin/console make:neox:crud App\\Entity\\Product --slug=catalog-item --with-trans --locale=en

Ce qui est généré/utilisé
- Classe Handler sous App\\Crud\\Handler\\<Entity>CrudHandler (classe PHP namespacée)
- FormType sous App\\Form\\<Entity>Type (classe PHP namespacée)
- Les templates Twig sont fournis par le bundle via le namespace `@NeoxCrud` (personnalisables via override)

---

make:crud-handler — Handler seul

Commande
```
php bin/console make:crud-handler <resource> <entity-class> <form-type-class>
```

Arguments
- resource: slug de la ressource, ex: product
- entity-class: FQCN ou raccourci Doctrine, ex: App\Entity\Product ou Product
- form-type-class: FQCN du FormType, ex: App\Form\ProductType

Comportement
- Génère une classe App\Crud\Handler\<Resource>CrudHandler qui étend AbstractDoctrineCrudHandler
- Branche automatiquement le nom de ressource, le FQCN de l’entité et le FormType
- Le rendu Twig est assuré par les templates du bundle sous `@NeoxCrud` (override via `twig.paths` si besoin)
- Crée également un fichier `config.yaml` commenté à côté du handler avec les options supportées. Le fichier inclut la liste des champs Doctrine détectés et une ligne `index_fields` pré-remplie (commentée) avec tous ces champs pour un démarrage rapide.
  La clé `index_fields` supporte aussi des attributs par champ (optionnels) comme `format`, `boolean_icon`, `type: image`, et `voters`. Voir docs/fr/config.md → « Attributs avancés ». Les templates continuent d’utiliser `fields` (noms) et peuvent lire `field_options` pour les attributs.

LiveTable (activation)
- La LiveTable s’active par ressource via le `config.yaml` du handler.
- Option CLI : `--enable-live-table` génère le `config.yaml` du handler avec le bloc `neox_crud.live_table` déjà activé.
- Sinon, décommentez le bloc `neox_crud.live_table` dans le `config.yaml` du handler.
- Voir docs/fr/config.md → « Activer la LiveTable ».

Exemple
```
php bin/console make:crud-handler product App\Entity\Product App\Form\ProductType
```

---

make:neox:crud-maker — CRUD complet

Commande
```
php bin/console make:neox:crud-maker <entity-class> [--slug=<slug>] [--with-trans] [--locale=<fr|en|...>] [--twig-namespace=<NsOuChemin>] [--twig-base-layout=<chemin>] [--with-controller] [--enable-live-table]
```

Arguments
- entity-class: FQCN ou raccourci Doctrine (ex: App\Entity\Product ou Product)

Mode interactif

Si aucun flag n'est fourni, le Maker pose ces questions avant de generer :

```
 Generer un fichier de traductions ? (yes/no) [no]:
 Langue (locale) [fr]:
 Generer un controleur dedie (GenericCrudController) ? (yes/no) [no]:
 Activer le LiveTable ? (yes/no) [no]:
```

Si un flag est passe explicitement (ex. `--with-trans`), la question correspondante est ignoree.

Options
- --slug: slug de la ressource (préféré) (par défaut: nom court de l’entité en minuscule)
- --with-trans: génère un fichier de traduction pour la ressource
- --locale: langue pour le fichier de traduction (défaut: fr)
- --twig-namespace: Soit un namespace Twig (ex. `Admin`, `NeoxCrud`), soit un chemin complet de template Twig (ex. `@Admin/Partial/_layout.html.twig` ou `Admin/Partial/_layout.html.twig`) utilisé pour résoudre le layout de base des templates du bundle. Surcharge `neox_crud.makers.templates_namespace`.
- --twig-base-layout: Chemin Twig explicite pour le layout de base (ex. `@App/admin/_layout.html.twig` ou `/admin/_layout.html.twig`). Prioritaire sur `--twig-namespace` et sur la configuration.
- --with-controller: génère un contrôleur dédié qui étend `GenericCrudController` pour cette ressource (désactivé par défaut).
- `--enable-live-table` : active le LiveTable dans le `config.yaml` du handler (demandé interactivement si absent)
Compatibilité ascendante
- --resource reste accepté comme alias mais est déprécié au profit de --slug.

Comportement
- Génère:
  - un FormType App\Form\<Entity>Type
  - SÉCURITÉ: si le FormType existe déjà, il n’est jamais modifié; une version suggérée est écrite dans src/Form/<Entity>Type.php.sav pour fusion manuelle
  - un Handler App\Crud\Handler\<Entity>CrudHandler
  - un fichier `config.yaml` commenté: `src/Crud/Handle/<Entity>/config.yaml`
  - (aucun template Twig n’est généré ; le bundle fournit des templates par défaut via `@NeoxCrud`)
  - (optionnel) un YAML de traduction pour les champs, aligné sur neox_crud.translations.field_keys

LiveTable (activation)
- La LiveTable s’active par ressource via le `config.yaml` du handler.
- Option CLI : `--enable-live-table` génère le `config.yaml` du handler avec le bloc `neox_crud.live_table` déjà activé.
- Sinon, décommentez le bloc `neox_crud.live_table` dans le `config.yaml` du handler.

Notes
- Le FormType généré devine désormais automatiquement les types de champs Symfony à partir des types Doctrine. Quand le type est reconnu, le champ est déclaré avec le FQCN du FormType sous forme de chaîne; sinon, le type est laissé à null pour laisser Symfony deviner à l’exécution. Les UUID/GUID utilisent TextType par défaut pour éviter des dépendances supplémentaires.

Exemples
```
# Générer un CRUD complet pour Product, ressource "product"
php bin/console make:neox:crud-maker Product

# Générer avec un slug personnalisé et un fichier de traduction anglais
php bin/console make:neox:crud-maker Product --slug=catalog-item --with-trans --locale=en

# Générer en indiquant un namespace Twig personnalisé pour l’héritage de layout
php bin/console make:neox:crud-maker Product --twig-namespace=NeoxCrud

# Générer en indiquant un chemin complet via --twig-namespace
php bin/console make:neox:crud-maker Product --twig-namespace=@Admin/Partial/_layout-administrator.html.twig

# Générer en fournissant directement le layout (prioritaire)
php bin/console make:neox:crud-maker Product --twig-base-layout=/admin/_layout.html.twig

# Générer aussi un contrôleur dédié
php bin/console make:neox:crud-maker Product --with-controller

# Générer l’index avec colonne de sélection et UI des actions de masse (opt‑in)
php bin/console make:neox:crud-maker Product --enable-live-table
```

Le dossier du handler généré contiendra un `config.yaml` éditable:
- `src/Crud/Handle/Product/config.yaml`

Exemple de contenu (toutes les options sont commentées par défaut)
```
# NeoxCrud — Configuration par handler (optionnelle)
# Clés supportées actuellement:
# index_fields: ['id', 'name', 'createdAt']
# ou imbriqué sous neox_crud:
# neox_crud:
#   index_fields: ['id', 'name', 'createdAt']
#
# Champs détectés (Doctrine):
# - id
# - name
# - createdAt
#
# Démarrage rapide — décommentez pour utiliser tous les champs tels quels:
# index_fields: ['id', 'name', 'createdAt']
```

Exposer les routes (afin que le contrôleur CRUD générique soit accessible)
```
# config/routes/neox_crud.yaml
neox_crud:
    resource: '@NeoxCrudBundle/Controller/'
    type: attribute
    prefix: /
```

Ouvrir la page CRUD dans le navigateur
- Index: `http://localhost/admin/<resource>`
- Exemple pour Product (slug "product"): `http://localhost/admin/product`

Fichiers générés (exemple)
- src/Form/ProductType.php (ou .php.sav si déjà présent)
- src/Crud/Handler/ProductCrudHandler.php
- translations/product.<locale>.yaml (si --with-trans)
- src/Controller/ProductCrudController.php (si --with-controller)

Notes sur l’UI de masse (opt‑in)
- La LiveTable (si activée) supporte la sélection + actions de masse dans l’UI du composant.
- En mode classique, le template classique du bundle vise une table simple + actions.

Personnalisation Twig (LiveTable)
- La LiveTable utilise des templates du bundle sous le namespace `@NeoxCrud`.
- Pour personnaliser le rendu sans modifier le bundle, surchargez le namespace `NeoxCrud` via `twig.paths`.
  Voir docs/fr/controller.md → « Personnalisation Twig (override des templates) ».

---

Bonnes pratiques
- Ne modifiez pas les signatures publiques des handlers; elles sont utilisées par le contrôleur générique et la factory
- Utilisez la configuration neox_crud.translations pour définir les clés de champs à générer (label, placeholder, help, etc.)
- Les actions personnalisées doivent être exposées via supportsAction()/handleAction() dans le handler
- Pour la pagination, implémentez ou surchargez findList(Request $request) côté handler

Sécurité
- Les routes CRUD génériques sont généralement protégées (ex: #[IsGranted('ROLE_ADMIN')])
- Les actions custom en POST doivent utiliser un token CSRF de forme: custom_{resource}_{id}_{action}

Voir aussi
- docs/fr/config.md — Référence de configuration
- docs/fr/guide.md — Guide complet et cas d’usage
