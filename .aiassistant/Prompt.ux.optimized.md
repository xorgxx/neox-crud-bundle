# NeoxCrudBundle - Live Table Interactive (Symfony UX Integration)

## Vision d'ensemble
Ajouter une fonctionnalité **OPT-IN** qui remplace l'index (liste) actuel par une table interactive server-side basée sur Pagerfanta + Symfony UX LiveComponent, tout en préservant la rétrocompatibilité totale.

**Objectif** : Moderniser l'expérience utilisateur sans casser les implémentations existantes, avec une approche progressive et sécurisée.

---

## Architecture cible

### Composants principaux
```
LiveIndexTableComponent (Symfony UX)
├── QueryBuilderService (logic)
├── ColumnNormalizerService  
├── JoinResolverService
└── BulkActionProcessor
```

### Flux de données
```
Config YAML → ColumnNormalizer → LiveComponent → Twig → Stimulus → User
```

---

## Spécifications fonctionnelles

### Phase 1 - Core Features (MVP)
**1. Table interactive avec pagination**
- Pagination server-side via Pagerfanta
- Tri sur colonnes autorisées (`sortable: true`)
- Rendu Bootstrap 5 responsive

**2. Configuration unifiée**
- Extension de `index_fields` avec options de requête
- Source de vérité unique pour affichage ET capacités
- Activation opt-in via configuration globale

### Phase 2 - Search & Filters
**3. Recherche et filtres**
- Recherche LIKE sur champs `searchable: true`
- Filtres typés (boolean, choice, date)
- Whitelist dérivée de `index_fields`

### Phase 3 - Bulk Actions & UI Structure
**4. Structure UI complète (Bootstrap 5)**
- **Header** : Barre recherche (gauche) + boutons actions (droite)
- **Content** : Table interactive + pagination intégrée
- **Footer** : Actions de masse + informations résultats

**5. Actions de masse**
- Sélection multiple avec checkboxes
- Intégration avec `bulk_actions` existants
- Validation des droits côté serveur

### Phase 4 - Turbo Integration & Actions Management
**6. Navigation Turbo et gestion des actions**
- Options `turbo` par action/bouton (configuration YAML)
- Support frames et confirmations
- Désactivation sélective possible
- **3 stratégies d'exécution** selon le type d'action

#### **Stratégies d'exécution des actions**

**1. Actions LiveComponent (UX moderne)**
- **Recherche, tri, filtres, pagination** : LiveComponent uniquement
- **Sélection/désélection** : Stimulus controller sans refresh
- **Actions de masse simples** : LiveAction si possible

**2. Actions Turbo Frame (navigation partielle)**
- **Actions CRUD standards** : edit, delete, new
- **Actions custom avec retour** : export avec feedback
- **Targeting** : `data-turbo-frame="crud_table"` pour refresh table uniquement
- **Full page** : `data-turbo-frame="_top"` pour navigation complète

**3. Actions Refresh classique (compatibilité)**
- **Actions externes** : download, redirection
- **Actions complexes** : traitements longs avec redirect
- **Fallback** : si Turbo désactivé ou non supporté

### Phase 5 - Advanced Features & Notifications
**7. Performance & Accessibilité**
- Cache des métadonnées
- Support WCAG
- Theming configurable

**8. Système de notifications**
- **Délégation principale** à neoxWrapNotificatorBundle
- Support bundles externes (Scheb/2FA, Symfony Notifier) en fallback
- Fallback modal Bootstrap 5 si aucun bundle
- Notifications LiveComponent/Turbo intégrées

**9. Internationalisation (i18n)**
- Support multi-langues pour tous les messages UI
- Templates avec traductions intégrées
- Fichiers de traduction FR/EN fournis
- Pas d'i18n pour les formulaires (laissé au développeur)

**10. Export avancé**
- Export CSV/JSON avec filtres
- Support grosses volumétries (streaming)
- Colonnes sélectionnables
- Templates d'export personnalisables

**11. Accessibilité WCAG**
- ARIA labels complets
- Navigation clavier totale
- Support lecteurs d'écran
- Contrastes et focus optimisés

**12. Performance avancée**
- Lazy loading des colonnes
- Virtual scrolling optionnel
- Cache métadonnées intelligent
- Monitoring intégré

---

## Contraintes techniques

### Non-régression absolue
- ✅ **Aucun BC break** sur signatures publiques
- ✅ **Namespace** : `Neox\NeoxCrudBundle\` exclusivement
- ✅ **Templates** : variante opt-in, pas de remplacement
- ✅ **Routes** : inchangées

### Dépendances maîtrisées
- ✅ **Symfony 7.3 / PHP 8.3**
- ✅ **Pagerfanta** (déjà utilisé)
- ✅ **LiveComponent** (optionnel, si activé)
- ❌ **Aucune dépendance front** (pas DataTables/jQuery)

### Sécurité et performance
- ✅ **Whitelist stricte** sur les champs de requête
- ✅ **JOINs contrôlés** avec alias stables
- ✅ **Rate limiting** sur recherches/filtres
- ✅ **Validation CSRF** sur actions de masse

---

## Configuration YAML

### Configuration globale avec notifications
```yaml
# config/packages/neox_crud.yaml
neox_crud:
  live_table:
    enabled: true
    default_per_page: 25
    max_per_page: 100
    theme: bootstrap5
  
  # Configuration des notifications
  notifications:
    # Provider principal (délégation)
    provider: auto  # auto|neox_wrap|symfony_notifier|scheb_2fa|bootstrap
    
    # neoxWrapNotificatorBundle (prioritaire si installé)
    neox_wrap:
      enabled: true
      channel: 'crud'  # canal de notification dédié
      flash_integration: true  # intégration avec flash messages
      turbo_integration: true  # support Turbo frames
      template: 'neox_wrap/crud_notification.html.twig'

```


### Configuration par ressource avec notifications
```yaml
# src/Crud/Handler/UserHandler.yaml
# ========================================
# CONFIGURATION LIVE TABLE - DÉCOMMENTER POUR ACTIVER
# ========================================

# --- CONFIGURATION DE BASE ---
# Décommentez cette section pour activer la table interactive
neox_crud:
    # Champs de la table avec options avancées
    index_fields:
        # ID : toujours visible, triable
        - { name: 'id', label: 'ID', sortable: true }
        
        # Email : recherche et tri activés
        - { name: 'email', label: 'Email', sortable: true, searchable: true }
        
        # Nom : avec jointure sur la table profile
        - { name: 'profile.name', label: 'Nom complet', sortable: true, searchable: true, join: left }
        
        # Statut : filtre boolean + icône
        - { name: 'isActive', label: 'Actif', sortable: true, filter: { type: boolean }, boolean_icon: true }
        
        # Date de création : formatage + filtre date
        - { name: 'createdAt', label: 'Créé le', sortable: true, filter: { type: date }, format: 'd/m/Y H:i' }
        
        # Rôles : visible uniquement pour les admins
        - { name: 'roles', label: 'Rôles', voters: ['ROLE_ADMIN'] }
    
    # --- BOUTONS D'ACTION (toolbar) ---
    toolbar_buttons:
        # Bouton Nouveau : ouvre en modal Turbo
        - name: 'new'
          label: 'Nouveau utilisateur'
          icon: 'bi bi-plus-circle'
          class: 'btn-primary'
          turbo: { frame: 'crud_modal' }
          notification: { type: 'success', message: 'Utilisateur créé avec succès' }
        
        # Export CSV : refresh table avec confirmation
        - name: 'export_csv'
          label: 'Exporter CSV'
          icon: 'bi bi-file-earmark-csv'
          class: 'btn-outline-secondary'
          turbo: { frame: 'crud_table', confirm: 'Générer l\'export CSV ?' }
          notification: { type: 'info', message: 'Export CSV généré' }
        
        # Import : refresh complet (traitement long)
        # - name: 'import'
        #   label: 'Importer'
        #   icon: 'bi bi-upload'
        #   class: 'btn-outline-info'
        #   turbo: false  # Force refresh classique
        #   notification: { type: 'warning', message: 'Import en cours de traitement' }
    
    # --- ACTIONS PAR LIGNE ---
    actions:
        # Édition : modal Turbo
        - name: 'edit'
          label: 'Éditer'
          icon: 'bi bi-pencil'
          class: 'btn-outline-primary btn-sm'
          turbo: { frame: 'crud_modal' }
          notification: { type: 'success', message: 'Utilisateur mis à jour' }
        
        # Suppression : confirmation + refresh table
        - name: 'delete'
          label: 'Supprimer'
          icon: 'bi bi-trash'
          class: 'btn-outline-danger btn-sm'
          turbo: { frame: 'crud_table', confirm: 'Supprimer cet utilisateur ?' }
          notification: { type: 'success', message: 'Utilisateur supprimé' }
        
        # Action custom : envoi email
        # - name: 'send_email'
        #   label: 'Envoyer email'
        #   icon: 'bi bi-envelope'
        #   class: 'btn-outline-info btn-sm'
        #   turbo: { frame: 'crud_table', confirm: 'Envoyer un email à cet utilisateur ?' }
        #   notification: { type: 'info', message: 'Email envoyé avec succès' }
        
        # Action complexe : refresh complet
        # - name: 'complex_processing'
        #   label: 'Traiter'
        #   icon: 'bi bi-gear'
        #   class: 'btn-warning btn-sm'
        #   turbo: false  # Redirect vers page de traitement
        #   notification: { type: 'warning', message: 'Traitement en cours, redirection...' }
    
    # --- ACTIONS DE MASSE ---
    bulk_actions:
        # Suppression multiple : confirmation + refresh table
        - name: 'bulk_delete'
          label: 'Supprimer la sélection'
          icon: 'bi bi-trash3'
          turbo: { frame: 'crud_table', confirm: 'Supprimer les éléments sélectionnés ?' }
          notification: { type: 'success', message: '{count} utilisateurs supprimés' }
        
        # Export multiple : refresh table
        - name: 'bulk_export'
          label: 'Exporter la sélection'
          icon: 'bi bi-download'
          turbo: { frame: 'crud_table' }
          notification: { type: 'info', message: 'Export de {count} utilisateurs généré' }
        
        # Activation multiple : LiveAction (instantané)
        # - name: 'bulk_activate'
        #   label: 'Activer la sélection'
        #   icon: 'bi bi-check-circle'
        #   turbo: { frame: 'crud_table' }
        #   notification: { type: 'success', message: '{count} utilisateurs activés' }
        
        # Désactivation multiple
        # - name: 'bulk_deactivate'
        #   label: 'Désactiver la sélection'
        #   icon: 'bi bi-x-circle'
        #   turbo: { frame: 'crud_table', confirm: 'Désactiver les éléments sélectionnés ?' }
        #   notification: { type: 'warning', message: '{count} utilisateurs désactivés' }

