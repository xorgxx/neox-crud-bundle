Référence de configuration (FR)

Ce document décrit toutes les options disponibles sous la clé neox_crud et leurs valeurs par défaut. Surchargez-les dans config/packages/neox_crud.yaml.

Sommaire
- uploads_dir
- mercure.enabled
- mercure.topic_prefix
- makers.enabled
- makers.templates_namespace
- makers.base_layout
- makers.content_block
- translations.field_keys
- translations.patterns
 - Surcharges YAML par handler (index_fields)

---

Vue d’ensemble (avec valeurs par défaut)

```yaml
# config/packages/neox_crud.yaml
neox_crud:
  # Dossier d’upload utilisé par le bundle (par défaut: public/uploads)
  uploads_dir: 'public/uploads'
  
  # Intégration Mercure (désactivable)
  mercure:
    enabled: true
    topic_prefix: '/crud'   # Préfixe des topics Mercure
  
  # Options du Maker (générateur)
  makers:
    enabled: true
    # Namespace Twig utilisé par défaut pour les templates générés
    # Peut être surchargé en CLI via --twig-namespace
    templates_namespace: 'App'   # ex: vos layouts sous @App/admin/_layout.html.twig
    # Chemin explicite du layout de base (peut être surchargé par --twig-base-layout)
    base_layout: null
    # Nom du block Twig cible où le CRUD doit s’insérer (ex: content, body, admin_content)
    # Défaut: content
    content_block: content
  
  # Schéma de traductions auto-générées par le Maker (option --with-trans)
  translations:
    # Clés créées pour chaque champ
    field_keys: ['label', 'placeholder']
    # Motifs par clé (placeholders: %field%, %field_label%, %resource%)
    patterns: {}

  live_table:
    enabled: false
    pagination_position: bottom # top | bottom | all
    default_per_page: 25
    max_per_page: 100
```

Détails des options

1) uploads_dir
- Type: string
- Défaut: 'public/uploads'
- Description: Répertoire racine des fichiers téléversés, utilisé par les handlers/formulaires qui en ont besoin. Vous pouvez le redéfinir vers un autre répertoire public ou un stockage monté.

2) mercure
- Type: objet
- Description: Notifications temps réel via Mercure.

2.1) mercure.enabled
- Type: bool
- Défaut: true
- Effet: Active/désactive la publication des événements CRUD (create/update/delete) vers Mercure via l’implémentation par défaut de CrudNotifierInterface.

2.2) mercure.topic_prefix
- Type: string
- Défaut: '/crud'
- Effet: Préfixe appliqué aux topics Mercure, par exemple `/crud/product/123`.

3) makers
- Type: objet
- Description: Contrôle l’activation des commandes Maker fournies par le bundle.

3.1) makers.enabled
- Type: bool
- Défaut: true
- Effet: Si false, les commandes `make:crud-handler` et `make:neox:crud` sont retirées de l’enregistreur de commandes.

3.2) makers.templates_namespace
- Type: string|null
- Défaut: null
- Effet: Détermine le layout de base utilisé par les templates générés. Accepte soit :
  - un namespace Twig (ex. `Admin`, `NeoxCrud`) → le layout devient `@<namespace>/admin/_layout.html.twig`
  - un chemin de template complet (ex. `@Admin/Partial/_layout.html.twig` ou `Admin/Partial/_layout.html.twig`) → utilisé tel quel
  Surchargable par commande via `--twig-namespace`.
  Remarque: la résolution finale tient aussi compte de `makers.base_layout` et de l’option CLI `--twig-base-layout`.
  Quand tout est nul/absent, les templates générés retombent sur `'/admin/_layout.html.twig'`.

3.3) makers.base_layout
- Type: string|null
- Défaut: null
- Effet: Chemin Twig explicite pour le layout de base utilisé par le Maker lors de la génération (ex. `@App/admin/_layout.html.twig` ou `/admin/_layout.html.twig`).
- Priorité: CLI `--twig-base-layout` > cette option `makers.base_layout` > `makers.templates_namespace` (dérivé en `@<ns>/admin/_layout.html.twig`) > défaut `'/admin/_layout.html.twig'`.

3.4) makers.content_block
- Type: string
- Défaut: content
- Effet: indique dans quel block Twig (de votre layout) les templates du bundle doivent rendre le contenu CRUD.
  Exemples: `content`, `body`, `admin_content`.

4) translations
- Type: objet
- Description: Paramètres pour les clés de traduction générées/utilisées par les formulaires et les makers.

