# NeoxCrudBundle - Live Table Interactive (AI-Optimized Prompt)

## 🎯 OBJECTIVE
Create an opt-in live table feature replacing the current index with Symfony UX LiveComponent + Pagerfanta, maintaining 100% backward compatibility.

## 📋 REQUIREMENTS SUMMARY

### Core Features (MVP)
- ✅ Live table with pagination, sorting, search, filters
- ✅ Bulk selection + actions
- ✅ Turbo integration (frames, confirmations)
- ✅ Bootstrap 5 responsive UI
- ✅ Opt-in configuration (disabled by default)

### Advanced Features
- ✅ Internationalization (FR/EN)
- ✅ Export (CSV/Excel/JSON) with streaming
- ✅ Accessibility WCAG (ARIA, keyboard navigation)
- ✅ Performance optimization (lazy loading, cache)
- ✅ Notifications (delegated to neoxWrapNotificatorBundle)

## 🏗️ ARCHITECTURE

### Component Structure
```
LiveIndexTableComponent (Symfony UX)
├── QueryBuilderService (logic)
├── ColumnNormalizerService (config processing)
├── JoinResolverService (Doctrine joins)
├── NotificationManager (notifications)
├── ExportService (data export)
├── PerformanceOptimizer (optimizations)
└── SecurityValidator (input validation)
```

### Data Flow
```
YAML Config → ColumnNormalizer → LiveComponent → Twig → Stimulus → User
```

## 📝 CONFIGURATION

### Global Configuration
```yaml
# config/packages/neox_crud.yaml
neox_crud:
  live_table:
    enabled: true
    default_per_page: 25
    max_per_page: 100
    theme: bootstrap5
  notifications:
    provider: auto  # auto|neox_wrap|symfony_notifier|bootstrap
    neox_wrap:
      enabled: true
      channel: 'crud'
      flash_integration: true
      turbo_integration: true
```

### Per-Handler Configuration
```yaml
# src/Crud/Handler/UserHandler.yaml
neox_crud:
    index_fields:
      - { name: 'id', label: 'ID', sortable: true }
      - { name: 'email', label: 'Email', sortable: true, searchable: true }
      - { name: 'profile.name', label: 'Name', sortable: true, join: left }
      - { name: 'isActive', label: 'Active', sortable: true, filter: { type: boolean } }
      - { name: 'createdAt', label: 'Created', sortable: true, filter: { type: date }, format: 'd/m/Y H:i' }
    
    toolbar_buttons:
      - { name: 'new', label: 'New', turbo: { frame: 'crud_modal' } }
      - { name: 'export_csv', label: 'Export CSV', turbo: { frame: 'crud_table' } }
    
    actions:
      - { name: 'edit', turbo: { frame: 'crud_modal' } }
      - { name: 'delete', turbo: { frame: 'crud_table', confirm: 'Delete this item?' } }
    
    bulk_actions:
      - { name: 'bulk_delete', turbo: { frame: 'crud_table', confirm: 'Delete selected items?' } }
```

## 🔧 IMPLEMENTATION TASKS

### Phase 1: Core LiveComponent (2-3 days)
**Priority: HIGH**
- [ ] Create `LiveIndexTableComponent` with basic pagination
- [ ] Implement `ColumnNormalizerService` for config processing
- [ ] Add `QueryBuilderService` with secure query building
- [ ] Create basic Twig template with Bootstrap 5
- [ ] Add unit tests for core services

### Phase 2: Search & Filters (2-3 days)
**Priority: HIGH**
- [ ] Implement search functionality with debounce
- [ ] Add typed filters (boolean, choice, date)
- [ ] Create `JoinResolverService` for dot-notation fields
- [ ] Add functional tests for search/filter features

### Phase 3: Bulk Actions (2 days)
**Priority: MEDIUM**
- [ ] Implement checkbox selection with Stimulus controller
- [ ] Add bulk actions integration
- [ ] Create security validation for bulk operations
- [ ] Add security tests

### Phase 4: Turbo Integration (1-2 days)
**Priority: MEDIUM**
- [ ] Add Turbo frame support
- [ ] Implement action-specific turbo options
- [ ] Create modal integration for edit/new
- [ ] Add integration tests