# --- CONFIGURATION SIMPLE (BASIC) ---
# Pour une configuration minimale, décommentez seulement cette section :
# neox_crud:
#     index_fields:
#         - 'id'
#         - 'email'
#         - 'isActive'
#         - 'createdAt'
#     
#     toolbar_buttons:
#         - { name: 'new', label: 'Nouveau', turbo: { frame: 'crud_modal' }, notification: { type: 'success', message: 'Créé avec succès' } }
#     
#     actions:
#         - { name: 'edit', turbo: { frame: 'crud_modal' }, notification: { type: 'success', message: 'Mis à jour' } }
#         - { name: 'delete', turbo: { frame: 'crud_table', confirm: 'Supprimer ?' }, notification: { type: 'success', message: 'Supprimé' } }
#     
#     bulk_actions:
#         - { name: 'bulk_delete', turbo: { frame: 'crud_table', confirm: 'Supprimer la sélection ?' }, notification: { type: 'success', message: '{count} éléments supprimés' } }
```

---

## Spécifications techniques détaillées

### 1. ColumnNormalizerService
```php
final class ColumnNormalizerService
{
    public function normalizeIndexFields(array $indexFields): array
    {
        // Convertit les deux formats YAML supportés
        // Ajoute les valeurs par défaut (sortable: false, searchable: false)
        // Résout les voters pour affichage conditionnel
    }
    
    public function getQueryableFields(array $normalizedFields): array
    {
        // Retourne uniquement les champs avec capacités de requête
        // Whitelist pour sécurité
    }
}
```

### 2. JoinResolverService
```php
final class JoinResolverService
{
    public function resolveJoins(QueryBuilder $qb, array $fields): void
    {
        // JOINs automatiques avec alias stables
        // Uniquement si nécessaire (tri/recherche/filtre)
        // Support dot-notation (ex: owner.email)
    }
    
    private function generateAlias(string $path): string
    {
        // Alias uniques et stables
        // ex: owner_email pour owner.email
    }
}
```

### 7. LiveComponent mis à jour (avec debounce)
```php
#[LiveComponent]
final class LiveIndexTableComponent
{
    use ComponentAttributeBag;
    
    #[LiveProp]
    public string $resourceClass;
    
    #[LiveProp]
    public array $normalizedFields;
    
    #[LiveProp]
    public int $page = 1;
    
    #[LiveProp]
    public int $perPage = 25;
    
    #[LiveProp]
    public array $filters = [];
    
    #[LiveProp]
    public array $selectedIds = [];
    
    #[LiveProp]
    public ?string $searchQuery = null;
    
    public function getItems(): Pagerfanta
    {
        // Construction de la requête sécurisée avec recherche/filtres/tri
        return $this->queryBuilder->buildQuery(
            $this->resourceClass,
            $this->normalizedFields,
            $this->page,
            $this->perPage,
            $this->searchQuery,
            $this->filters
        );
    }
    
    // Actions LiveComponent
    #[LiveAction]
    public function search(string $query): void
    {
        $this->searchQuery = $query ?: null;
        $this->page = 1; // Reset pagination on search
    }
    
    #[LiveAction]
    public function sort(string $field): void
    {
        // Toggle sort direction or set new sort
        $this->sortField = $field;
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }
    
    #[LiveAction]
    public function filter(array $filters): void
    {
        $this->filters = $filters;
        $this->page = 1; // Reset pagination on filter
    }
    
    // Helper methods
    public function renderFieldValue(object $entity, array $field): string
    {
        // Logique de rendu des champs (date, boolean, image, etc.)
    }
    
    public function getToolbarButtons(): array
    {
        // Récupération depuis la config du handler
    }
    
    public function getRowActions(): array
    {
        // Actions par ligne depuis la config
    }
    
    public function getBulkActions(): array
    {
        // Actions de masse depuis la config
    }
    
