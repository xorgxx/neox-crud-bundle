Tu es un contributeur senior Symfony 7.3 / PHP 8.3. Tu travailles sur NeoxCrudBundle (CRUD générique avec GenericCrudController + CrudHandlers).
Objectif : ajouter une fonctionnalité OPT-IN qui remplace l’index (liste) actuel par une table interactive server-side basée sur Pagerfanta + Symfony UX LiveComponent, compatible Turbo + Stimulus + Bootstrap 5.

Contraintes obligatoires :
- Minimal patch, pas de refonte massive.
- Aucun BC break : ne change pas les signatures publiques existantes (GenericCrudController, CrudHandlerInterface, AbstractDoctrineCrudHandler, CrudHandlerFactory, routes, variables Twig).
- Tout le code du bundle reste sous Neox\NeoxCrudBundle\.
- Feature opt-in via configuration (neox_crud.yaml) et/ou par ressource, par défaut désactivée.
- Ajouter au moins 1 test (fonctionnel ou unitaire) + mettre à jour docs FR/EN + CHANGELOG.
- Ne pas introduire de dépendances front (pas de DataTables/jQuery). On utilise Turbo/Stimulus/LiveComponent (feature optionnelle : le bundle ne doit pas forcer l’installation si non activée).
- Ne jamais exécuter de commandes shell ni de fetch HTTP.
- Éviter les incohérences de configuration : **une seule source de vérité** pour l’affichage ET les capacités de requête.
- IMPORTANT : c’est un CRUD complet. La feature doit s’intégrer avec l’UI existante (index + new/edit/delete + custom actions), sans casser les routes ni le contrat Twig.

Fonctionnel attendu :
1) Source de vérité unique : `index_fields` (par ressource)
   - Le bundle lit `index_fields` (2 formats YAML supportés : liste d’objets ou map associative) et génère dynamiquement :
      - thead/th (label si dispo, sinon name)
      - td avec rendu : format date (format), boolean_icon (✓/✗), type=image + class, etc.
      - colonne conditionnelle avec `voters`: la colonne est affichée seulement si `isGranted(...)` est vrai (support roles comme 'ROLE_ADMIN' et attributs custom si déjà gérés).
   - **Nouveau (opt-in, sans BC break)** : `index_fields` peut aussi porter les capacités de requête, afin d’éviter un bloc `sort/search` séparé :
      - `sortable: true|false` (défaut: false)
      - `searchable: true|false` (défaut: false)
      - `filter: { type: <boolean|choice|date|...>, ... }` (optionnel)
      - `join: left|inner` (optionnel, défaut: left) pour les champs en dot-notation
      - (optionnel) `query_path: "owner.email"` si le nom UI doit différer du chemin Doctrine
   - Si `index_fields` est une liste simple de strings (BC), alors aucun tri/recherche/filtre n’est activé par défaut.

2) Tri / pagination / recherche / filtres server-side (sans configuration dupliquée)
   - pagination : page/perPage via Pagerfanta (server-side)
   - tri : click sur th **uniquement** si le champ `sortable: true`
   - recherche : LIKE **uniquement** sur les champs `searchable: true`
   - filtres : générés et appliqués **uniquement** sur les champs qui ont `filter: ...`
   - La whitelist de tri/recherche/filtres doit être dérivée de `index_fields` (pas d’input utilisateur brut).
   - Comportement stable si un champ est visible mais non triable/recherchable.
   - Ajouter une icon de tri pour afficher ordre croissant/descendant. 
   
3) JOINs Doctrine pour champs dot-notation (ex: `category.name`, `owner.email`)
   - JOIN automatique avec alias stables (ex: owner_company pour owner.company)
   - Join appliqué **uniquement** si le champ est utilisé par une opération (tri/recherche/filtre) ET qu’il est autorisé (`sortable/searchable/filter`).
   - Stratégie par défaut : LEFT JOIN (configurable par champ via `join`).

