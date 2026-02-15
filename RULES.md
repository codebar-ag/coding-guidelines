# Rules Structure

This file defines the folder structure and conventions for adding new Cursor rules.

## Folder Layout

```
rules/
  testing/          # Test framework and quality tool rules
  frontend/         # Blade, Livewire, CSS, and JS rules
  backend/          # General PHP conventions
  laravel/          # Laravel framework conventions
  services/         # External API integration rules
```

## Categories

### testing/

Rules for test frameworks and static analysis tools.

| File | Scope | Description |
|------|-------|-------------|
| `phpunit.mdc` | `tests/**/*.php` | PHPUnit test structure, naming, assertions, factories |
| `phpstan.mdc` | Always | PHPStan Level 9 compliance |

Future additions: `pest.mdc`, `dusk.mdc`, `larastan.mdc`

### frontend/

Rules for frontend technologies and templating.

| File | Scope | Description |
|------|-------|-------------|
| `blade.mdc` | `resources/views/**/*.blade.php` | Blade template conventions |
| `design.mdc` | `resources/views/**/*.blade.php` | Component-first design system and responsive UI conventions |
| `livewire.mdc` | `app/Livewire/**/*.php` | Livewire component patterns |
| `tailwind.mdc` | `resources/css/**` | Tailwind CSS v4 conventions |

Future additions: `alpine.mdc`, `inertia.mdc`

### backend/

Rules for general PHP backend conventions (not Laravel-specific).

| File | Scope | Description |
|------|-------|-------------|
| `php.mdc` | `app/**/*.php` | Strict typing, formatting, error handling |
| `enums.mdc` | `app/Enums/**/*.php` | Backed string enum conventions |
| `dto.mdc` | `**/DataObjects/**/*.php` | Readonly DTO pattern |
| `jobs.mdc` | `app/Jobs/**/*.php` | Queued job conventions |

### laravel/

Rules for Laravel framework conventions.

| File | Scope | Description |
|------|-------|-------------|
| `general.mdc` | Always | Config, logging, formatting, activity log |
| `models.mdc` | `app/Models/**/*.php` | Eloquent model conventions |
| `controllers.mdc` | `app/Http/Controllers/**/*.php` | Thin controller pattern |
| `migrations.mdc` | `database/migrations/**/*.php` | Migration conventions |
| `routing.mdc` | `routes/**/*.php` | Route organization |

### services/

Rules for external API integrations. Add a new file for each service.

| File | Scope | Description |
|------|-------|-------------|
| `saloon.mdc` | `app/Services/**/*.php` | General Saloon service pattern |
| `docuware.mdc` | `app/Services/DocuWare/**/*.php` | DocuWare integration |
| `albatros.mdc` | `app/Services/Albatros/**/*.php` | Albatros accounting API |

Future additions: `stripe.mdc`, `mailgun.mdc`, etc.

## How to Add a New Rule

1. Choose the correct category folder (or create a new one if needed)
2. Create a `.mdc` file with YAML frontmatter:

```markdown
---
description: Brief description of what this rule covers
globs: app/Path/**/*.php
alwaysApply: false
---

# Rule Title

Your rule content here...
```

3. Set the frontmatter:
   - `description`: shown in Cursor's rule picker
   - `globs`: file pattern -- rule activates when matching files are open
   - `alwaysApply: true`: rule applies to every conversation (use sparingly)

4. Keep rules concise (under 50 lines of content)
5. Include code examples showing the preferred pattern
6. Use lowercase kebab-case for filenames: `my-service.mdc`
