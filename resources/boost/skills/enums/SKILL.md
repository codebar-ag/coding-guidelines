---
name: enums
description: PHP backed string enums used instead of constants or magic strings. Enums include `label()` and `color()` helper methods and are cast on Eloquent models.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Enums

## When to Use

- When a domain field has a **closed set of values** (status, type, category).
- When you want type safety, autocomplete, and safer refactors than raw strings.
- When values are stored in the database and cast on Eloquent models.

## When NOT to Use

- For open-ended or user-defined values that can change at runtime.
- For free-text labels coming from external systems that are not a stable finite set.

## Preconditions

- PHP version supports enums.
- Enum classes are placed in `app/Enums/`.
- Related Eloquent models cast enum-backed columns.

## Rules

- Use **backed string enums** — never use plain constants or magic strings for finite sets of values
- Include `label(): string` and `color(): string` helper methods on every enum
- Use `color()` for **UI color tokens** (for example Tailwind tokens like `gray`, `green`, `red`) unless the consuming UI explicitly requires a different format
- Place all enums in `app/Enums/`
- Always cast enum columns in model `casts()` methods
- Use enum string values in migrations: `$table->string('status')->default('draft')`
- Reference enum cases in code, never raw strings: `Status::Draft` not `'draft'`
- Use `match` expressions in `label()` and `color()` — never if/else chains
- Enum naming: `PascalCase` for enum classes and `PascalCase` for cases (`Status::Draft`)

## Examples

```php
enum Status: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Draft',
            self::Active   => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft    => 'gray',
            self::Active   => 'green',
            self::Archived => 'red',
        };
    }
}
```

```php
// Model cast
protected function casts(): array
{
    return [
        'status' => Status::class,
    ];
}

// Usage
if ($invoice->status === Status::Draft) { ... }
echo $invoice->status->label(); // 'Draft'
```

## Anti-Patterns

- Using raw strings instead of enum cases: `'draft'` instead of `Status::Draft`
- Using integer-backed enums when string enums are more readable
- Forgetting to cast enum columns in the model's `casts()` method
- Using if/else chains instead of `match` expressions in `label()` or `color()`
- Putting business logic inside enum methods (beyond label/color presentation helpers)
- Using enums for values that are dynamic, user-generated, or frequently changing

## References

- [PHP Enums](https://www.php.net/manual/en/language.enumerations.php)
- [Laravel Enum Casting](https://laravel.com/docs/eloquent-mutators#enum-casting)
- Related: `Models/SKILL.md` — for how enums are cast in Eloquent models