    public function getActiveFilters(): array
    {
        // Retourne les filtres actifs pour affichage
    }
}
```

### 8. Template principal avec Turbo Frames
```twig
{# templates/neox_crud/index_live.html.twig #}
{% extends neox_crud_config.templates.index ?? '@NeoxCrud/index.html.twig' %}

{% block neox_crud_content %}
    {% if neox_crud_config.live_table.enabled %}
        {# Frame principal pour le refresh partiel #}
        <turbo-frame id="crud_table" data-turbo-action="advance">
            {{ component('neox_crud_live_index_table', {
                resourceClass: resource.class,
                normalizedFields: normalized_fields,
                items: items,
                currentPage: app.request.query.get('page', 1),
                perPage: app.request.query.get('perPage', 25)
            }) }}
        </turbo-frame>
        
        {# Frame pour les modals (edit/new) #}
        <turbo-frame id="crud_modal" data-turbo-action="advance"></turbo-frame>
        
        {# Frame pour les messages/notifications #}
        <turbo-frame id="crud_notifications" data-turbo-action="advance"></turbo-frame>
    {% else %}
        {{ parent() }}
    {% endif %}
{% endblock %}
```

### 9. Service de gestion des actions Turbo
```php
final class TurboActionResolver
{
    public function resolveAction(array $actionConfig): array
    {
        $turboConfig = $actionConfig['turbo'] ?? null;
        
        if ($turboConfig === false || $turboConfig['enabled'] === false) {
            // Refresh classique
            return [
                'method' => 'GET',
                'turbo' => false,
                'target' => '_self'
            ];
        }
        
        if (isset($turboConfig['frame'])) {
            // Turbo Frame
            return [
                'method' => 'GET',
                'turbo' => true,
                'frame' => $turboConfig['frame'],
                'confirm' => $turboConfig['confirm'] ?? null
            ];
        }
        
        // Turbo par défaut (full page)
        return [
            'method' => 'GET',
            'turbo' => true,
            'frame' => '_top'
        ];
    }
    
    public function generateAttributes(array $actionConfig): string
    {
        $resolved = $this->resolveAction($actionConfig);
        $attributes = [];
        
        if (!$resolved['turbo']) {
            $attributes[] = 'data-turbo="false"';
        } elseif ($resolved['frame'] !== '_top') {
            $attributes[] = sprintf('data-turbo-frame="%s"', $resolved['frame']);
        }
        
        if ($resolved['confirm']) {
            $attributes[] = sprintf('data-turbo-confirm="%s"', htmlspecialchars($resolved['confirm']));
        }
        
        return implode(' ', $attributes);
    }
}
```

### 11. Service de gestion des notifications (avec délégation neoxWrap)
```php
final class NotificationManager
{
    public function __construct(
        private ?Neox\WrapNotificatorBundle\Service\NotifierInterface $neoxWrapNotifier = null,
        private ?NotifierInterface $symfonyNotifier = null,
        private ?FlashBagInterface $flashBag = null,
        private array $config = []
    ) {
    }

    public function detectProvider(): string
    {
        if ($this->config['notifications']['provider'] !== 'auto') {
            return $this->config['notifications']['provider'];
        }

        // neoxWrapNotificatorBundle - PRIORITÉ ABSOLUE
        if ($this->neoxWrapNotifier && ($this->config['notifications']['neox_wrap']['enabled'] ?? true)) {
            return 'neox_wrap';
        }

        // Symfony Notifier - fallback 1
        if ($this->symfonyNotifier) {
            return 'symfony_notifier';
        }
        
        // Scheb/2FA - fallback 2
        if (class_exists('\Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface')) {
            return 'scheb_2fa';
        }
        
        return 'bootstrap'; // Fallback par défaut
    }

    public function success(string $message, array $context = []): void
    {
        $this->notify('crud_success', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->notify('crud_error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->notify('crud_warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->notify('crud_info', $message, $context);
    }

    private function notify(string $type, string $message, array $context): void
    {
        $provider = $this->detectProvider();

        switch ($provider) {
            case 'neox_wrap':
                $this->notifyWithNeoxWrap($type, $message, $context);
                break;
                
            case 'symfony_notifier':
                $this->notifyWithSymfony($type, $message, $context);
                break;
                
            case 'scheb_2fa':
                $this->notifyWithScheb($type, $message, $context);
                break;
                
            case 'bootstrap':
            default:
                $this->notifyWithBootstrap($type, $message, $context);
                break;
        }
    }

    private function notifyWithNeoxWrap(string $type, string $message, array $context): void
    {
        if (!$this->neoxWrapNotifier) {
            return;
        }

        $channel = $this->config['notifications']['neox_wrap']['channel'] ?? 'crud';
        
        // Création de la notification via neoxWrap
        $notification = $this->neoxWrapNotifier->createNotification(
            type: $type,
            message: $message,
            context: array_merge($context, [
                'source' => 'neox_crud',
                'timestamp' => time(),
                'user_id' => $this->getCurrentUserId()
            ])
        );

        // Configuration spécifique CRUD
        $typeConfig = $this->config['notifications']['neox_wrap']['types'][$type] ?? [];
        if (isset($typeConfig['sound'])) {
            $notification->setSound($typeConfig['sound']);
        }
        if (isset($typeConfig['auto_hide'])) {
            $notification->setAutoHide($typeConfig['auto_hide']);
        }

        // Envoi via neoxWrap
        $this->neoxWrapNotifier->send($channel, $notification);

        // Flash integration si activé
        if ($this->flashBag && ($this->config['notifications']['neox_wrap']['flash_integration'] ?? true)) {
            $this->flashBag->add($this->mapCrudTypeToFlash($type), $message);
        }
    }

    private function notifyWithSymfony(string $type, string $message, array $context): void
    {
        if (!$this->symfonyNotifier) {
            return;
        }

        $channel = $this->config['notifications']['symfony_notifier']['channel'] ?? 'browser';
        
        $notification = new Notification($message, ['browser'])
            ->content($message)
            ->importance($this->getSymfonyImportance($type));

        $this->symfonyNotifier->send($notification);

        // Flash message en complément
        if ($this->flashBag && ($this->config['notifications']['symfony_notifier']['flash'] ?? true)) {
            $this->flashBag->add($this->mapCrudTypeToFlash($type), $message);
        }
    }

    private function notifyWithScheb(string $type, string $message, array $context): void
    {
        // Intégration avec Scheb/2FA si disponible
        if ($this->flashBag && ($this->config['notifications']['scheb_2fa']['flash_integration'] ?? true)) {
            $this->flashBag->add($this->mapCrudTypeToFlash($type), $message);
        }

        // Stockage pour affichage dans template 2FA
        $this->storeNotification($type, $message, $context);
    }

    private function notifyWithBootstrap(string $type, string $message, array $context): void
    {
        // Stockage pour affichage via Bootstrap modal/toast
        $this->storeNotification($type, $message, $context);
    }

    private function storeNotification(string $type, string $message, array $context): void
    {
        // Stockage en session pour affichage au prochain rendu
        $notifications = $_SESSION['_neox_crud_notifications'] ?? [];
        $notifications[] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'timestamp' => time(),
            'id' => uniqid('notif_', true)
        ];
        $_SESSION['_neox_crud_notifications'] = $notifications;
    }

    private function mapCrudTypeToFlash(string $type): string
    {
        return match ($type) {
            'crud_success' => 'success',
            'crud_error' => 'error',
            'crud_warning' => 'warning',
            'crud_info' => 'info',
            default => 'info',
        };
    }

    private function getSymfonyImportance(string $type): string
    {
        return match ($type) {
            'crud_success' => Importance::high,
            'crud_error' => Importance::urgent,
            'crud_warning' => Importance::medium,
            'crud_info' => Importance::low,
            default => Importance::normal,
        };
    }

    public function getStoredNotifications(): array
    {
        $notifications = $_SESSION['_neox_crud_notifications'] ?? [];
        unset($_SESSION['_neox_crud_notifications']); // Clear after reading
        return $notifications;
    }

    private function getCurrentUserId(): ?int
    {
        // Récupération de l'ID utilisateur courant si disponible
        // Implementation selon votre système d'authentification
        return null;
    }
}
```

### 12. Templates Bootstrap 5 pour notifications
```twig
{# templates/neox_crud/notifications/bootstrap_toasts.html.twig #}
{% if notifications is not empty %}
<div class="toast-container position-fixed p-3" style="z-index: 1055; top: 20px; right: 20px;">
    {% for notification in notifications %}
        {% set config = neox_crud_config.notifications.types[notification.type] %}
        <div class="toast align-items-center text-white bg-{{ config.class }} border-0" 
             role="alert" 
             aria-live="assertive" 
             aria-atomic="true"
             id="{{ notification.id }}"
             data-bs-delay="{{ config.auto_hide }}">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="{{ config.icon }} me-2"></i>
                    {{ notification.message }}
                </div>
                {% if config.auto_hide > 0 %}
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                {% endif %}
            </div>
        </div>
    {% endfor %}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    {% for notification in notifications %}
        {% set config = neox_crud_config.notifications.types[notification.type] %}
        {% if config.auto_hide > 0 %}
        var toastElement = document.getElementById('{{ notification.id }}');
        if (toastElement) {
            var toast = new bootstrap.Toast(toastElement);
            toast.show();
        }
        {% endif %}
    {% endfor %}
});
</script>
{% endif %}
```

```twig
{# templates/neox_crud/notifications/bootstrap_modal.html.twig #}
{% if notifications is not empty %}
<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-bell me-2"></i>
                    Notifications
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {% for notification in notifications %}
                    {% set config = neox_crud_config.notifications.types[notification.type] %}
                    <div class="alert alert-{{ config.class }} d-flex align-items-center" role="alert">
                        <i class="{{ config.icon }} me-2"></i>
                        <div class="flex-grow-1">
                            {{ notification.message }}
                        </div>
                    </div>
                {% endfor %}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    {% if notifications|length > 0 %}
    var modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();
    {% endif %}
});
</script>
{% endif %}
```

### 13. Template principal avec notifications
```twig
{# templates/neox_crud/index_live.html.twig #}
{% extends neox_crud_config.templates.index ?? '@NeoxCrud/index.html.twig' %}

{% block neox_crud_content %}
    {% if neox_crud_config.live_table.enabled %}
        {# Frame principal pour le refresh partiel #}
        <turbo-frame id="crud_table" data-turbo-action="advance">
            {{ component('neox_crud_live_index_table', {
                resourceClass: resource.class,
                normalizedFields: normalized_fields,
                items: items,
                currentPage: app.request.query.get('page', 1),
                perPage: app.request.query.get('perPage', 25)
            }) }}
        </turbo-frame>
        
        {# Frame pour les modals (edit/new) #}
        <turbo-frame id="crud_modal" data-turbo-action="advance"></turbo-frame>
        
        {# Frame pour les notifications #}
        <turbo-frame id="crud_notifications" data-turbo-action="advance">
            {% set notifications = neox_crud_notification_manager.getStoredNotifications() %}
            
            {% if neox_crud_config.notifications.bootstrap.modal %}
                {{ include('neox_crud/notifications/bootstrap_modal.html.twig') }}
            {% endif %}
            
            {% if neox_crud_config.notifications.bootstrap.toast %}
                {{ include('neox_crud/notifications/bootstrap_toasts.html.twig') }}
            {% endif %}
        </turbo-frame>
    {% else %}
        {{ parent() }}
    {% endif %}
{% endblock %}
```

### 15. Fichiers de traduction (i18n)
```yaml
# translations/neox_crud.fr.yaml
neox_crud:
  table:
    search_placeholder: "Rechercher..."
    search_button: "Rechercher"
    results_count: "%count% résultat|%count% résultats"
    results_count_filtered: "%count% résultat pour \"%query%\"|%count% résultats pour \"%query%\""
    no_results: "Aucun résultat trouvé"
    loading: "Chargement..."
    select_all: "Tout sélectionner"
    deselect_all: "Tout désélectionner"
    selected_count: "%count% sélectionné|%count% sélectionnés"
    bulk_actions: "Actions de masse"
    filters_active: "Filtres actifs : %filters%"
    per_page: "Par page"
    previous: "Précédent"
    next: "Suivant"
  
  actions:
    new: "Nouveau"
    edit: "Éditer"
    delete: "Supprimer"
    show: "Voir"
    export: "Exporter"
    import: "Importer"
    bulk_delete: "Supprimer la sélection"
    bulk_export: "Exporter la sélection"
    bulk_activate: "Activer la sélection"
    bulk_deactivate: "Désactiver la sélection"
  
  confirmations:
    delete: "Supprimer cet élément ?"
    bulk_delete: "Supprimer les %count% éléments sélectionnés ?"
    export: "Générer l'export ?"
    bulk_export: "Exporter les %count% éléments sélectionnés ?"
  
  notifications:
    success:
      created: "Élément créé avec succès"
      updated: "Élément mis à jour avec succès"
      deleted: "Élément supprimé avec succès"
      bulk_deleted: "%count% éléments supprimés avec succès"
      exported: "Export généré avec succès"
      bulk_exported: "Export de %count% éléments généré avec succès"
      bulk_activated: "%count% éléments activés avec succès"
      bulk_deactivated: "%count% éléments désactivés avec succès"
    
    error:
      generic: "Une erreur est survenue"
      delete_failed: "Erreur lors de la suppression"
      bulk_delete_failed: "Erreur lors de la suppression en masse"
      export_failed: "Erreur lors de la génération de l'export"
      permission_denied: "Permission refusée"
    
    warning:
      processing: "Traitement en cours..."
      long_processing: "Le traitement peut prendre du temps"
      no_selection: "Aucun élément sélectionné"
    
    info:
      loading: "Chargement des données..."
      processing_complete: "Traitement terminé"
      selection_cleared: "Sélection effacée"

  sort:
    ascending: "Tri croissant"
    descending: "Tri décroissant"
    sort_by: "Trier par %field%"

  filters:
    clear_all: "Effacer tous les filtres"
    active_filters: "Filtres actifs"
    boolean_true: "Oui"
    boolean_false: "Non"
    date_range: "Plage de dates"
    select_option: "Sélectionner une option"
```

```yaml
# translations/neox_crud.en.yaml
neox_crud:
  table:
    search_placeholder: "Search..."
    search_button: "Search"
    results_count: "%count% result|%count% results"
    results_count_filtered: "%count% result for \"%query%\"|%count% results for \"%query%\""
    no_results: "No results found"
    loading: "Loading..."
    select_all: "Select all"
    deselect_all: "Deselect all"
    selected_count: "%count% selected"
    bulk_actions: "Bulk actions"
    filters_active: "Active filters: %filters%"
    per_page: "Per page"
    previous: "Previous"
    next: "Next"
  
  actions:
    new: "New"
    edit: "Edit"
    delete: "Delete"
    show: "View"
    export: "Export"
    import: "Import"
    bulk_delete: "Delete selection"
    bulk_export: "Export selection"
    bulk_activate: "Activate selection"
    bulk_deactivate: "Deactivate selection"
  
  confirmations:
    delete: "Delete this item?"
    bulk_delete: "Delete the %count% selected items?"
    export: "Generate export?"
    bulk_export: "Export the %count% selected items?"
  
  notifications:
    success:
      created: "Item created successfully"
      updated: "Item updated successfully"
      deleted: "Item deleted successfully"
      bulk_deleted: "%count% items deleted successfully"
      exported: "Export generated successfully"
      bulk_exported: "Export of %count% items generated successfully"
      bulk_activated: "%count% items activated successfully"
      bulk_deactivated: "%count% items deactivated successfully"
    
    error:
      generic: "An error occurred"
      delete_failed: "Error during deletion"
      bulk_delete_failed: "Error during bulk deletion"
      export_failed: "Error during export generation"
      permission_denied: "Permission denied"
    
    warning:
      processing: "Processing..."
      long_processing: "Processing may take some time"
      no_selection: "No items selected"
    
    info:
      loading: "Loading data..."
      processing_complete: "Processing complete"
      selection_cleared: "Selection cleared"

  sort:
    ascending: "Sort ascending"
    descending: "Sort descending"
    sort_by: "Sort by %field%"

  filters:
    clear_all: "Clear all filters"
    active_filters: "Active filters"
    boolean_true: "Yes"
    boolean_false: "No"
    date_range: "Date range"
    select_option: "Select an option"
```

### 16. Template avec i18n intégré
```twig
{# templates/neox_crud/components/live_index_table.html.twig #}
<div {{ attributes }}
     data-controller="neox-crud--live-table"
     data-neox-crud--live-table-debounce-delay-value="300">
    
    <!-- Header : recherche + actions -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" 
                       class="form-control" 
                       placeholder="{{ 'neox_crud.table.search_placeholder'|trans }}"
                       data-neox-crud--live-table-target="searchInput"
                       data-action="input->neox-crud--live-table#search"
                       value="{{ this.searchQuery }}">
                <button class="btn btn-outline-secondary" type="submit">
                    <span data-neox-crud--live-table-target="searchIcon">
                        <i class="bi bi-search"></i>
                    </span>
                </button>
            </div>
        </div>
        <div class="col-md-6 text-end">
            {% for button in this.toolbarButtons %}
                <a href="{{ button.url }}" 
                   class="btn {{ button.class|default('btn-primary') }}"
                   {% if button.turbo is defined %}{{ button.turbo.attributes|raw }}{% endif %}>
                    {% if button.icon %}<i class="{{ button.icon }}"></i> {% endif %}
                    {{ button.label|trans }}
                </a>
            {% endfor %}
        </div>
    </div>

    <!-- Content : table + pagination -->
    <div class="card" data-neox-crud--live-table-target="tableContainer">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       data-action="click->neox-crud--bulk-selection#toggleAll"
                                       title="{{ 'neox_crud.table.select_all'|trans }}">
                            </th>
                            {% for field in this.normalizedFields %}
                                <th {% if field.sortable %} 
                                        class="sortable cursor-pointer" 
                                        data-sort-field="{{ field.name }}"
                                        data-neox-crud--live-table-target="sortIcons"
                                        data-action="click->neox-crud--live-table#sort"
                                        title="{{ 'neox_crud.sort.sort_by'|trans({'%field%': field.label}) }}"
                                    {% endif %}>
                                    {{ field.label|trans }}
                                    {% if field.sortable %}
                                        <i class="bi bi-arrow-down-up ms-1" 
                                           data-neox-crud--live-table-target="sortIcons"
                                           title="{{ 'neox_crud.sort.ascending'|trans }}"></i>
                                    {% endif %}
                                </th>
                            {% endfor %}
                            <th>{{ 'neox_crud.actions.edit'|trans }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% if this.items.currentPageResults|length > 0 %}
                            {% for item in this.items.currentPageResults %}
                                <tr>
                                    <td>
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               value="{{ item.id }}"
                                               data-neox-crud--bulk-selection-target="item"
                                               data-action="click->neox-crud--bulk-selection#toggleItem">
                                    </td>
                                    {% for field in this.normalizedFields %}
                                        <td>
                                            {{ this.renderFieldValue(item, field) }}
                                        </td>
                                    {% endfor %}
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            {% for action in this.rowActions %}
                                                <a href="{{ action.url }}" 
                                                   class="btn btn-sm {{ action.class|default('btn-outline-primary') }}"
                                                   {% if action.turbo is defined %}{{ action.turbo.attributes|raw }}{% endif %}
                                                   title="{{ action.label|trans }}">
                                                    {% if action.icon %}<i class="{{ action.icon }}"></i>{% endif %}
                                                </a>
                                            {% endfor %}
                                        </div>
                                    </td>
                                </tr>
                            {% endfor %}
                        {% else %}
                            <tr>
                                <td colspan="{{ this.normalizedFields|length + 2 }}" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-1 d-block mb-2"></i>
                                        {{ 'neox_crud.table.no_results'|trans }}
                                    </div>
                                </td>
                            </tr>
                        {% endif %}
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination intégrée -->
        {% if this.items.haveToPaginate %}
            <div class="card-footer">
                {{ pagerfanta(this.items, 'twitter_bootstrap5') }}
            </div>
        {% endif %}
    </div>

    <!-- Footer : actions de masse + informations -->
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="d-flex align-items-center">
                <span class="me-3">
                    <strong>{{ 'neox_crud.table.results_count'|trans({'%count%': this.items.nbResults}) }}</strong>
                    {% if this.searchQuery %}
                        {{ 'neox_crud.table.results_count_filtered'|trans({
                            '%count%': this.items.nbResults, 
                            '%query%': this.searchQuery
                        }) }}
                    {% endif %}
                </span>
                {% if this.activeFilters|length > 0 %}
                    <small class="text-muted">
                        {{ 'neox_crud.table.filters_active'|trans({'%filters%': this.activeFilters|join(', ')}) }}
                    </small>
                {% endif %}
            </div>
        </div>
        <div class="col-md-6 text-end">
            {% if this.bulkActions|length > 0 %}
                <div class="dropdown" 
                     data-neox-crud--bulk-selection-target="dropdown" 
                     style="display: none;">
                    <button class="btn btn-primary dropdown-toggle" 
                            type="button" 
                            data-bs-toggle="dropdown">
                        <span data-neox-crud--bulk-selection-target="count">0</span> 
                        {{ 'neox_crud.table.selected_count'|trans }}
                    </button>
                    <ul class="dropdown-menu">
                        {% for action in this.bulkActions %}
                            <li>
                                <a class="dropdown-item" 
                                   href="{{ action.url }}" 
                                   {% if action.turbo is defined %}{{ action.turbo.attributes|raw }}{% endif %}>
                                    {% if action.icon %}<i class="{{ action.icon }}"></i> {% endif %}
                                    {{ action.label|trans }}
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </div>
            {% endif %}
        </div>
    </div>
</div>
```

### 18. Service d'export avancé
```php
final class ExportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private NotificationManager $notificationManager
    ) {}

    public function exportCsv(array $items, array $fields, array $filters = []): string
    {
        $csv = fopen('php://temp', 'r+');
        
        // En-tête avec traductions
        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $field['label'] ?? $field['name'];
        }
        fputcsv($csv, $headers);
        
        // Données
        foreach ($items as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $this->formatFieldValue($item, $field);
            }
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }

    public function exportExcel(array $items, array $fields, array $filters = []): BinaryFileResponse
    {
        // Utilisation de phpspreadsheet si disponible
        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return $this->exportExcelWithSpreadsheet($items, $fields, $filters);
        }
        
        // Fallback CSV
        $content = $this->exportCsv($items, $fields, $filters);
        return new BinaryFileResponse(
            new MemoryStream($content),
            200,
            ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="export.csv"']
        );
    }

    public function exportJson(array $items, array $fields, array $filters = []): string
    {
        $data = [];
        foreach ($items as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field['name']] = $this->formatFieldValue($item, $field);
            }
            $data[] = $row;
        }
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function streamLargeExport(QueryBuilder $qb, array $fields, string $format = 'csv'): Response
    {
        return new StreamedResponse(function() use ($qb, $fields, $format) {
            $output = fopen('php://output', 'w');
            
            if ($format === 'csv') {
                $this->streamCsv($output, $qb, $fields);
            } elseif ($format === 'json') {
                $this->streamJson($output, $qb, $fields);
            }
            
            fclose($output);
        }, 200, [
            'Content-Type' => $this->getContentType($format),
            'Content-Disposition' => sprintf('attachment; filename="export.%s"', $format)
        ]);
    }

    private function streamCsv($output, QueryBuilder $qb, array $fields): void
    {
        // En-tête
        $headers = array_map(fn($field) => $field['label'] ?? $field['name'], $fields);
        fputcsv($output, $headers);
        
        // Streaming des données
        $iterator = $qb->getQuery()->toIterable();
        foreach ($iterator as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $this->formatFieldValue($item, $field);
            }
            fputcsv($output, $row);
        }
    }

    private function formatFieldValue(object $entity, array $field): string
    {
        $value = $this->getPropertyValue($entity, $field['name']);
        
        return match ($field['type'] ?? 'string') {
            'boolean' => $value ? 'Oui' : 'Non',
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) $value,
            'integer' => (string) $value,
            'float' => number_format($value, 2, ',', ' '),
            default => (string) $value,
        };
    }
}
```

### 19. Templates accessibles WCAG
```twig
{# templates/neox_crud/components/live_index_table_accessible.html.twig #}
<div {{ attributes }}
     data-controller="neox-crud--live-table"
     data-neox-crud--live-table-debounce-delay-value="300"
     role="region"
     aria-label="{{ 'neox_crud.table.data_table'|trans }}">
    
    <!-- Header : recherche + actions -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="crud-search" class="form-label visually-hidden">
                {{ 'neox_crud.table.search_placeholder'|trans }}
            </label>
            <div class="input-group">
                <input type="text" 
                       id="crud-search"
                       class="form-control" 
                       placeholder="{{ 'neox_crud.table.search_placeholder'|trans }}"
                       data-neox-crud--live-table-target="searchInput"
                       data-action="input->neox-crud--live-table#search"
                       value="{{ this.searchQuery }}"
                       aria-describedby="search-help"
                       autocomplete="off">
                <button class="btn btn-outline-secondary" 
                        type="submit"
                        aria-label="{{ 'neox_crud.table.search_button'|trans }}"
                        data-neox-crud--live-table-target="searchButton">
                    <span class="sr-only">{{ 'neox_crud.table.search_button'|trans }}</span>
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
                <div id="search-help" class="sr-only">
                    {{ 'neox_crud.table.search_help'|trans }}
                </div>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group" aria-label="{{ 'neox_crud.table.toolbar_actions'|trans }}">
                {% for button in this.toolbarButtons %}
                    <a href="{{ button.url }}" 
                       class="btn {{ button.class|default('btn-primary') }}"
                       {% if button.turbo is defined %}{{ button.turbo.attributes|raw }}{% endif %}
                       role="button"
                       aria-label="{{ button.label|trans }}">
                        {% if button.icon %}<i class="{{ button.icon }}" aria-hidden="true"></i> {% endif %}
                        <span class="d-none d-md-inline">{{ button.label|trans }}</span>
                    </a>
                {% endfor %}
            </div>
        </div>
    </div>

    <!-- Content : table + pagination -->
    <div class="card" data-neox-crud--live-table-target="tableContainer">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0"
                       role="table"
                       aria-label="{{ 'neox_crud.table.data_table'|trans }}"
                       aria-rowcount="{{ this.items.nbResults }}">
                    <thead class="table-light">
                        <tr role="row">
                            <th scope="col" width="40" role="columnheader">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           id="select-all"
                                           class="form-check-input" 
                                           data-action="click->neox-crud--bulk-selection#toggleAll"
                                           aria-label="{{ 'neox_crud.table.select_all'|trans }}"
                                           title="{{ 'neox_crud.table.select_all'|trans }}">
                                    <label class="form-check-label visually-hidden" for="select-all">
                                        {{ 'neox_crud.table.select_all'|trans }}
                                    </label>
                                </div>
                            </th>
                            {% for field in this.normalizedFields %}
                                <th scope="col" 
                                    {% if field.sortable %} 
                                        class="sortable cursor-pointer" 
                                        data-sort-field="{{ field.name }}"
                                        data-neox-crud--live-table-target="sortIcons"
                                        data-action="click->neox-crud--live-table#sort"
                                        role="columnheader"
                                        aria-sort="{% if field.sorted %}{{ field.direction }}{% else %}none{% endif %}"
                                        tabindex="0"
                                    {% endif %}>
                                    <div class="d-flex align-items-center">
                                        {{ field.label|trans }}
                                        {% if field.sortable %}
                                            <button class="btn btn-sm btn-link p-0 ms-1" 
                                                    type="button"
                                                    aria-label="{{ 'neox_crud.sort.sort_by'|trans({'%field%': field.label}) }}"
                                                    title="{{ 'neox_crud.sort.sort_by'|trans({'%field%': field.label}) }}">
                                                <i class="bi bi-arrow-down-up" 
                                                   data-neox-crud--live-table-target="sortIcons"
                                                   aria-hidden="true"></i>
                                            </button>
                                        {% endif %}
                                    </div>
                                </th>
                            {% endfor %}
                            <th scope="col" role="columnheader">
                                {{ 'neox_crud.actions.edit'|trans }}
                            </th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        {% if this.items.currentPageResults|length > 0 %}
                            {% for item in this.items.currentPageResults %}
                                <tr role="row" aria-rowindex="{{ loop.index }}">
                                    <td role="gridcell">
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   id="select-{{ item.id }}"
                                                   class="form-check-input" 
                                                   value="{{ item.id }}"
                                                   data-neox-crud--bulk-selection-target="item"
                                                   data-action="click->neox-crud--bulk-selection#toggleItem"
                                                   aria-label="{{ 'neox_crud.table.select_item'|trans({'%id%': item.id}) }}">
                                            <label class="form-check-label visually-hidden" for="select-{{ item.id }}">
                                                {{ 'neox_crud.table.select_item'|trans({'%id%': item.id}) }}
                                            </label>
                                        </div>
                                    </td>
                                    {% for field in this.normalizedFields %}
                                        <td role="gridcell" data-label="{{ field.label|trans }}">
                                            {{ this.renderFieldValue(item, field) }}
                                        </td>
                                    {% endfor %}
                                    <td role="gridcell">
                                        <div class="btn-group btn-group-sm" 
                                             role="group" 
                                             aria-label="{{ 'neox_crud.table.row_actions'|trans }}">
                                            {% for action in this.rowActions %}
                                                <a href="{{ action.url }}" 
                                                   class="btn btn-sm {{ action.class|default('btn-outline-primary') }}"
                                                   {% if action.turbo is defined %}{{ action.turbo.attributes|raw }}{% endif %}
                                                   role="button"
                                                   aria-label="{{ action.label|trans }}"
                                                   title="{{ action.label|trans }}">
                                                    {% if action.icon %}<i class="{{ action.icon }}" aria-hidden="true"></i>{% endif %}
                                                    <span class="sr-only">{{ action.label|trans }}</span>
                                                </a>
                                            {% endfor %}
                                        </div>
                                    </td>
                                </tr>
                            {% endfor %}
                        {% else %}
                            <tr role="row">
                                <td colspan="{{ this.normalizedFields|length + 2 }}" 
                                    class="text-center py-4" 
                                    role="gridcell">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-1 d-block mb-2" aria-hidden="true"></i>
                                        <p>{{ 'neox_crud.table.no_results'|trans }}</p>
                                    </div>
                                </td>
                            </tr>
                        {% endif %}
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination intégrée -->
        {% if this.items.haveToPaginate %}
            <div class="card-footer" role="navigation" aria-label="{{ 'neox_crud.table.pagination'|trans }}">
                {{ pagerfanta(this.items, 'twitter_bootstrap5') }}
            </div>
        {% endif %}
    </div>

    <!-- Footer : actions de masse + informations -->
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="d-flex align-items-center">
                <div role="status" aria-live="polite" aria-atomic="true">
                    <strong>{{ 'neox_crud.table.results_count'|trans({'%count%': this.items.nbResults}) }}</strong>
                    {% if this.searchQuery %}
                        {{ 'neox_crud.table.results_count_filtered'|trans({
                            '%count%': this.items.nbResults, 
                            '%query%': this.searchQuery
                        }) }}
                    {% endif %}
                </div>
                {% if this.activeFilters|length > 0 %}
                    <div class="ms-3">
                        <small class="text-muted">
                            {{ 'neox_crud.table.filters_active'|trans({'%filters%': this.activeFilters|join(', ')}) }}
                        </small>
                    </div>
                {% endif %}
            </div>
        </div>
        <div class="col-md-6 text-end">
            {% if this.bulkActions|length > 0 %}
                <div class="dropdown" 
                     data-neox-crud--bulk-selection-target="dropdown" 
                     style="display: none;"
                     role="region"
                     aria-label="{{ 'neox_crud.table.bulk_actions'|trans }}">
                    <button class="btn btn-primary dropdown-toggle" 
                            type="button" 
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <span data-neox-crud--bulk-selection-target="count">0</span> 
                        {{ 'neox_crud.table.selected_count'|trans }}
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        {% for action in this.bulkActions %}
                            <li role="none">
                                <a class="dropdown-item" 
                                   href="{{ action.url }}" 
                                   {% if action.turbo is defined %}{{ action.turbo.attributes|raw }}{% endif %}
                                   role="menuitem">
                                    {% if action.icon %}<i class="{{ action.icon }}" aria-hidden="true"></i> {% endif %}
                                    {{ action.label|trans }}
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </div>
            {% endif %}
        </div>
    </div>
</div>
```

### 20. Service de performance avancée
```php
final class PerformanceOptimizer
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function enableLazyLoading(LiveComponent $component): void
    {
        // Lazy loading des colonnes optionnelles
        $component->setLazyFields($this->getLazyFields());
    }

    public function enableVirtualScrolling(array $options = []): void
    {
        // Virtual scrolling pour gros datasets
        $this->virtualScrollingEnabled = true;
        $this->virtualScrollingOptions = array_merge([
            'item_height' => 50,
            'buffer_size' => 10,
            'threshold' => 1000
        ], $options);
    }

    public function optimizeQuery(QueryBuilder $qb, array $fields): QueryBuilder
    {
        // Optimisation des requêtes
        $qb->select('partial e.' . implode(', partial e.', $this->getEntityFields($fields)))
           ->setMaxResults($this->getOptimalPageSize())
           ->setCacheable(true);
        
        return $qb;
    }

    public function cacheMetadata(string $key, array $data, int $ttl = 3600): void
    {
        $this->cache->get($key, function() use ($data) {
            return $data;
        }, $ttl);
    }

    public function getPerformanceMetrics(): array
    {
        return [
            'query_time' => $this->queryTime,
            'memory_usage' => memory_get_usage(true),
            'cache_hits' => $this->cacheHits,
            'render_time' => $this->renderTime
        ];
    }

    private function getOptimalPageSize(): int
    {
        // Adaptation dynamique selon la performance
        $memory = memory_get_usage(true);
        $maxMemory = 128 * 1024 * 1024; // 128MB
        
        if ($memory > $maxMemory * 0.8) {
            return 25; // Réduction si mémoire haute
        }
        
        return 50; // Valeur par défaut
    }
}
```

### 5. Stimulus controller (avec debounce et loaders)
```javascript
// assets/controllers/neox_crud/bulk_selection_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'count', 'dropdown'];
    static values = {
        selectedIds: Array,
        debounceDelay: { type: Number, default: 300 }
    };

    connect() {
        this.selectedIdsValue = [];
        this.updateUI();
    }

    // Gestion de la sélection
    toggleAll(event) {
        const checkboxes = this.itemTargets;
        const checked = event.target.checked;
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            this.updateSelection(checkbox.value, checked);
        });
        
        this.updateUI();
    }

    toggleItem(event) {
        const checkbox = event.target;
        this.updateSelection(checkbox.value, checkbox.checked);
        this.updateUI();
    }

    updateSelection(id, selected) {
        if (selected) {
            if (!this.selectedIdsValue.includes(id)) {
                this.selectedIdsValue = [...this.selectedIdsValue, id];
            }
        } else {
            this.selectedIdsValue = this.selectedIdsValue.filter(item => item !== id);
        }
    }

    updateUI() {
        const count = this.selectedIdsValue.length;
        this.countTarget.textContent = count;
        this.dropdownTarget.style.display = count > 0 ? 'block' : 'none';
    }
}

