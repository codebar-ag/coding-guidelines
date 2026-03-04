---
name: models
description: Eloquent model conventions for mass assignment, casts, relationship naming, activity logging, and mandatory model tests (CRUD + relations).
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
- When reviewing models for consistency in relationships, helpers, activity logging, and model tests.

## When NOT to Apply

- For DTOs, value objects, and classes that are not persisted with Eloquent.
- For pivot-only structures that do not use a dedicated business model.

## Preconditions

- Laravel application and Eloquent are configured.
- Database schema and migrations for the model’s table exist or are being designed.
- `spatie/laravel-activitylog` is installed and configured for activity logging.
- "Business models" means models representing core domain records with audit value (for example invoices, orders, payments). Apply `LogsActivity` to these models by default.
- A factory exists (or is created) for the model and any related models used in tests.

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
- Follow Laravel relationship naming conventions:
  - Use singular names for single-record relations (`belongsTo`, `hasOne`, `morphOne`).
  - Use plural names for multi-record relations (`hasMany`, `belongsToMany`, `morphMany`).
- Method names must use `camelCase` based on the related model name (for example, `pipelineSteps()` for `PipelineStep`).
- Avoid generic relation names like `steps()`, `runs()`, `items()`, or `attachments()` when they hide model intent.
- Group related sections of the model with comment headers such as:
  - `// --- Relationships ---`
  - `// --- Status Helpers ---`
  - `// --- Activity Log ---`
- Keep domain-specific helper methods focused and clearly named (e.g., `isDraft()`, `isPaid()`).

### 3.1 Required Relationship Renames (Canonical Examples)

All relationship renames follow this convention: method name = `camelCase(RelatedModelName)` with singular/plural matching relation cardinality.

| Model | Old Method | New Method |
| --- | --- | --- |
| Pipeline | `steps()` | `pipelineSteps()` |
| Pipeline | `runs()` | `pipelineRuns()` |
| PipelineStep | `stepRuns()` | `pipelineStepRuns()` |
| PipelineRun | `stepRuns()` | `pipelineStepRuns()` |
| PipelineTemplate | `steps()` | `pipelineSteps()` |
| Inbox | `items()` | `inboxItems()` |
| Inbox | `serviceUsers()` | `inboxServiceUsers()` |
| Inbox | `importConfigs()` | `inboxImportConfigs()` |
| InboxItem | `importConfig()` | `inboxImportConfig()` |
| InboxItem | `sections()` | `inboxItemSections()` |
| ProviderType | `templates()` | `providerTypeTemplates()` |
| Prompt | `attachments()` | `promptAttachments()` |

### 4. Ensure Testability and Factories

- Create a corresponding factory for every model under `database/factories/`.
- Ensure factories cover required attributes and common state variants.
- Prefer explicit factory states for common statuses (`->draft()`, `->paid()`, `->archived()`) to match model helpers.

### 5. Write Mandatory Model Tests (CRUD + All Relations)

- Add a dedicated model test file under `tests/Unit/Models/` (or the project-standard model-test location).
- Use Pest syntax unless the code area is explicitly standardized on class-based PHPUnit.
- Cover all CRUD operations:
  - **Create**: persist model with factory and assert DB row exists.
  - **Read**: retrieve model and assert expected attributes/casts.
  - **Update**: change persisted data and assert DB reflects updates.
  - **Delete**: delete model and assert row is missing/soft-deleted as expected.
- Test every relationship method defined on the model:
  - Assert relation returns the correct relation class (`HasMany`, `BelongsTo`, etc.).
  - Assert related records can be created/attached through the relation.
  - Assert retrieval returns expected related models/count.
- Include at least one helper/cast assertion for domain behavior (for example `isDraft()` and enum/date casts).

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

    public function invoiceLines(): HasMany
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

```php
// tests/Unit/Models/InvoiceTest.php
use App\Enums\Status;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('supports invoice CRUD operations', function () {
    // Create
    $invoice = Invoice::factory()->create([
        'status' => Status::Draft,
        'amount' => '100.00',
    ]);

    expect($invoice->exists)->toBeTrue();
    $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'amount' => '100.00']);

    // Read + cast/helper checks
    $fresh = Invoice::query()->findOrFail($invoice->id);
    expect($fresh->status)->toBe(Status::Draft)
        ->and($fresh->isDraft())->toBeTrue();

    // Update
    $fresh->update(['amount' => '250.00']);
    $this->assertDatabaseHas('invoices', ['id' => $fresh->id, 'amount' => '250.00']);

    // Delete (supports soft deletes)
    $fresh->delete();
    $this->assertSoftDeleted('invoices', ['id' => $fresh->id]);
    // For hard-deleting models, use instead:
    // $this->assertDatabaseMissing('invoices', ['id' => $fresh->id]);
});

it('defines and resolves invoiceLines relation', function () {
    $invoice = Invoice::factory()->create();

    // Relation shape
    expect($invoice->invoiceLines())->toBeInstanceOf(HasMany::class);

    // Relation behavior
    InvoiceLine::factory()->count(2)->create(['invoice_id' => $invoice->id]);

    expect($invoice->invoiceLines)->toHaveCount(2)
        ->and($invoice->invoiceLines->first())->toBeInstanceOf(InvoiceLine::class);
});
```

## Checklists

### Execution Checklist

- [ ] Model uses `$guarded = []` and **does not** define `$fillable`.
- [ ] Casting is implemented via a `casts()` method, not a `$casts` property.
- [ ] Enums, dates, decimals, and JSON fields are explicitly cast.
- [ ] `LogsActivity` trait is added where auditing is required.
- [ ] `getActivitylogOptions()` is configured with `logAll()`, `logOnlyDirty()`, and `dontSubmitEmptyLogs()`.
- [ ] All relationship methods have correct typed return types.
- [ ] Relationship method names use `camelCase(RelatedModelName)` with correct singular/plural form.
- [ ] Existing generic relation names are renamed to explicit model-based names (for example, `steps()` -> `pipelineSteps()`).
- [ ] A matching factory exists in `database/factories/`.
- [ ] A model test exists and covers **Create, Read, Update, Delete** behavior.
- [ ] Every relationship method has at least one assertion for relation type and one for relation data retrieval.
- [ ] At least one cast/helper assertion validates domain behavior (for example enum or status helper).
- [ ] Business logic is extracted to Actions or Services instead of living directly in the model.

## Safety / Things to Avoid

- Using `$fillable` arrays instead of `$guarded = []`.
- Defining `$casts` as a property instead of a `casts()` method.
- Omitting the `LogsActivity` trait on business models that should be audited.
- Omitting return types on relationship methods.
- Using ambiguous relationship names that do not reflect the related model class.
- Creating a model without a corresponding factory.
- Creating or updating a model without adding/updating CRUD + relation tests.
- Testing only relation existence but not relation behavior (or vice versa).
- Putting complex business logic directly in the model — prefer Actions or Services.
- Defining model shape with `protected array $fillable = ['name'];` and `protected array $casts = ['status' => 'string'];` instead of `$guarded = []` and `casts()`

## References

- [Laravel Eloquent Models](https://laravel.com/docs/eloquent)
- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog/)
- Related: `Enums/SKILL.md` — enums are cast in `casts()`
- Related: `Migrations/SKILL.md` — migrations define the model's schema
- Related: `PestTesting/SKILL.md` — preferred style for model tests
- Related: `PHPUnit/SKILL.md` — class-based alternative where required
