# Coding Guidelines

Shared Cursor AI rules for codebar-ag Laravel projects. See [RULES.md](RULES.md) for the full rule index.

## Install

From your Laravel project root:

**1. Clone the repo**

```bash
git clone --depth 1 git@github.com:codebar-ag/coding-guidelines.git guidelines
```

**2. Add to `.gitignore`**

```
/guidelines
```

**3. Create `.cursor/rules/guidelines.mdc`**

```markdown
---
description: References shared coding guidelines in the guidelines/ directory
alwaysApply: true
---

# Shared Coding Guidelines

This project has shared coding guidelines in the `guidelines/` directory
(synced from the codebar-ag/coding-guidelines repo).

Before making any code change, read and follow the relevant rule files:

- `guidelines/rules/testing/` -- PHPUnit, PHPStan conventions
- `guidelines/rules/frontend/` -- Blade, Livewire, Tailwind conventions
- `guidelines/rules/backend/` -- PHP typing, enums, DTOs, jobs conventions
- `guidelines/rules/laravel/` -- Models, controllers, migrations, routing conventions
- `guidelines/rules/services/` -- Saloon, DocuWare, Albatros API conventions

See `guidelines/RULES.md` for the full structure and index.
```

## Update

```bash
git -C guidelines pull
```
