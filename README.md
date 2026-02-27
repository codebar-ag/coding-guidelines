# Coding Guidelines

Shared Laravel AI coding guidelines and skills for codebar-ag projects. A Boost-first Composer package that ships 36 skills for consistent conventions across PHP, Laravel, testing, frontend, and services.

**Rule index:** See [RULES.md](RULES.md) for the full rule structure and file reference.

---

## Skills Summary

| Category | Skills | Description |
|----------|--------|-------------|
| **Laravel** | General, HelperFunctions, Models, Controllers, Migrations, Routing, FormRequests | Framework conventions, config/logging, Eloquent, routes, validation |
| **Backend** | Actions, Commands, DTO, Enums, Events, Exceptions, Helpers, Interfaces, Jobs, Middleware, Observers, Policies, Requests, Resources, Services, Traits | PHP architecture, single-purpose classes, API resources, queue jobs |
| **Frontend** | Blade, Design, Livewire, Tailwind, Translations | Templates, design system, components, Tailwind CSS, i18n |
| **Testing** | PHPUnit, PestTesting, PHPStan, Dusk | Unit/feature tests, static analysis, browser tests |
| **Services** | Saloon, DocuWare, Albatros | External API integrations (Saloon, DocuWare, Albatros) |

**Total: 36 skills.** Each skill includes Rules, Examples, Anti-Patterns, and References.

---

## Implementation Guide

### Prerequisites

- A Laravel project where you want to use these guidelines
- [Laravel Boost](https://github.com/laravel/boost) (required for skill discovery)

### Step 1: Install Laravel Boost

In your Laravel project:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

### Step 2: Add this package

**Option A — Composer (recommended for version pinning):**

```bash
composer require codebar-ag/coding-guidelines --dev
```

**Option B — GitHub via Boost:**

```bash
php artisan boost:add-skill codebar-ag/coding-guidelines
```

### Step 3: Sync skills

```bash
php artisan boost:update
```

Boost automatically discovers skills from `vendor/codebar-ag/coding-guidelines/resources/boost/skills/` when the package is installed.

### Step 4: Activate MCP (optional)

If your editor supports MCP (Model Context Protocol), configure it to use the Boost MCP server for full context. See the [Laravel Boost documentation](https://laravel.com/docs/boost) for your editor’s setup.

### Step 5: Override skills locally (optional)

To customize a skill for your project, create a file at `.ai/skills/{skill-name}/SKILL.md`. Your local version takes precedence over the package default.

Example: override the Models skill:

```
your-project/
├── .ai/
│   └── skills/
│       └── models/
│           └── SKILL.md    ← Your custom version
```

---

## How It Works

This package places skills in `resources/boost/skills/{skill-name}/SKILL.md`. Laravel Boost v2.2+ automatically discovers skills from vendor packages when you run `boost:update`. No custom sync commands — Boost handles everything.

| Source | Path |
|--------|------|
| This package | `vendor/codebar-ag/coding-guidelines/resources/boost/skills/` |
| Project overrides | `.ai/skills/{skill-name}/SKILL.md` |

---

## Updating

When using Composer:

```bash
composer update codebar-ag/coding-guidelines
php artisan boost:update
```

When using `boost:add-skill`:

```bash
php artisan boost:update
```