// assets/controllers/neox_crud/live_table_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['searchInput', 'searchIcon', 'tableContainer', 'sortIcons'];
    static values = {
        debounceDelay: { type: Number, default: 300 },
        loading: { type: Boolean, default: false }
    };

    connect() {
        this.debounceTimer = null;
    }

    // Recherche avec debounce
    search(event) {
        const query = event.target.value;
        
        // Clear previous timer
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        
        // Show loader immediately
        this.showSearchLoader();
        
        // Debounce search
        this.debounceTimer = setTimeout(() => {
            this.performSearch(query);
        }, this.debounceDelayValue);
    }

    performSearch(query) {
        // Trigger LiveComponent update
        this.dispatch('search', { detail: { query } });
    }

    // Tri avec loader
    sort(event) {
        const field = event.currentTarget.dataset.sortField;
        
        // Show loader on sort icon
        this.showSortLoader(event.currentTarget);
        
        // Trigger sort
        this.dispatch('sort', { detail: { field } });
    }

    // Filtres avec loader
    filter(event) {
        this.showTableLoader();
        this.dispatch('filter', { detail: event.target.value });
    }

    // Loader management
    showSearchLoader() {
        if (this.hasSearchIconTarget) {
            this.searchIconTarget.innerHTML = `
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            `;
        }
    }

    hideSearchLoader() {
        if (this.hasSearchIconTarget) {
            this.searchIconTarget.innerHTML = '<i class="bi bi-search"></i>';
        }
    }

    showSortLoader(thElement) {
        const icon = thElement.querySelector('i');
        if (icon) {
            icon.className = 'spinner-border spinner-border-sm';
        }
    }

    hideSortLoaders() {
        this.sortIconsTargets.forEach(icon => {
            icon.className = 'bi bi-arrow-down-up ms-1';
        });
    }

    showTableLoader() {
        if (this.hasTableContainerTarget) {
            this.tableContainerTarget.style.opacity = '0.5';
            this.tableContainerTarget.style.pointerEvents = 'none';
        }
    }

    hideTableLoader() {
        if (this.hasTableContainerTarget) {
            this.tableContainerTarget.style.opacity = '1';
            this.tableContainerTarget.style.pointerEvents = 'auto';
        }
    }

    // LiveComponent lifecycle hooks
    liveConnect() {
        this.loadingValue = false;
        this.hideAllLoaders();
    }

    liveDisconnect() {
        this.showTableLoader();
    }

    liveRender() {
        this.loadingValue = false;
        this.hideAllLoaders();
    }

    hideAllLoaders() {
        this.hideSearchLoader();
        this.hideSortLoaders();
        this.hideTableLoader();
    }
}
```
### 6. Template du composant (avec debounce et loaders)
```twig
{# templates/neox_crud/components/live_index_table.html.twig #}
<div {{ attributes }}
     data-controller="neox-crud--live-table"
     data-neox-crud--live-table-debounce-delay-value="300">
    
    <!-- Header : recherche + actions -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" 
                       class="form-control" 
                       placeholder="Rechercher..." 
                       data-neox-crud--live-table-target="searchInput"
                       data-action="input->neox-crud--live-table#search"
                       value="{{ this.searchQuery }}">
                <button class="btn btn-outline-secondary" type="submit">
                    <span data-neox-crud--live-table-target="searchIcon">
                        <i class="bi bi-search"></i>
                    </span>
                </button>
            </div>
        </div>
        <div class="col-md-6 text-end">
            {% for button in this.toolbarButtons %}
                <a href="{{ button.url }}" 
                   class="btn {{ button.class|default('btn-primary') }}"
                   {% if button.turbo is defined %}{{ button.turbo.attributes|raw }}{% endif %}>
                    {% if button.icon %}<i class="{{ button.icon }}"></i> {% endif %}
                    {{ button.label }}
                </a>
            {% endfor %}
        </div>
    </div>

    <!-- Content : table + pagination -->
    <div class="card" data-neox-crud--live-table-target="tableContainer">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       data-action="click->neox-crud--bulk-selection#toggleAll">
                            </th>
                            {% for field in this.normalizedFields %}
                                <th {% if field.sortable %} 
                                        class="sortable cursor-pointer" 
                                        data-sort-field="{{ field.name }}"
                                        data-neox-crud--live-table-target="sortIcons"
                                        data-action="click->neox-crud--live-table#sort"
                                    {% endif %}>
                                    {{ field.label }}
                                    {% if field.sortable %}
                                        <i class="bi bi-arrow-down-up ms-1" data-neox-crud--live-table-target="sortIcons"></i>
                                    {% endif %}
                                </th>
                            {% endfor %}
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for item in this.items.currentPageResults %}
                            <tr>
                                <td>
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           value="{{ item.id }}"
                                           data-neox-crud--bulk-selection-target="item"
                                           data-action="click->neox-crud--bulk-selection#toggleItem">
                                </td>
                                {% for field in this.normalizedFields %}
                                    <td>
                                        {{ this.renderFieldValue(item, field) }}
                                    </td>
                                {% endfor %}
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        {% for action in this.rowActions %}
                                            <a href="{{ action.url }}" 
                                               class="btn btn-sm {{ action.class|default('btn-outline-primary') }}"
                                               {% if action.turbo is defined %}{{ action.turbo.attributes|raw }}{% endif %}>
                                                {% if action.icon %}<i class="{{ action.icon }}"></i>{% endif %}
                                            </a>
                                        {% endfor %}
                                    </div>
                                </td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination intégrée -->
        {% if this.items.haveToPaginate %}
            <div class="card-footer">
                {{ pagerfanta(this.items, 'twitter_bootstrap5') }}
            </div>
        {% endif %}
    </div>

    <!-- Footer : actions de masse + informations -->
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="d-flex align-items-center">
                <span class="me-3">
                    <strong>{{ this.items.nbResults }}</strong> résultats
                    {% if this.searchQuery %} pour "{{ this.searchQuery }}"{% endif %}
                </span>
                {% if this.activeFilters|length > 0 %}
                    <small class="text-muted">
                        Filtres actifs : {{ this.activeFilters|join(', ') }}
                    </small>
                {% endif %}
            </div>
        </div>
        <div class="col-md-6 text-end">
            {% if this.bulkActions|length > 0 %}
                <div class="dropdown" 
                     data-neox-crud--bulk-selection-target="dropdown" 
                     style="display: none;">
                    <button class="btn btn-primary dropdown-toggle" 
                            type="button" 
                            data-bs-toggle="dropdown">
                        <span data-neox-crud--bulk-selection-target="count">0</span> sélectionné(s)
                    </button>
                    <ul class="dropdown-menu">
                        {% for action in this.bulkActions %}
                            <li>
                                <a class="dropdown-item" 
                                   href="{{ action.url }}" 
                                   {% if action.turbo is defined %}{{ action.turbo.attributes|raw }}{% endif %}>
                                    {% if action.icon %}<i class="{{ action.icon }}"></i> {% endif %}
                                    {{ action.label }}
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </div>
            {% endif %}
        </div>
    </div>
