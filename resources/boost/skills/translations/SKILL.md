---
name: translations
description: Translation and localization conventions for Laravel. Use when adding user-facing strings, creating translation files, or working with lang/ directory.
compatible_agents:
  - implement
  - refactor
  - review
---

# Translations

## When to Use

- You add or modify user-facing labels/messages in PHP, Blade, Livewire, or API responses.
- You introduce domain vocabulary that must be available in `en` and `de`.
- You touch the `lang/` directory or translation test coverage.

## When Not to Use

- Internal debug logs, developer-only diagnostics, and system telemetry.
- Low-level exception details intended for logs/monitoring only (not user UI).
- Temporary local debugging text that never ships to users.

## Rules

- Never hardcode user-facing text — always use `__()` or `@lang()`
- This project supports **English (`en`)** and **German (`de`)** — add keys to both locales when creating new translations
- Use **namespaced keys** (`lang/{locale}/{group}.php`) for domain-specific terms (e.g. `sprints.`, `organizations.`)
- Use **JSON keys** (`lang/{locale}.json`) for generic UI labels (Save, Back, Cancel, etc.)
- Use `:placeholder` for dynamic values in translation strings
- Run translation tests after changes: `php artisan test --filter=MissingTranslation`
- Automated detection: `MissingTranslationTest.php` scans for `__()`, `trans()`, and `@lang()` and verifies every key exists in every locale
- Missing keys fall back to the key string at runtime, which is a production-facing defect; treat missing keys as test failures

## Examples

```php
// lang/en/sprints.php — namespaced keys for domain-specific strings
return [
    'step_locked' => 'This step is locked.',
    'create_success' => 'Sprint :name was created successfully.',
];

// Usage
__('sprints.step_locked');
__('sprints.create_success', ['name' => $sprint->name]);
```

```json
// lang/de.json — generic UI labels
{
    "Save": "Speichern",
    "Back": "Zurück",
    "Cancel": "Abbrechen"
}

// Usage
__('Save');
__('Back');
```

```blade
{{-- In Blade templates — always use translation helpers --}}
<button type="submit">{{ __('Save') }}</button>
<p>{{ __('sprints.step_locked') }}</p>
<p>{{ __('Configure the :provider API key.', ['provider' => $provider]) }}</p>
```

```php
// In PHP — use trans() or __()
throw new ValidationException(__('validation.required', ['attribute' => 'email']));
Log::info(__('sprints.import_started', ['count' => $count]));
```

```php
// Recommended workflow for adding a new domain key
// 1) Add key to lang/en/sprints.php
// 2) Mirror the same key in lang/de/sprints.php
// 3) Use __() in code
// 4) Run: php artisan test --filter=MissingTranslation
```

## Checklist

- [ ] Every new user-facing string uses `__()` or `@lang()`.
- [ ] New keys are added to both `lang/en/*` and `lang/de/*`.
- [ ] Domain terms use namespaced PHP files; generic labels use JSON files.
- [ ] Placeholder names are identical across locales.
- [ ] `MissingTranslation` tests pass after changes.

## Anti-Patterns

- Hardcoding user-facing strings like `'Save'` or `'This step is locked.'` directly in views or PHP
- Adding a translation key to only one locale (en or de)
- Using namespaced keys for generic labels — use JSON for Save, Back, Cancel
- Using JSON keys for domain-specific terminology — use PHP files with namespaces
- Forgetting `:placeholder` syntax for dynamic values: `__('Welcome, :name')` with `['name' => $user->name]`
- Not running `php artisan test --filter=MissingTranslation` after adding or changing translations

## References

- [Laravel Localization](https://laravel.com/docs/localization)
- [Translation Helper](https://laravel.com/docs/helpers#method-__)
- Related: `README.md` — project-level language setup and testing commands
- Related: `Blade/SKILL.md` — use `__()` in Blade for all user-facing text
- Related: `PHPUnit/SKILL.md` — MissingTranslationTest validates translation coverage
