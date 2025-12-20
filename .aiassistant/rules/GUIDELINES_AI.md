---
apply: always
---

# GUIDELINES FOR AI CONTRIBUTORS — NeoxCrudBundle (Full Version)

This document defines strict rules for AI agents and developers to safely extend or modify NeoxCrudBundle without breaking its architecture, DX, or backward compatibility.

---

# 1. Project Goals

NeoxCrudBundle provides:

- A generic CRUD controller for /admin/{resource}
- Per-resource CrudHandlers encapsulating Doctrine, forms, hooks, actions
- A single Maker command to generate CRUDs
- Unified configuration: handlers + forms + translations derive from Maker + bundle config
- Stable DX and predictable CRUD generation

Contributions **must not** break these principles.

## 1.1 Version 
PHP 8.3
Symfony 7.3
---

# 2. Golden Rules (Mandatory for AI)

## 2.1 Minimal Patch Principle
Modify the **smallest possible scope**.
Do NOT rewrite or regenerate whole files unless explicitly required.

Exploratory work → place in:

docs/drafts/  
sandbox/

---

## 2.2 No BC Breaks
Do NOT:

- rename public classes
- change public method signatures
- remove existing hooks
- modify routes or template variable contracts
- rename Maker commands

Any BC break requires:

- version bump
- migration guide
- changelog entry

---

## 2.3 Bundle Namespace Only
All bundle code MUST remain under:

Neox\NeoxCrudBundle\

Never introduce:

use App\...

Except in generated user project files.

---

## 2.4 Respect Folder Architecture

src/
  Crud/
  Controller/
  Maker/
    tpl/
  DependencyInjection/
Resources/
  config/
  views/
docs/
  reference/
  drafts/

No new top-level folders unless approved.

---

## 2.5 Config First
If a behavior can be configured:

- via neox_crud.yaml
- via Configuration.php
- via NeoxCrudExtension.php

→ Extend configuration instead of duplicating code.

---

# 3. Documentation Rules

Every change MUST update:

CHANGELOG.md
docs/reference/cli.md
docs/reference/config.md
docs/reference/handlers.md

Documentation MUST be present in **FR + EN**.

---

# 4. Security Rules

AI MUST NOT introduce:

- eval()
- shell_exec, system, passthru, exec
- remote HTTP fetches
- unsafe deserialization

All YAML parsing MUST use:

Symfony\Component\Yaml\Yaml

---

# 5. Code Quality Rules

Changes MUST pass:

composer validate  
composer cs:check  
composer stan  
composer test  

Coding standards:

- PSR-12
- Ordered imports
- No unused imports
- 4-space indentation
- strict_types encouraged

---

# 6. Public API Stability

The following APIs are stable:

CrudHandlerInterface  
AbstractDoctrineCrudHandler  
CrudHandlerFactory  
GenericCrudController  
NeoxCrudMaker  
Templates variable naming  
CRUD route names  

AI MUST NOT modify their signatures.

---

# 7. Maker Commands Rules

Do NOT rename existing commands:

make:crud-handler  
make:neox:crud  

New flags must be:

- optional
- backward compatible
- documented in FR + EN
- tested

Templates MUST stay in:

src/Maker/tpl/

---

# 8. Handler Rules

Hooks MUST remain unchanged:

preCreate  
preUpdate  
preDelete  
beforeSave  
afterSave  
beforeDelete  
afterDelete  

AI MUST NOT rename or remove them.

New features MUST be opt-in.

---

# 9. Tests Rules

Each feature MUST include at least **one test**:

tests/Unit/...  
tests/Functional/...  

Tests MUST NOT:

- depend on networks
- rely on random values

---

# 10. Naming Rules

AI features MUST follow:

src/AI/<FeatureName>/
  Description.md
  Patch.php

Commits:

feat(crud): ...  
fix(crud): ...  
docs(crud): ...  

---

# 11. Template & Route Contracts

Templates MUST receive:

resource  
form  
entity  
items  
handler  

Routes MUST remain:

neox_crud_admin_crud_index  
neox_crud_admin_crud_new  
neox_crud_admin_crud_edit  
neox_crud_admin_crud_delete  
neox_crud_admin_crud_custom  

---

# 12. Final Validation Checklist

✔ No BC break  
✔ FR/EN documentation updated  
✔ Tests added  
✔ PHP-CS-Fixer OK  
✔ PHPStan OK  
✔ CHANGELOG updated  
✔ Maker output unaffected unless new feature enabled  

---

# 13. Example Acceptable Patch

Feature: auto boolean filter in index view.

AI MUST:

1. Extend YamlConfigGuesser OR metadata detection  
2. Add boolean filter markup to template  
3. Add BooleanFilterTest.php  
4. Update docs (yaml schema) FR + EN  
5. Update CHANGELOG  
6. Ensure BC  

---

# 14. AI PR Header

This change follows the NeoxCrudBundle AI Guidelines:
- minimal patch
- no BC break
- documentation FR/EN updated
- tests included
- coding standards verified
- configuration-first approach