4.1) translations.field_keys
- Type: array<string>
- Défaut: ['label', 'placeholder']
- Effet: Liste des suffixes de clés de traduction générées par défaut pour chaque champ. Exemple pour la ressource `product` et le champ `name`:
  - `product.field.name.label`
  - `product.field.name.placeholder`
- Vous pouvez ajouter 'help', 'hint', etc., si vos templates les gèrent.

4.2) translations.patterns
- Type: map<string, string>
- Défaut: {}
- Effet: Modèles de texte utilisés par le Maker pour préremplir certaines clés. Les modèles peuvent référencer `%field_label%` (ou d’autres variables futures) pour interpoler une valeur lisible.

5) live_table
- Type: objet
- Description: Options globales pour la table d’index interactive (Pagerfanta + Symfony UX LiveComponent). Fonctionnalité opt-in.

5.1) live_table.enabled
- Type: bool
- Défaut: false
- Effet: Active l’index live par défaut. Peut être surchargé par handler via la clé YAML `live_table`.

5.2) live_table.default_per_page
- Type: int
- Défaut: 25

5.3) live_table.max_per_page
- Type: int
- Défaut: 100

5.4) live_table.pagination_position
- Type: enum('top'|'bottom'|'all')
- Défaut: bottom
- Effet: Contrôle l’affichage de la pagination dans la table live.
  - `top` : pagination uniquement au-dessus du tableau
  - `bottom` : pagination uniquement en dessous du tableau
  - `all` : pagination en haut et en bas

Activer la LiveTable

La LiveTable est une fonctionnalité opt-in.

Activation par handler (une ressource)

Dans le YAML à côté de votre handler (ex: `src/Crud/Handler/ProductCrudHandler/config.yaml`) :

```yaml
neox_crud:
  live_table:
    enabled: true
    pagination_position: top   # top | bottom | all
    default_per_page: 4
    max_per_page: 4
```

Remarque
- Vous pouvez aussi générer ce bloc déjà activé via la CLI : `make:neox:crud-handler --enable-live-table` ou `make:neox:crud-maker --enable-live-table`.

6) Surcharges YAML par handler (index_fields)
- Type: basé sur fichier (optionnel)
- Défaut: non utilisé
- Effet: Définir quels champs apparaissent dans le tableau de la vue index pour un handler donné, sans surcharger le code PHP.

Emplacement (premier trouvé l’emporte), relatif au fichier de votre classe handler concrète (ex. `src/Crud/Handler/ProductCrudHandler.php`) :
- `src/Crud/Handler/ProductCrudHandler/config.yaml`
- `src/Crud/Handler/ProductCrudHandler/ProductCrudHandler.yaml`
  - `src/Crud/Handler/ProductCrudHandler/config/crud.yaml`

Formats supportés

Vous pouvez déclarer `index_fields` sous plusieurs formes (rétrocompatibles) :

1) Liste simple (BC)
```yaml
index_fields: ['id', 'name', 'createdAt']
```

2) Liste de maps “identifiées” (recommandé)
```yaml
index_fields:
  - { name: 'name', sortable: true, searchable: true }
  - { name: 'roles' }
  - { name: 'createdAt', format: 'Y-m-d' }
```

3) Map associative (champ => options)
```yaml
index_fields:
  name: { sortable: true, searchable: true }
  roles: ~
  createdAt: { format: 'Y-m-d' }
```

Options utiles (surtout pour la LiveTable)
- `sortable` (bool) : autorise le tri sur cette colonne (LiveTable)
- `searchable` (bool) : inclut le champ dans la recherche texte (LiveTable)
- `query_path` (string) : chemin DQL, utile pour les relations (ex: `user.email`)
- `join` (string) : `left` (défaut) ou `inner` (si `query_path` traverse des relations)
- `label` (string) : libellé affiché de la colonne

Remarque
- Les options `sortable/searchable/query_path/join` sont exploitées par le query builder de la LiveTable (UX LiveComponent). En mode index “classique”, elles peuvent être ignorées selon vos templates.

6.1) Actions UI par handler (opt-in)
- Type : basé fichier (optionnel)
- Défaut : non utilisé
- Effet : configure les boutons d’actions de la vue index sans changer le PHP. Totalement rétrocompatible. Si absent, les templates gardent leur rendu par défaut.

Clés optionnelles supportées dans le YAML du handler (forme plate ou imbriquée sous `neox_crud:`) :
- `actions` : boutons par ligne (colonne actions)
- `bulk_actions` : actions sur la sélection courante (si votre UI gère la sélection)
- `toolbar_buttons` : boutons affichés à côté de « Nouveau » dans la vue d’index

