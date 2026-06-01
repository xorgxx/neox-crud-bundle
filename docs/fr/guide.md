
NeoxCrudBundle te fournit une **colonne vertébrale CRUD** pour tous tes back-offices :

- moins de duplication
- plus de lisibilité
- des points d’extension clairs (hooks, events, actions custom)
- une intégration facile dans ton écosystème Symfony.

Voir aussi
- docs/fr/controller.md — Contrôleur CRUD générique (routes, flux, LiveTable vs classique)
- docs/fr/forms-relations.md — Forms & relations (ManyToOne/ManyToMany/OneToOne) + hooks (createEntity/preUpdate/beforeSave/beforeDelete)


---

## Symfony UX Autocomplete (optionnel)

Pour utiliser le rendu `autocomplete` pour les relations dans les formulaires, installez Symfony UX Autocomplete.

```bash
composer require symfony/ux-autocomplete
```

Si votre projet utilise Symfony UX (Stimulus), pensez à activer le contrôleur côté assets selon la documentation Symfony UX.

Configuration Maker (optionnel) :

```yaml
neox_crud:
  makers:
    relations:
      mode: 'interactive'             # mix|interactive (défaut: mix)
      default_render: 'autocomplete'
```

- `mode: 'mix'` (défaut) : le Maker pose des questions seulement en cas d'ambiguïté.
- `mode: 'interactive'` : le Maker pose systématiquement des questions pour chaque relation.

## 13. Développement & contrôles qualité

Pour contribuer au bundle lui‑même, les outils suivants sont configurés :

```bash
# Validation stricte de composer.json
composer validate

# Normes de code (PSR‑12, imports ordonnés, pas d’imports inutilisés, strict_types)
composer cs:check   # dry‑run
composer cs:fix     # applique les correctifs

# Analyse statique
composer stan

# Tests
composer test
```

Fichiers de configuration :
- `.php-cs-fixer.dist.php`
- `phpstan.neon.dist`
- `phpunit.xml.dist`

Ces commandes sont destinées aux contributrices/teurs du bundle et n’impactent pas les projets utilisateurs.