### Phase 5: Notifications (1-2 days)
**Priority: MEDIUM**
- [ ] Create `NotificationManager` with neoxWrap delegation
- [ ] Implement Bootstrap fallback notifications
- [ ] Add notification templates
- [ ] Test notification providers

### Phase 6: Internationalization (2 days)
**Priority: LOW**
- [ ] Create FR/EN translation files
- [ ] Update templates with `|trans` filters
- [ ] Add `TranslatorInterface` to controllers
- [ ] Test i18n functionality

### Phase 7: Export (2 days)
**Priority: LOW**
- [ ] Create `ExportService` with CSV/Excel/JSON support
- [ ] Implement streaming for large datasets
- [ ] Add export actions to toolbar
- [ ] Test export functionality

### Phase 8: Accessibility (2 days)
**Priority: LOW**
- [ ] Create WCAG-compliant template
- [ ] Add ARIA labels and roles
- [ ] Implement keyboard navigation
- [ ] Test with screen readers

### Phase 9: Performance (1-2 days)
**Priority: LOW**
- [ ] Create `PerformanceOptimizer` service
- [ ] Implement lazy loading for columns
- [ ] Add intelligent caching
- [ ] Performance testing

### Phase 10: Polish & Documentation (1-2 days)
**Priority: LOW**
- [ ] Update documentation (FR/EN)
- [ ] Create migration guide
- [ ] Update CHANGELOG.md
- [ ] Final validation checklist

## 🚀 DELIVERABLES

### Core Services
```php
// src/Service/
├── ColumnNormalizerService.php
├── QueryBuilderService.php  
├── JoinResolverService.php
├── NotificationManager.php
├── ExportService.php
├── PerformanceOptimizer.php
└── SecurityValidator.php
```

### LiveComponent
```php
// src/LiveComponent/
├── LiveIndexTableComponent.php
└── LiveIndexTableComponentFactory.php
```

### Templates
```twig
// templates/neox_crud/
├── index_live.html.twig
├── components/
│   ├── live_index_table.html.twig
│   ├── live_index_table_accessible.html.twig
│   └── modal_content.html.twig
└── notifications/
    ├── bootstrap_toasts.html.twig
    └── bootstrap_modal.html.twig
```

### Stimulus Controllers
```javascript
// assets/controllers/neox_crud/
├── live_table_controller.js
└── bulk_selection_controller.js
```

### Translations
```yaml
// translations/
├── neox_crud.fr.yaml
└── neox_crud.en.yaml
```

### Tests
```php
// tests/
├── Unit/
├── Functional/
├── Performance/
└── E2E/
```

## ⚠️ CONSTRAINTS & REQUIREMENTS

