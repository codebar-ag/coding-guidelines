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
| `phpunit.mdc` | `tests/**/*.php` | PHPUnit test structure, naming, assertions, and factory conventions |
| `phpstan.mdc` | Always | PHPStan Level 9 static analysis compliance |
| `pest-testing.mdc` | Laravel Testing Framework Pest 4 |


### frontend/

Rules for frontend technologies and templating.

| File | Scope | Description |
|------|-------|-------------|
| `blade.mdc` | `resources/views/**/*.blade.php` | Blade template conventions and best practices |
| `design.mdc` | `resources/views/**/*.blade.php` | Component-first design system and responsive UI conventions |
| `livewire.mdc` | `app/Livewire/**/*.php` | Livewire component patterns and conventions |
| `tailwind.mdc` | `resources/css/**` | Tailwind CSS v4 styling conventions |
| `translations.mdc` | `lang/**/*.php,lang/**/*.json,resources/views/**/*.blade.php,app/**/*.php` | Translation and localization conventions |

### backend/

Rules for general PHP backend conventions (not Laravel-specific).

| File | Scope | Description |
|------|-------|-------------|
| `actions.mdc` | `app/Actions/**/*.php` | Action conventions — single-purpose business logic classes |
| `commands.mdc` | `app/Console/Commands/**/*.php` | Command conventions — command classes, how to use them, and what belongs in them |
| `dto.mdc` | `**/DataObjects/**/*.php` | Readonly Data Transfer Object conventions with fromArray factory |
| `enums.mdc` | `app/Enums/**/*.php` | PHP backed enum conventions with label and color helpers |
| `events-listeners.mdc` | `app/Events/**/*.php,app/Listeners/**/*.php` | Event & Listener conventions — decoupled communication between application layers |
| `exceptions.mdc` | `app/Exceptions/**/*.php` | Exception conventions — custom exceptions for domain and application errors |
| `helpers.mdc` | `app/Helpers/**/*.php` | Helper conventions — helper classes how to use and what belongs into it |
| `interfaces-contracts.mdc` | `app/Contracts/**/*.php` | Interface & Contract conventions — defining contracts between components |
| `jobs.mdc` | `app/Jobs/**/*.php` | Queued job conventions with retries, backoff, and structured logging |
| `middleware.mdc` | `app/Http/Middleware/**/*.php` | Middleware conventions — HTTP request and response pipeline |
| `observers.mdc` | `app/Observers/**/*.php` | Observer conventions — reacting to Eloquent model lifecycle events |
| `php.mdc` | `app/**/*.php` | General PHP conventions -- strict typing, formatting, language |
| `policies.mdc` | `app/Policies/**/*.php` | Policy conventions — authorization logic for models and resources |
| `requests.mdc` | `app/Http/Requests/**/*.php` | Form Request validation conventions |
| `resources.mdc` | `app/Http/Resources/**/*.php` | Resource conventions — API resources, collections, and response shaping |
| `services.mdc` | `app/Services/**/*.php` | Service conventions — complex orchestration across multiple domains |
| `traits.mdc` | `app/Traits/**/*.php` | Trait conventions — when to use traits, how to write them, and what belongs in them |

### laravel/

Rules for Laravel framework conventions.

| File | Scope | Description |
|------|-------|-------------|
| `general.mdc` | Always | Project-wide Laravel conventions that always apply |
| `laravel-helperfunctions.mdc` | Always | Laravel Helper Functions – Always use Arr::, Str:: and Collection methods instead of native PHP equivalents |
| `models.mdc` | `app/Models/**/*.php` | Eloquent model conventions -- guarded, casts, sections, activity log |
| `controllers.mdc` | `app/Http/Controllers/**/*.php` | Controller conventions -- thin controllers, invokable, service delegation |
| `migrations.mdc` | `database/migrations/**/*.php` | Database migration conventions -- always new files, naming, structure |
| `form-requests.mdc` | `app/Http/Requests/**/*.php,app/Http/Controllers/**/*.php` | Dedicated Form Request validation classes for all controller input |
| `routing.mdc` | `routes/**/*.php` | Route file conventions -- api vs web, grouping, model binding |

### services/

Rules for external API integrations. Add a new file for each service.

| File | Scope | Description |
|------|-------|-------------|
| `saloon.mdc` | `app/Services/**/*.php` | Saloon-based service layer pattern for external API integrations |
| `docuware.mdc` | `app/Services/DocuWare/**/*.php` | DocuWare integration patterns -- connector, webhook, import lifecycle |
| `albatros.mdc` | `app/Services/Albatros/**/*.php` | Albatros accounting API integration -- Saloon connector, DTOs, pagination |

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