Options par action/bouton :
- `name` (string, requis)
- `label` (string ; défaut = `name`)
- `icon` (string ; classe d’icône optionnelle)
- `route` (string) ou `path` (string)
- `method` (string ; défaut `GET`)
- `confirm` (string ; message de confirmation optionnel)
- `class` (string ; classes CSS additionnelles)
- `priority` (int ; plus grand = affiché en premier)
- `params` (map ; prend en charge des placeholders dynamiques)
- `voters` (string ou liste<string> ; visibilité/autorisation gérée par votre Voter si vous en utilisez)
- `if` (string|bool ; condition simple évaluée sur `entity.*` ou `context.*`)
- `selection_required` (bool ; bulk uniquement, défaut true)
- `turbo` (bool|map ; optionnel) :
  - `false` ou `{ enabled: false }` → ajoute `data-turbo="false"`
  - `{ frame: "_top"|"crud_table"|"<frame_id>" }` → ajoute `data-turbo-frame="..."`
  - `{ confirm: "..." }` → ajoute `data-turbo-confirm="..."` et surcharge `confirm`

Paramètres dynamiques et conditions
- Vous pouvez référencer des valeurs de l’entité et du contexte dans `params` et `if` via :
  - `"entity.<prop>"` (ex. `entity.id`, `entity.user.email`)
  - `"context.<key>"` (fourni par le contrôleur lors de la résolution des actions)
- L’évaluateur `if` supporte des tests vrai/faux simples, ex. `entity.enabled`, `!entity.archived`.

Sécurité
- Les actions non‑GET rendues par les templates de démonstration sont protégées par des tokens CSRF pour les routes natives :
  - Suppression : nom du token `delete_{id}`
  - Actions POST custom : nom du token `custom_{resource}_{id}_{action}`
- Les cibles non‑GET inconnues ne sont pas postées automatiquement par sécurité (rendu désactivé par défaut) ; adaptez vos templates si besoin.

Ordre d’affichage
- Les entrées sont triées par `priority` (desc), puis `name` (asc) pour la stabilité.

Exemples (forme imbriquée)
```yaml
neox_crud:
  actions:
    - name: edit
      label: "Éditer"
      route: "neox_crud_admin_crud_edit"
      params: { id: "entity.id" }
      priority: 100
    - name: delete
      label: "Supprimer"
      route: "neox_crud_admin_crud_delete"
      method: DELETE
      params: { id: "entity.id" }
      confirm: "Confirmer la suppression ?"
      class: "btn-outline-danger"
      priority: 50
      if: "!entity.archived"

  bulk_actions:
    - name: bulk_delete
      label: "Supprimer la sélection"
      route: "neox_crud_admin_crud_custom"
      method: POST
      params: { action: "bulk_delete" }
      selection_required: true

  toolbar_buttons:
    - name: export_csv
      label: "Exporter CSV"
      route: "neox_crud_admin_crud_custom"
      method: GET
      params: { action: "export_csv" }
```

Note sur le rendu UI (actions de masse)
- Par défaut, le template d’index fourni n’affiche pas `bulk_actions` afin de préserver la rétrocompatibilité.
- Vous pouvez soit surcharger votre template d’index pour ajouter une colonne de sélection + des formulaires POST, soit générer votre CRUD avec le flag Maker `--with-bulk-ui` qui fournit une variante de template incluant l’UI de masse.

Append des actions par défaut (opt‑in)
-------------------------------------

Vous pouvez demander au handler d’« ajouter » les actions CRUD par défaut (Éditer/Supprimer) après vos actions personnalisées, sans les écraser et sans créer de doublons. Activez l’option YAML `append_default_actions` (plate ou sous `neox_crud:`):

Exemple (clé plate):
```yaml
actions:
  - { name: view, label: "Voir", route: app_product_show, method: GET, params: { id: "entity.id" } }
append_default_actions: true
```

Exemple (imbriqué sous neox_crud):
```yaml
neox_crud:
  actions:
    - { name: view, label: "Voir", route: app_product_show, method: GET, params: { id: "entity.id" } }
  append_default_actions: true
```

Comportement:
- Si vos actions ne contiennent pas `edit` et/ou `delete`, elles seront ajoutées en fin de liste avec les routes du bundle (`neox_crud_admin_crud_edit`, `neox_crud_admin_crud_delete`) et les méthodes appropriées (GET/DELETE).
- Si vous avez déjà défini `edit` ou `delete`, aucune duplication n’est faite.
- Par défaut (option absente ou `false`), rien ne change: seules vos actions configurées sont rendues.

Rétrocompatibilité
- Si ces clés sont absentes, rien ne change dans les templates ni le comportement.
- Les API publiques et noms de routes restent inchangés.

Options LiveTable par handler (opt‑in)
-------------------------------------

