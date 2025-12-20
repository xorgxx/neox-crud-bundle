
- If Maker refuses to overwrite an existing FormType, check the .php.sav file and merge changes manually.
- Ensure your templates reference the `items` variable returned by findList().
- For advanced i18n strategies, review translations.field_keys and patterns.

See also
- [docs/en/config.md — Configuration reference](./config.md)
- [docs/en/config-examples.md — Practical configuration examples](./config-examples.md)
- [docs/en/cli.md — CLI / Maker reference](./cli.md)
- [docs/en/uuid.md — Identifier support (UUID/ULID)](./uuid.md)
- Starter kit: [doc_en.md](../starter_kit/doc_en.md) and examples under `starter_kit/`

---

13. Development and quality checks

For contributors working on the bundle itself, the following quality tools are configured:

```bash
# Validate composer.json strictly
composer validate

# Coding standards (PSR-12, ordered imports, no unused imports, strict_types)
composer cs:check   # dry-run
composer cs:fix     # apply fixes

# Static analysis
composer stan

# Tests
composer test
```

Configuration files:
- `.php-cs-fixer.dist.php`
- `phpstan.neon.dist`
- `phpunit.xml.dist`

These commands are documented in the project guidelines and do not impact end-users of the bundle.
