
NeoxCrudBundle te fournit une **colonne vertébrale CRUD** pour tous tes back-offices :

- moins de duplication
- plus de lisibilité
- des points d’extension clairs (hooks, events, actions custom)
- une intégration facile dans ton écosystème Symfony.


---

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
