---
name: models
description: Eloquent model conventions covering mass assignment, casts, relationships, section headers, and activity logging. Every model must follow these structural rules.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Models

## When to Apply

- When creating a new Eloquent model in `app/Models/`.
- When refactoring existing models to align with mass-assignment, casting, and logging conventions.
- When reviewing models for consistency in relationships, helpers, and activity logging.

## When NOT to Apply

- For DTOs, value objects, and classes that are not persisted with Eloquent.
- For pivot-only structures that do not use a dedicated business model.

## Preconditions

- Laravel application and Eloquent are configured.
- Database schema and migrations for the model’s table exist or are being designed.
- `spatie/laravel-activitylog` is installed and configured for activity logging.
- "Business models" means models representing core domain records with audit value (for example invoices, orders, payments). Apply `LogsActivity` to these models by default.

## Process

### 1. Configure Mass Assignment and Casting

- Set `protected $guarded = [];` on all business models; **do not** use `$fillable`.
- Implement a `casts()` method instead of a `$casts` property.
- In `casts()`, configure:
  - Enums, dates, decimals, and JSON fields with explicit types (e.g., `decimal:2`).

### 2. Add Activity Logging

- Use the `LogsActivity` trait on all business models that should be audited.
- Implement `getActivitylogOptions()` to:
  - Call `logAll()`.
  - Call `logOnlyDirty()`.
  - Call `dontSubmitEmptyLogs()`.

### 3. Define Relationships and Helpers

- Use typed return types on all relationship methods (`HasMany`, `BelongsTo`, etc.).
- Group related sections of the model with comment headers such as:
  - `// --- Relationships ---`
  - `// --- Status Helpers ---`
  - `// --- Activity Log ---`
- Keep domain-specific helper methods focused and clearly named (e.g., `isDraft()`, `isPaid()`).

### 4. Ensure Testability and Factories

- Create a corresponding factory for every model under `database/factories/`.
- Ensure factories cover required attributes and common state variants.
- Prefer explicit factory states for common statuses (`->draft()`, `->paid()`, `->archived()`) to match model helpers.

## Examples

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'   => Status::class,
            'due_date' => 'date',
            'amount'   => 'decimal:2',
        ];
    }

    // --- Relationships ---

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    // --- Status Helpers ---

    public function isDraft(): bool
    {
        return $this->status === Status::Draft;
    }

    public function isPaid(): bool
    {
        return $this->status === Status::Paid;
    }

    // --- Activity Log ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

## Checklists

### Execution Checklist

- [ ] Model uses `$guarded = []` and **does not** define `$fillable`.
- [ ] Casting is implemented via a `casts()` method, not a `$casts` property.
- [ ] Enums, dates, decimals, and JSON fields are explicitly cast.
- [ ] `LogsActivity` trait is added where auditing is required.
- [ ] `getActivitylogOptions()` is configured with `logAll()`, `logOnlyDirty()`, and `dontSubmitEmptyLogs()`.
- [ ] All relationship methods have correct typed return types.
- [ ] A matching factory exists in `database/factories/`.
- [ ] Business logic is extracted to Actions or Services instead of living directly in the model.

## Safety / Things to Avoid

- Using `$fillable` arrays instead of `$guarded = []`.
- Defining `$casts` as a property instead of a `casts()` method.
- Omitting the `LogsActivity` trait on business models that should be audited.
- Omitting return types on relationship methods.
- Creating a model without a corresponding factory.
- Putting complex business logic directly in the model — prefer Actions or Services.
- Defining model shape with `protected array $fillable = ['name'];` and `protected array $casts = ['status' => 'string'];` instead of `$guarded = []` and `casts()`

## References

- [Laravel Eloquent Models](https://laravel.com/docs/eloquent)
- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog/)
- Related: `Enums/SKILL.md` — enums are cast in `casts()`
- Related: `Migrations/SKILL.md` — migrations define the model's schema