### Technical Constraints
- ✅ **NO BC BREAKS** - All existing APIs must remain unchanged
- ✅ **Namespace**: `Neox\NeoxCrudBundle\` only
- ✅ **No external dependencies** (except optional Symfony UX)
- ✅ **Configuration-first** approach
- ✅ **Opt-in only** - disabled by default

### Security Requirements
- ✅ **Input validation** for all user inputs
- ✅ **Whitelist-based** query building
- ✅ **CSRF protection** on all actions
- ✅ **Rate limiting** on search/filter operations
- ✅ **Permission checks** for all operations

### Performance Requirements
- ✅ **Equal or better** performance than current index
- ✅ **Memory efficient** for large datasets
- ✅ **Cache-friendly** design
- ✅ **Lazy loading** for optional features

### Accessibility Requirements
- ✅ **WCAG AA compliance**
- ✅ **Keyboard navigation** support
- ✅ **Screen reader** compatibility
- ✅ **ARIA labels** and semantic HTML

## 🧪 TESTING STRATEGY

### Unit Tests
```php
// Test coverage targets
- ColumnNormalizerService: 95%
- QueryBuilderService: 95%
- NotificationManager: 90%
- ExportService: 90%
- PerformanceOptimizer: 85%
```

### Functional Tests
```php
// Key scenarios
- Live table rendering
- Search and filtering
- Bulk operations
- Turbo navigation
- Export functionality
```

### Performance Tests
```php
// Performance benchmarks
- 10K records with joins: <500ms
- Memory usage: <128MB
- Concurrent users: 50+
```

### Accessibility Tests
```php
// WCAG compliance
- Screen reader compatibility
- Keyboard navigation
- Color contrast
- Focus management
```

## 📋 VALIDATION CHECKLIST

### Pre-deployment
- [ ] `composer validate --strict`
- [ ] `composer cs:check`
- [ ] `composer stan`
- [ ] `composer test`
- [ ] Performance benchmarks pass
- [ ] Accessibility tests pass
- [ ] Security audit complete

### Documentation
- [ ] FR/EN documentation updated
- [ ] CHANGELOG.md updated
- [ ] Migration guide created
- [ ] API reference updated

### Quality Assurance
- [ ] No BC breaks detected
- [ ] All tests passing
- [ ] Performance meets requirements
- [ ] Accessibility compliant
- [ ] Security validated

## 🎯 SUCCESS METRICS

### Technical Metrics
- ✅ **0 BC breaks**
- ✅ **<500ms response time** for 10K records
- ✅ **<128MB memory usage**
- ✅ **95%+ test coverage**
- ✅ **WCAG AA compliance**

### User Experience Metrics
- ✅ **Intuitive configuration** (copy-paste ready)
- ✅ **Smooth interactions** (debounce, loaders)
- ✅ **Responsive design** (mobile-first)
- ✅ **Accessible** (keyboard, screen reader)
- ✅ **Multi-language** support

## 🔄 IMPLEMENTATION ORDER

### Critical Path (MVP)
1. **LiveIndexTableComponent** - Core functionality
2. **ColumnNormalizerService** - Config processing
3. **QueryBuilderService** - Secure queries
4. **Basic template** - UI rendering
5. **Unit tests** - Core reliability

### Enhanced Features
6. **Search & filters** - User interaction
7. **Bulk actions** - Advanced functionality
8. **Turbo integration** - Modern UX
9. **Notifications** - User feedback

### Polish & Advanced
10. **Internationalization** - Multi-language
11. **Export** - Data export
12. **Accessibility** - WCAG compliance
13. **Performance** - Optimization
14. **Documentation** - Complete guide

## 🚨 RISK MITIGATION

### Technical Risks
- **Performance degradation** → Lazy loading + caching
- **Memory issues** → Streaming + pagination
- **Security vulnerabilities** → Whitelist + validation
- **Compatibility issues** → Extensive testing

### Implementation Risks
- **Scope creep** → Phase-based approach
- **Complexity** → Modular design
- **Timeline delays** → MVP-first strategy
- **Quality issues** → Automated testing

## 📚 REFERENCE IMPLEMENTATION

### Key Code Patterns
```php
// LiveComponent pattern
#[LiveComponent]
final class LiveIndexTableComponent
{
    #[LiveProp] public string $resourceClass;
    #[LiveProp] public array $normalizedFields;
    #[LiveProp] public int $page = 1;
    #[LiveProp] public ?string $searchQuery = null;
    
    #[LiveAction] public function search(string $query): void
    {
        $this->searchQuery = $query ?: null;
        $this->page = 1;
    }
}
```

```php
// Service pattern
final class ColumnNormalizerService
{
    public function normalizeIndexFields(array $indexFields): array
    {
        // Normalize configuration with defaults
        return array_map($this->normalizeField(...), $indexFields);
    }
}
```

```twig
<!-- Template pattern -->
<div {{ attributes }} data-controller="neox-crud--live-table">
    <input type="text" 
           data-neox-crud--live-table-target="searchInput"
           data-action="input->neox-crud--live-table#search"
           placeholder="{{ 'neox_crud.table.search_placeholder'|trans }}">
</div>
```

## 🎯 FINAL DELIVERABLE

This prompt provides a complete, actionable plan for implementing a modern live table feature for NeoxCrudBundle that:

1. **Maintains 100% backward compatibility**
2. **Provides modern UX** with Symfony UX LiveComponent
3. **Includes all advanced features** (i18n, export, accessibility, performance)
4. **Follows best practices** for security and performance
5. **Is thoroughly tested** and documented
6. **Is AI-optimized** for clear implementation

**Estimated timeline: 15-20 days for full implementation**
**MVP available: 8-10 days**
**Risk level: LOW** (phased approach, extensive testing)