</div>
```

---

## Gestion des erreurs et edge cases

### Stratégie de fallback
```php
try {
    $component = $this->liveComponentFactory->create($componentClass, $data);
} catch (ComponentNotFoundException $e) {
    // Fallback vers index classique
    return $this->renderClassicIndex($resource, $request);
}
```

### Validation des entrées
```php
final class InputValidator
{
    public function validateSortField(string $field, array $allowedFields): bool
    {
        return in_array($field, $allowedFields, true);
    }
    
    public function validateFilterValue(string $field, mixed $value, array $fieldConfig): bool
    {
        // Validation selon le type de filtre
        // Protection contre injection
    }
}
```

### Messages utilisateur
- Erreur de configuration : "Table configuration invalid. Please check your neox_crud.yaml"
- Timeout requête : "Query timeout. Try reducing filters or contact administrator"
- Droits insuffisants : "You don't have permission to access this feature"

---

## Performance optimisation

### Cache des métadonnées
```php
final class MetadataCache
{
    public function getNormalizedFields(string $resourceClass): array
    {
        $key = "neox_crud.fields.{$resourceClass}";
        return $this->cache->get($key, fn() => $this->computeFields($resourceClass));
    }
}
```

### Optimisations Doctrine
- **JOINs conditionnels** : uniquement si nécessaire
- **Indexation** : suggestions pour champs fréquemment triés
- **Pagination** : limites configurables (max_per_page)
- **Timeout** : protection contre requêtes longues

### Monitoring
```yaml
neox_crud:
  live_table:
    performance:
      query_timeout: 5000  # ms
      max_joins: 5
      cache_ttl: 3600      # seconds