En plus de `live_table` (activation), vous pouvez surcharger la pagination par handler.

Forme unifiée recommandée (imbriquée sous `neox_crud:`) :
```yaml
neox_crud:
  live_table:
    enabled: true
    default_per_page: 4
    max_per_page: 4
    pagination_position: all
```

Notes :
- Le bundle reste compatible avec l’ancien format `live_table: true` (clé booléenne), mais la forme unifiée ci-dessus est à privilégier pour la lisibilité.

Clés acceptées :
```yaml
# Soit la clé plate
index_fields: ['id', 'name', 'createdAt']

# Soit imbriquée sous neox_crud
neox_crud:
  index_fields: ['id', 'name', 'createdAt']
```

Notes :
- Si aucun fichier n’est trouvé (ou si la clé manque/est invalide), le comportement par défaut s’applique : tous les champs Doctrine sauf `id`.
- 100% rétrocompatible et opt‑in.

Attributs avancés (optionnels)
------------------------------

Vous pouvez associer des attributs à chaque champ pour contrôler son rendu dans la liste (index) ou sa visibilité selon des voters/roles. Trois syntaxes équivalentes sont supportées, et elles restent rétrocompatibles avec la liste simple.

1) Liste simple (BC) :
```yaml
index_fields: ['title', 'email', 'enabled', 'createdAt']
```

2) Liste d’objets avec une clé `name` (ou `field`) :
```yaml
index_fields:
  - { name: 'title' }
  - { name: 'email', format: 'text' }
  - { name: 'enabled', boolean_icon: true }   # affiche des icônes ✓/✗ pour les booléens
  - { name: 'image', type: 'image', class: 'thumb-48' } # affiche une balise <img>
  - { name: 'createdAt', format: 'Y-m-d H:i' } # format de date/heure
  - { name: 'roles', voters: ['ROLE_ADMIN'] }  # visible uniquement si le voter est accordé
```

3) Map associative : nom du champ en clé, options en valeur :
```yaml
index_fields:
  title: ~
  email: { format: 'text' }
  enabled: { boolean_icon: true }
  image: { type: 'image', class: 'thumb-48' }
  createdAt: { format: 'Y-m-d' }
  roles: { voters: ['ROLE_ADMIN', 'ROLE_MANAGER'] }
```

Options de rendu (liste non exhaustive) :
- `format` : string. Pour les dates, tout format PHP (ex. `Y-m-d`).
- `boolean_icon` : bool. Si `true`, affiche une icône coche/croix (✓/✗) pour les booléens.
- `type` : string. Type de rendu automatique. Valeurs disponibles :
  - `truncate` : tronque le texte. Options : `length` (défaut: 50).
  - `currency` : formatage monétaire. Options : `symbol` (défaut: '€'), `decimals` (défaut: 2).
  - `number` : formatage numérique. Options : `decimals` (défaut: 2), `decimal_separator` (défaut: ','), `thousand_separator` (défaut: ' ').
  - `percent` : pourcentage. Options : `decimals` (défaut: 0).
  - `badge` : badge coloré. Options : `color_map` (map valeur → couleur Bootstrap, ex: `{ active: 'success', inactive: 'secondary' }`).
  - `boolean_badge` : badge success/danger pour booléens (Oui/Non).
  - `link` : lien cliquable. Options : `target` (défaut: '_blank').
  - `email` : lien mailto.
  - `image` : balise `<img>`. Options : `class` (classe CSS).
- `class` : string. Classe(s) CSS supplémentaires (pour type: image).
- `voters` ou `voter` : string ou string[] d’attributs de sécurité ; le template peut masquer un champ si non autorisé.

Relations Doctrine (Maker)
-------------------------

Le Maker peut être configuré pour définir le comportement par défaut des champs de relation (ManyToOne/ManyToMany/OneToOne/OneToMany) lors de la génération des FormType.

Configuration (dans `config/packages/neox_crud.yaml`) :

```yaml
neox_crud:
  makers:
    relations:
      default_render: 'select'          # select|autocomplete|checkbox
      choice_label_priority: ['name', 'title', 'label', 'id']
      nullable_required: false
      order: 'interleaved'              # end|interleaved|start
      group_relations: true
```

Notes :
- `default_render=autocomplete` nécessite Symfony UX Autocomplete (voir guide d’installation).
- Pour afficher/filtrer/trier sur une relation dans `index_fields`, utilisez généralement `query_path` et `join` (ex: `category.name`, `join: left`).

Options de requête (opt-in, table live)
------------------------------------

