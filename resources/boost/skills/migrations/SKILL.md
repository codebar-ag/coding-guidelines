---
name: migrations
description: Database schema change files. Always create new files for changes — never modify existing migrations. Use descriptive names, proper foreign key constraints, and reversible `down()` methods.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Migrations

## When to Apply

- Apply for schema changes: tables, columns, indexes, foreign keys, constraints.
- Apply when rollout/rollback behavior must be explicit and versioned.
- Do not use for long-running data backfills or one-off data repair scripts.
- For zero-downtime rollout strategies, pair this skill with deployment/runbook guidance.

## Preconditions

- Database connection and target environment are known.
- Migration file naming follows timestamp prefix ordering.
- Risky operations (`dropColumn`, type changes, destructive renames) are reviewed with backup/rollback plan.

## Process

### 1. Create a New Migration File

- Never edit previously committed migration files.
- Use descriptive names, for example `add_status_to_invoices_table`.
- Generate with Laravel command:
  - `php artisan make:migration add_status_to_invoices_table --table=invoices`

### 2. Implement `up()` with Focused Changes

- Keep one concern per migration.
- Add indexes for foreign keys and common filters.
- Prefer string-backed status columns for enum-like states.

### 3. Implement Safe `down()` Reversal

- Reverse every structural change in `up()` where possible.
- Document irreversible/data-loss risks in comments and PR notes.
- Remember: dropping a column restores schema only; removed data is not recovered.

### 4. Validate Migration Lifecycle

- Run `php artisan migrate` and `php artisan migrate:rollback --step=1` locally.
- Confirm table timestamps behavior explicitly when removing timestamps:
  - `$table->dropTimestamps();` (drops `created_at` and `updated_at` together).

## Examples

```php
public function up(): void
{
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('status')->default('draft');
        $table->decimal('amount', 10, 2);
        $table->date('due_date');
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();

        $table->index('status');
    });
}

public function down(): void
{
    Schema::dropIfExists('invoices');
}
```

```php
// Adding a column to an existing table
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->string('reference')->nullable()->after('status');
    });
}

public function down(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropColumn('reference');
    });
}
```

```php
// Dropping timestamps explicitly (schema reversal only, not data recovery)
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropTimestamps();
    });
}

public function down(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->timestamps();
    });
}
```

## Checklists

- [ ] Migration is new (no edits to existing historical migrations).
- [ ] File name is descriptive and timestamp ordered.
- [ ] `up()` and `down()` are implemented and tested locally.
- [ ] Destructive operations include rollback and data-loss notes.
- [ ] Indexes/foreign keys are added for query and integrity needs.

## Anti-Patterns

- Modifying existing migration files instead of creating new ones
- Omitting the `down()` method or leaving it empty
- Not adding indexes on foreign key columns and frequently queried columns
- Creating a single migration that makes multiple unrelated schema changes
- Using integer status defaults when domain status is a named state (`draft`, `paid`, `cancelled`)
- Assuming `down()` can recover data that was dropped in `up()`

## References

- [Laravel Migrations](https://laravel.com/docs/migrations)
- [Laravel Migration Events and Commands](https://laravel.com/docs/migrations#running-migrations)
- `resources/boost/skills/models/SKILL.md` (schema-to-model alignment)
- `resources/boost/skills/enums/SKILL.md` (status value conventions)