```

---

## Sécurité renforcée

### Rate limiting
```yaml
neox_crud:
  live_table:
    security:
      rate_limit:
        enabled: true
        limit: 100        # requests per minute
        per_ip: true
```

### Validation stricte
```php
final class SecurityValidator
{
    public function validateQueryPath(string $path): bool
    {
        // Regex stricte pour éviter injection
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $path);
    }
    
    public function validateBulkAction(string $action, array $allowedActions): bool
    {
        return in_array($action, $allowedActions, true);
    }
}
```

### Audit trail
```php
final class AuditLogger
{
    public function logBulkAction(string $action, array $ids, string $user): void
    {
        $this->logger->info('Bulk action executed', [
            'action' => $action,
            'count' => count($ids),
            'user' => $user,
            'ip' => $this->requestStack->getCurrentRequest()->getClientIp()
        ]);
    }
}
```

---

## Tests et qualité

### Tests unitaires
```php
final class ColumnNormalizerServiceTest extends TestCase
{
    public function testNormalizeIndexFieldsWithObjects(): void
    {
        // Test format objets avec options
    }
    
    public function testNormalizeIndexFieldsWithStrings(): void
    {
        // Test format strings (BC)
    }
    
    public function testVotersOnColumns(): void
    {
        // Test affichage conditionnel
    }
}
```

### Tests fonctionnels
```php
final class LiveTableComponentTest extends WebTestCase
{
    public function testLiveTableRendering(): void
    {
        // Test rendu complet du composant
    }
    
