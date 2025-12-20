Configuration examples (EN)

This page provides practical snippets you can copy into your app to get started quickly.

1) Minimal setup (defaults)
```yaml
neox_crud: { }
```

2) Enable Mercure with custom topic prefix
```yaml
neox_crud:
  mercure:
    enabled: true
    topic_prefix: '/realtime/crud'
```

3) Extend translation field keys and use patterns
```yaml
neox_crud:
  translations:
    field_keys: ['label', 'placeholder', 'help']
    patterns:
      placeholder: 'Enter %field_label%'
      help: 'Help about %field_label%'
```

Example generated YAML (resource "product")
```yaml
product:
  field:
    name:
      label: 'Name'
      placeholder: 'Enter Name'
      help: 'Help about Name'
```

4) Change uploads directory
```yaml
neox_crud:
  uploads_dir: 'public/media'
```

5) Disable Maker commands (e.g., in production images)
```yaml
neox_crud:
  makers:
    enabled: false
```

6) Combine everything
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
      placeholder: 'Enter %field_label%'
      help: 'Help about %field_label%'
```

See also
- [Full configuration reference](./config.md)
- [CLI / Maker reference](./cli.md)
- [Identifier support (UUID/ULID)](./uuid.md)