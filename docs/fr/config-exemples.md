Exemples de configuration (FR)

Cette page propose des extraits prêts à l’emploi à copier dans votre application.

1) Configuration minimale (valeurs par défaut)
```yaml
neox_crud: { }
```

2) Activer Mercure avec un préfixe de topic personnalisé
```yaml
neox_crud:
  mercure:
    enabled: true
    topic_prefix: '/realtime/crud'
```

3) Étendre les clés de traduction et utiliser des patterns
```yaml
neox_crud:
  translations:
    field_keys: ['label', 'placeholder', 'help']
    patterns:
      placeholder: 'Saisir %field_label%'
      help: 'Aide concernant %field_label%'
```

Exemple de YAML généré (ressource « product »)
```yaml
product:
  field:
    name:
      label: 'Name'
      placeholder: 'Saisir Name'
      help: 'Aide concernant Name'
```

4) Changer le répertoire d’upload
```yaml
neox_crud:
  uploads_dir: 'public/media'
```

5) Désactiver les commandes Maker (ex. dans une image de prod)
```yaml
neox_crud:
  makers:
    enabled: false
```

6) Tout combiner
```yaml
neox_crud:
  uploads_dir: 'public/media'
  mercure:
    enabled: true
    topic_prefix: '/realtime/crud'
  makers:
    enabled: true
  translations:
    field_keys: ['label', 'placeholder', 'help']
    patterns:
      placeholder: 'Saisir %field_label%'
      help: 'Aide concernant %field_label%'
```

Voir aussi
- docs/fr/config.md — Référence complète de configuration