4) Sélection multiple + actions de masse
   - UI : checkboxes par ligne + select-all + compteur sélection
   - Les actions de masse proviennent de la config existante `bulk_actions` (déjà déclarée par ressource).
   - Exécution :
      - soit via LiveAction (si implémenté),
      - soit via routes existantes (custom action) avec CSRF lorsque POST,
      - mais dans tous les cas : revalider les droits côté serveur (voter) avant modification.
   - La sélection doit survivre aux refresh LiveComponent (state côté LiveComponent).

5) Intégration UI existante (config-first)
   - Structure header + content + fooder
   - Les barres de menu/boutons restent déclarées via :
      - `toolbar_buttons` (haut de page)
      - `actions` (par ligne)
      - `bulk_actions` (actions sur sélection)
   - Le rendu live doit réutiliser autant que possible les structures déjà normalisées par AbstractDoctrineCrudHandler :
      - `getToolbarButtons()`
      - `getBulkActions()`
      - `getRowActionsFor()`
      - `getIndexFields()` + `getIndexFieldOptions()`

6) Turbo : option par bouton/action (CRUD complet, index + navigation)
   - Ajouter une option YAML opt-in `turbo` sur `toolbar_buttons`, `actions`, `bulk_actions`.
   - Options supportées (toutes optionnelles) :
     - `turbo: false` OU `turbo: { enabled: false }` → génère `data-turbo="false"`
     - `turbo: { frame: "_top" | "crud_table" | "<frame_id>" }` → génère `data-turbo-frame="..."`
     - `turbo: { confirm: "..." }` (alias ou complément de `confirm`)
   - Le rendu Twig doit appliquer ces attributs Turbo aux liens/boutons/formulaires générés.
   - Objectif : permettre de choisir par action si elle doit :
     - naviguer via Turbo (GET),
     - cibler un Turbo Frame spécifique (ex: refresh table uniquement),
     - ou désactiver Turbo (download CSV, navigation externe, etc.).

7) Intégration Twig (BC-safe)
   - Respecter les variables contractuelles existantes : `resource`, `items`, `handler`, etc.
   - Fournir une branche/variante de template qui ne s’active que si la config opt-in est activée.
   - Ne pas casser les templates existants : l’index “classique” reste par défaut.

Livrables demandés :
- Liste des fichiers à créer/modifier (chemins exacts).
- Le code complet des classes importantes :
   - LiveComponent générique index table
   - helpers de normalisation de colonnes (déjà partiellement existant via getIndexFieldOptions, compléter si nécessaire)
   - query builder / join resolver sécurisé (whitelist issue de index_fields)
- Le Twig du composant/table Bootstrap + intégration Turbo (data attributes) sur :
   - liens de tri/pagination
   - toolbar_buttons
   - row actions
   - bulk_actions
- Un Stimulus controller minimal pour select-all + synchronisation selectedIds (si le bundle fournit JS, sinon documenter l’intégration).
- Une entrée de configuration (Configuration.php + docs) :
   - ex `neox_crud.live_table.enabled: true`
   - options globales (par ex: default_per_page, max_per_page)
- 1 test minimal :
   - ex: normalisation `index_fields` + options sortable/searchable + application des voters sur colonnes
   - ou test unitaire du join resolver (alias stables + left join) sur un champ dot-notation
   - + idéalement un test unitaire de normalisation `turbo` (enabled/frame) sur actions/boutons (sans dépendre du front)
- Mettre à jour docs FR/EN + CHANGELOG.

Contexte projet :
- Bundle: NeoxCrudBundle
- Symfony 7.3, PHP 8.3
- CRUD générique : GenericCrudController + CrudHandlerFactory + handlers par ressource
- Les colonnes d’index sont déjà déclarées dans la config YAML par ressource via `index_fields`.
- Les barres de menu sont déjà déclarées via `toolbar_buttons`.
- Les boutons d’action sont déjà déclarés via `actions`.
- Les actions de masse sont déjà déclarées via `bulk_actions`.

Commence par proposer une architecture (2-3 options) puis choisis la meilleure et implémente-la.
Utilise une approche progressive : d’abord pagination+tri, puis recherche/filtres, puis bulk actions, puis options Turbo par action.