    public function testSortingAndPagination(): void
    {
        // Test interaction utilisateur
    }
    
    public function testBulkActions(): void
    {
        // Test actions de masse avec validation
    }
}
```

### Tests d'intégration
```php
final class HandlerCompatibilityTest extends TestCase
{
    public function testExistingHandlersCompatibility(): void
    {
        // Vérifier que les handlers existants fonctionnent
    }
    
    public function testEventsIntegration(): void
    {
        // Test que les events existants sont toujours déclenchés
    }
}
```

### Tests de charge
```php
final class PerformanceTest extends TestCase
{
    public function testLargeDatasetPerformance(): void
    {
        // 10K+ enregistrements avec JOINs
        $this->assertLessThan(500, $response->getTime());
    }
}
```

---

## Documentation et guides

### Guide de migration pas-à-pas
```markdown
# Migration vers Live Table - Guide Développeur

## Étape 1 : Activation globale (une seule fois)
```yaml
# config/packages/neox_crud.yaml
neox_crud:
  live_table:
    enabled: true
    default_per_page: 25
    max_per_page: 100
```

## Étape 2 : Configuration par handler (3 options)

### Option A : Configuration complète ✨ RECOMMANDÉE
1. Copiez la section "CONFIGURATION DE BASE" dans votre handler YAML
2. Décommentez la section `neox_crud:` entière
3. Adaptez les champs à votre entité
4. Personnalisez les actions selon vos besoins

### Option B : Configuration rapide ⚡
1. Copiez la section "CONFIGURATION SIMPLE"
2. Décommentez uniquement cette section
3. Remplacez les noms de champs par les vôtres

