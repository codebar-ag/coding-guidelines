# codebar Solutions AG Coding Guidelines

Shared Laravel coding guidelines and skills for codebar-ag projects. A Composer package that ships 36 skills for consistent conventions across PHP, Laravel, testing, frontend, and services.

**Rule index:** See [RULES.md](RULES.md) for the full rule structure and file reference.

---

## Skills Overview

Skills are reusable convention guides that assistants use when working with your codebase. Each skill includes Rules, Examples, Anti-Patterns, and References.

| Category | Skills | Description |
|----------|--------|-------------|
| **Laravel** | General, HelperFunctions, Models, Controllers, Migrations, Routing, FormRequests | Framework conventions, config/logging, Eloquent, routes, validation |
| **Backend** | Actions, Commands, DTO, Enums, Events, Exceptions, Helpers, Interfaces, Jobs, Middleware, Observers, Policies, Requests, Resources, Services, Traits | PHP architecture, single-purpose classes, API resources, queue jobs |
| **Frontend** | Blade, Design, Livewire, Tailwind, Translations | Templates, design system, components, Tailwind CSS, i18n |
| **Testing** | PHPUnit, PestTesting, PHPStan, Dusk | Unit/feature tests, static analysis, browser tests |
| **Services** | Saloon, DocuWare, Albatros | External API integrations (Saloon, DocuWare, Albatros) |

**Total: 36 skills.**

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

```bash
composer require codebar-ag/coding-guidelines --dev
```

### Step 3: Sync skills

```bash
php artisan boost:update
```

Boost automatically discovers skills from `vendor/codebar-ag/coding-guidelines/resources/boost/skills/` when the package is installed.

### Step 4: Activate MCP (optional)

If your editor supports MCP (Model Context Protocol), configure it to use the Boost MCP server for full context. See the [Laravel Boost documentation](https://laravel.com/docs/boost) for your editor's setup.

### Step 5: Refactor command (Cursor)

To run a full codebase refactor against all guidelines:

1. Ensure `.cursor/commands/` exists in your project
2. Copy the refactor prompt:
   - From package: `cp vendor/codebar-ag/coding-guidelines/refactor.md .cursor/commands/refactor.md`
   - Or from guidelines (if synced): `cp guidelines/refactor.md .cursor/commands/refactor.md`
3. In Cursor, type `/refactor` and run the command

The prompt instructs the AI to discover all skills, map them to your codebase, analyze for violations, and refactor for full compliance. Use it when newly adopting guidelines or to cross-check AI implementations.

### Step 6: Override skills locally (optional)

To customize a skill for your project, create a file at `.ai/skills/{skill-name}/SKILL.md`. Your local version takes precedence over the package default.

Example: override the Models skill:

```
your-project/
├── .ai/
│   └── skills/
│       └── models/
│           └── SKILL.md    ← Your custom version
```

### Alternative: Sync full repo

To sync the complete guidelines repo (`.github/prompts`, `RULES.md`, etc.) into a `guidelines/` directory for reference, add to your `composer.json`:

```json
"scripts": {
    "sync-guidelines": "php vendor/codebar-ag/coding-guidelines/scripts/sync-guidelines.php"
}
```

Then run `composer sync-guidelines`. This clones or pulls the repo into `guidelines/` at your project root and copies `refactor.md` to `.cursor/commands/refactor.md` for use with Cursor slash commands.

---

## How It Works

This package places skills in `resources/boost/skills/{skill-name}/SKILL.md`. Laravel Boost v2.2+ automatically discovers skills from vendor packages when you run `boost:update`. No custom sync commands — Boost handles everything.

| Source | Path |
|--------|------|
| This package | `vendor/codebar-ag/coding-guidelines/resources/boost/skills/` |
| Project overrides | `.ai/skills/{skill-name}/SKILL.md` |

---

## Updating

```bash
composer update codebar-ag/coding-guidelines
php artisan boost:update
```

---

## CI

GitHub Actions run on push and pull requests. Run validation locally: `composer validate-skills`
