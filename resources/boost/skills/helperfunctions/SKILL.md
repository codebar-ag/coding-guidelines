---
name: helperfunctions
description: Always prefer Laravel's built-in helper classes over native PHP functions. Use `Arr::`, `Str::`, and Collection methods instead of native PHP equivalents.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Helper Functions

## When to Apply

- When transforming arrays, strings, or iterables in Laravel application code.
- When refactoring native PHP utility code to improve readability and consistency.
- When chaining multiple transformations on text or data collections.

## When NOT to Apply

- When no Laravel equivalent exists and native PHP is clearer.
- In tight low-level loops where collection wrapping would add unnecessary overhead.
- In framework-agnostic packages that should not depend on Laravel helpers.

## Preconditions

- Import `Illuminate\Support\Arr` for array helpers.
- Import `Illuminate\Support\Str` for string helpers.
- Use `collect()` only when collection operations improve clarity.

## Process

1. Identify native PHP helper usage (`array_*`, `str_*`, manual loops).
2. Replace with `Arr::`, `Str::`, or Collection methods where a direct equivalent exists.
3. Keep behavior identical (defaults, key handling, casing, null handling).
4. Prefer fluent chains (`Str::of()`, collections) for multi-step transformations.
5. Re-run tests/static analysis for the touched scope.

## Examples

```php
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

// Arrays
$city = Arr::get($payload, 'user.address.city', 'Unknown');
$safe = Arr::except($payload, ['password', 'token']);

// Strings
$slug = Str::of($title)->trim()->lower()->slug();

// Collections
$adminEmails = collect($users)
    ->filter(fn (User $user) => $user->isAdmin())
    ->pluck('email')
    ->values();
```

## Anti-Patterns

- Mixing native `array_*`/`str_*` calls with Laravel helpers in the same transformation flow.
- Replacing simple one-liners with collection chains that reduce readability.
- Omitting `use` imports and relying on fully qualified class names repeatedly.
- Rewriting code when no Laravel helper exists and native PHP is already explicit.

## References

- [Laravel Arr Helper](https://laravel.com/docs/helpers#arrays)
- [Laravel Str Helper](https://laravel.com/docs/helpers#strings)
- [Laravel Collections](https://laravel.com/docs/collections)