### Option C : Configuration avancée 🚀
1. Utilisez la section "CONFIGURATION AVANCÉE"
2. Décommentez les options souhaitées
3. Ajoutez templates custom si besoin

## Étape 3 : Test et validation
- [ ] Vérifiez que l'index classique fonctionne toujours
- [ ] Testez la recherche, tri, pagination
- [ ] Validez les actions (edit, delete, bulk)
- [ ] Testez les différents comportements Turbo

## Étape 4 : Déploiement progressif
- Commencez par 1-2 handlers simples
- Étendez aux handlers plus complexes
- Monitornez les performances

## 🚨 Points importants
- **Ne supprimez jamais** la configuration existante avant de tester
- **Testez** chaque action après activation
- **Gardez** une copie de votre config originale
```

### Référence rapide des options
```markdown
# RÉFÉRENCE RAPIDE - OPTIONS DISPONIBLES

## index_fields
- `name` (requis) : nom du champ (ex: 'email', 'profile.name')
- `label` : affichage dans l'en-tête
- `sortable: true` : active le tri
- `searchable: true` : active la recherche
- `filter: { type: boolean|choice|date|range }` : filtre avancé
- `join: left|inner` : type de jointure pour champs dot-notation
- `format: 'Y-m-d'` : formatage date/datetime
- `boolean_icon: true` : affiche ✓/✗ pour booléens
- `type: image` : rendu <img> avec class/width
- `voters: ['ROLE_ADMIN']` : affichage conditionnel

## toolbar_buttons / actions / bulk_actions
- `name` (requis) : identifiant unique
- `label` : texte du bouton
- `icon` : classe Bootstrap Icons (ex: 'bi bi-pencil')
- `class` : classes CSS additionnelles
- `turbo: { frame: 'crud_table' }` : refresh partiel
- `turbo: { frame: 'crud_modal' }` : modal
- `turbo: false` : refresh classique
- `turbo: { confirm: 'Message ?' }` : confirmation
- `method: GET|POST|DELETE` : méthode HTTP
- `voters: ['ROLE_ADMIN']` : droits requis

## Exemples prêts à copier
```yaml
# Champ email complet
- { name: 'email', label: 'Email', sortable: true, searchable: true }

# Champ avec filtre boolean
- { name: 'isActive', label: 'Actif', sortable: true, filter: { type: boolean }, boolean_icon: true }

# Champ avec jointure
- { name: 'company.name', label: 'Entreprise', sortable: true, join: left }

# Bouton modal
- { name: 'edit', label: 'Éditer', turbo: { frame: 'crud_modal' } }

# Bouton avec confirmation
- { name: 'delete', label: 'Supprimer', turbo: { frame: 'crud_table', confirm: 'Supprimer ?' } }

# Bouton classique
- { name: 'download', turbo: false }
```

### Référence API développeur
```markdown
# Extension des handlers

## Custom field renderer
```php
final class CustomFieldRenderer implements FieldRendererInterface
{
    public function render(object $entity, array $fieldConfig): string
    {
        // Logique de rendu personnalisée
    }
}
```

## Custom filter type
```php
final class RangeFilterType implements FilterTypeInterface
{
    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->add('min', IntegerType::class);
        $builder->add('max', IntegerType::class);
    }
}
```

---

## Livrables

### Fichiers à créer
```
src/
├── LiveComponent/
│   ├── LiveIndexTableComponent.php
│   └── LiveIndexTableComponentFactory.php
├── Service/
│   ├── ColumnNormalizerService.php
│   ├── JoinResolverService.php
│   ├── QueryBuilderService.php
│   ├── MetadataCache.php
│   ├── SecurityValidator.php
│   ├── NotificationManager.php
│   ├── ExportService.php
│   └── PerformanceOptimizer.php
├── Event/
│   └── LiveTableEvents.php
├── StimulusController/
│   ├── bulk_selection_controller.js
│   └── live_table_controller.js
└── Twig/
    └── NotificationExtension.php

templates/
└── neox_crud/
    ├── index_live.html.twig
    ├── components/
    │   ├── live_index_table.html.twig
    │   ├── live_index_table_accessible.html.twig
    │   └── modal_content.html.twig
    └── notifications/
        ├── bootstrap_toasts.html.twig
        └── bootstrap_modal.html.twig

translations/
├── neox_crud.fr.yaml
└── neox_crud.en.yaml

config/
└── live_table.php

tests/
├── Unit/
│   ├── ColumnNormalizerServiceTest.php
│   ├── JoinResolverServiceTest.php
│   ├── SecurityValidatorServiceTest.php
│   ├── NotificationManagerTest.php
│   ├── ExportServiceTest.php
│   └── PerformanceOptimizerTest.php
├── Functional/
│   ├── LiveTableComponentTest.php
│   ├── HandlerCompatibilityTest.php
│   ├── ExportTest.php
│   └── AccessibilityTest.php
├── Performance/
│   ├── LargeDatasetTest.php
│   └── VirtualScrollingTest.php
└── E2E/
    ├── KeyboardNavigationTest.php
    └── ScreenReaderTest.php
```

### Fichiers à modifier
```
src/
├── Controller/
│   └── GenericCrudController.php (ajout route live)
├── CrudHandler/
│   └── AbstractDoctrineCrudHandler.php (helpers)
├── DependencyInjection/
│   ├── Configuration.php (options live_table)
│   └── NeoxCrudExtension.php
├── Maker/
│   └── tpl/HandlerConfig.yaml.tpl (mettre à jour les exemples)
└── Resources/
    └── config/
        └── services.yaml (nouveaux services)

docs/
├── fr/
│   ├── live-table.md
│   └── migration-live-table.md
└── en/
    ├── live-table.md
    └── migration-live-table.md

CHANGELOG.md
```

---

## Timeline et phases

### Phase 1 (2-3 jours) - MVP
- [ ] LiveComponent basic avec pagination
- [ ] Tri sur colonnes simples
- [ ] Configuration opt-in
- [ ] Tests unitaires core

### Phase 2 (2-3 jours) - Search & Filters
- [ ] Recherche LIKE
- [ ] Filtres typés basic
- [ ] JOINs automatiques
- [ ] Tests fonctionnels

### Phase 3 (2 jours) - Bulk Actions
- [ ] Sélection multiple
- [ ] Intégration bulk_actions
- [ ] Validation droits
- [ ] Tests sécurité

### Phase 4 (1-2 jours) - Turbo Integration
- [ ] Options turbo par action
- [ ] Support frames
- [ ] Tests intégration

### Phase 5 (1-2 jours) - Notifications
- [ ] Service NotificationManager
- [ ] Intégration neoxWrap
- [ ] Templates notifications
- [ ] Tests notifications

### Phase 6 (2 jours) - Internationalisation
- [ ] Fichiers de traduction FR/EN
- [ ] Templates avec i18n
- [ ] Controller traductions
- [ ] Tests i18n

### Phase 7 (2 jours) - Export Avancé
- [ ] Service ExportService
- [ ] Export CSV/Excel/JSON
- [ ] Streaming pour gros volumes
- [ ] Tests export

### Phase 8 (2 jours) - Accessibilité WCAG
- [ ] Templates accessibles
- [ ] ARIA labels complets
- [ ] Navigation clavier
- [ ] Tests accessibilité

### Phase 9 (1-2 jours) - Performance
- [ ] Service PerformanceOptimizer
- [ ] Lazy loading optionnel
- [ ] Cache intelligent
- [ ] Tests performance

### Phase 10 (1-2 jours) - Polish & Docs
- [ ] Documentation complète
- [ ] Guide migration
- [ ] Tests finaux
- [ ] Validation checklist

**Total estimé : 15-20 jours**

---

## Validation finale

### Checklist de validation
- [ ] **Composer validate** : `composer validate --strict`
- [ ] **CS-Fixer** : `composer cs:check`
- [ ] **PHPStan** : `composer stan`
- [ ] **Tests** : `composer test`
- [ ] **Documentation FR/EN** : à jour
- [ ] **CHANGELOG** : mis à jour
- [ ] **BC Break** : aucun (vérification manuelle)
- [ ] **Performance** : tests charge OK
- [ ] **Sécurité** : validation inputs OK

### Critères de succès
- ✅ **Rétrocompatibilité** 100%
- ✅ **Performance** égale ou supérieure à l'index classique
- ✅ **Sécurité** renforcée (rate limiting, validation)
- ✅ **Documentation** complète pour développeurs
- ✅ **Tests** couvrant tous les cas critiques
- ✅ **Accessibilité** WCAG niveau AA

---

## Conclusion

Ce prompt optimisé structure la feature de manière **progressive et sécurisée**, avec une attention particulière à la **rétrocompatibilité**, la **performance**, et la **sécurité**.

L'approche par phases permet des **retours fréquents** et réduit le risque de régression, tandis que la documentation complète assure une **adoption sereine** par les développeurs existants.