En plus des options de rendu, `index_fields` peut porter des capacités de requête pour la table live (sans config dupliquée) :
- `sortable` : bool (défaut: false)
- `searchable` : bool (défaut: false)
- `filter` : map (optionnel), ex. `{ type: boolean }`, `{ type: choice, choices: { Yes: '1', No: '0' } }`, `{ type: date }`
- `join` : `left|inner` (optionnel, défaut: left) pour les champs en dot-notation
- `query_path` : string (optionnel) si le champ UI diffère du chemin Doctrine

Surcharge handler (activation live)
----------------------------------

Vous pouvez activer/désactiver l’index live par handler avec la clé `live_table` (bool), soit à plat soit sous `neox_crud:` :

```yaml
live_table: true

# ou
neox_crud:
  live_table: true
```

Sélection multiple + actions de masse (table live)
-------------------------------------------------

Lorsque la table live est activée et que des `bulk_actions` sont configurées dans le YAML du handler, la table affiche :
- une colonne de cases à cocher par ligne,
- un compteur de sélection,
- un bouton « Select page » (sélectionner la page courante),
- un bouton « Clear » (vider la sélection).

Exécution des actions de masse

- Les actions de masse proviennent uniquement de `bulk_actions` (pas d’input utilisateur brut).
- Les actions non‑GET sont rendues sous forme de formulaire POST (sécurité CSRF) et envoient la sélection sous la clé `ids`.
- Format envoyé : `ids` contient un JSON (liste) des IDs sélectionnés.
- Pour les routes custom (`neox_crud_admin_crud_custom`), le token CSRF est généré avec l’ID `custom_<resource>_0_<action>`.

Exemple côté handler (lecture de `ids`)

Dans votre `supportsAction()` / `handleAction()`, vous pouvez traiter une action `bulk_delete` ainsi :

```php
$idsRaw = $request->request->get('ids');
$ids = is_string($idsRaw) ? json_decode($idsRaw, true) : $idsRaw;
if (!is_array($ids)) {
    $ids = [];
}
foreach ($ids as $id) {
    // find + revalider droits + modifier/supprimer
}
```

Note : Le contrat des templates reste inchangé. Les templates existants peuvent continuer à utiliser `fields` (noms seuls). Si vous adoptez les attributs, lisez aussi `field_options` depuis le contexte transmis par le contrôleur : un tableau associatif indexé par nom de champ → options.

Astuce :
- Lorsque vous utilisez les commandes du Maker, un fichier `config.yaml` commenté est généré à côté de votre handler. Il contient la liste des champs Doctrine détectés pour votre entité ainsi qu’une ligne `index_fields` prête à être décommentée, pré-remplie avec ces champs pour un démarrage rapide.

Exemple avancé

```yaml
neox_crud:
  uploads_dir: 'public/media'
  mercure:
    enabled: true
    topic_prefix: '/realtime/crud'
  makers:
    enabled: true
    # Vous pouvez fournir un namespace OU un chemin de template complet
    # templates_namespace: 'Admin'                              # namespace
    # templates_namespace: '@Admin/Partial/_layout.html.twig'  # chemin complet
    # Ou définir un layout explicite (prioritaire sur templates_namespace)
    # base_layout: '/admin/_layout.html.twig'
  translations:
    field_keys: ['label', 'placeholder', 'help']
    patterns:
      placeholder: 'Saisir %field_label%'
      help: 'Aide concernant %field_label%'
```

Voir aussi
- docs/fr/cli.md — Référence CLI / Maker
- docs/fr/guide.md — Guide et cas d’usage

Cartographie par défaut des types de champs (Maker)
--------------------------------------------------

Lors de la génération d’un CRUD, le Maker devine les types de champs Symfony Form à partir des métadonnées Doctrine :

- string → `TextType`
- text → `TextareaType`
- integer/smallint/bigint → `IntegerType`
- float/decimal → `NumberType`
- boolean → `CheckboxType` (les booléens « nullable » sont générés avec `required=false`)
- date/date_immutable → `DateType`
- datetime/datetimetz/datetime_immutable → `DateTimeType`
- time/time_immutable → `TimeType`
- uuid/guid → `TextType`
- array/simple_array → `CollectionType` avec :
  - `entry_type=TextType`, `allow_add=true`, `allow_delete=true`, `by_reference=false`, `empty_data=[]`
- json → par défaut `TextareaType` pour éditer du JSON brut. Si la propriété PHP est typée `array` (ou si le champ s’appelle `roles`), le Maker utilise `CollectionType` avec les mêmes options, afin qu’une soumission vide donne `[]` et non `null`.

Ces valeurs sont des défauts sûrs pour le code généré. Vous pouvez librement adapter le FormType généré selon votre UX.
