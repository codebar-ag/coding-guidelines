# Test Criteria Checklist

This file is an execution runbook for `TestAgent`.

Goal: run the full testing checklist end-to-end and return objective PASS/FAIL evidence.

## Agent and Skills

- Primary role: `TestAgent`
- Supporting roles (only if needed): `BackendImplementationAgent`, `ReviewAgent`

Load these skills first:

- Core testing: `pesttesting`, `phpunit`, `phpstan`
- Quality and formatting: `php`, `general`
- Domain skills by touched files: `models`, `enums`, `jobs`

Skill references:

- `resources/boost/skills/pesttesting/SKILL.md`
- `resources/boost/skills/phpunit/SKILL.md`
- `resources/boost/skills/phpstan/SKILL.md`
- `resources/boost/skills/php/SKILL.md`
- `resources/boost/skills/general/SKILL.md`
- `resources/boost/skills/models/SKILL.md`
- `resources/boost/skills/enums/SKILL.md`
- `resources/boost/skills/jobs/SKILL.md`

## Copy-Paste Launch Prompt

Use this to launch `TestAgent`:

> Act as **TestAgent** for this Laravel project. Read and follow `TESTS.md`, `AGENTS.md`, and relevant skills in `resources/boost/skills/**/SKILL.md`. Execute the entire checklist in deterministic order, produce required evidence for each section, and do not silently skip any step. If blocked, mark `BLOCKED` with reason and remediation.

## Hard Pass/Fail Thresholds

The run is `PASS` only if all of the following are true:

- Test suite result: `0` failing tests.
- PHPStan result: `0` errors at **Level 9**.
- Pint result: `0` remaining formatting violations.
- Checklist result: no unresolved `FAIL` or `BLOCKED` items.

## No Silent Skip Policy

- Any step that cannot run must be marked `BLOCKED`.
- Every `BLOCKED` item must include:
  - blocking reason
  - impact
  - exact next remediation step
- Never report `PASS` if any item is `BLOCKED`.

## Deterministic Run Order

Run steps in this exact order:

1. Scope detection and class inventory.
2. Map classes to required test files.
3. Add/update missing tests by class type.
4. Run targeted tests for changed scope.
5. Run full test suite.
6. Run `vendor/bin/phpstan analyse` (Level 9).
7. Run `vendor/bin/pint`.
8. Produce final evidence report.

## Canonical Commands

- `php artisan test`
- `vendor/bin/phpstan analyse`
- `vendor/bin/pint`

Optional while iterating:

- `vendor/bin/pest`
- `vendor/bin/phpunit`

## Test Naming Convention (Dedicated Test Class Required)

Every touched class type requires a dedicated test file (no mega test files).

- `App\Models\Invoice` -> `tests/Unit/Models/InvoiceTest.php`
- `App\Actions\ProcessInvoice` -> `tests/Unit/Actions/ProcessInvoiceTest.php`
- `App\Services\BillingService` -> `tests/Unit/Services/BillingServiceTest.php`
- `App\Http\Controllers\InvoiceController` -> `tests/Feature/Http/Controllers/InvoiceControllerTest.php`
- `App\Http\Middleware\EnsureTenant` -> `tests/Feature/Http/Middleware/EnsureTenantTest.php`
- `App\Livewire\Invoices\CreateInvoice` -> `tests/Feature/Livewire/Invoices/CreateInvoiceTest.php`

## Coverage Dimensions (Minimum for Each Touched Class)

For each touched class, include as applicable:

- Happy path behavior
- Validation/guard path
- Failure/error path
- Authorization path
- Side effects and contract assertions

## Mandatory Checklist

### 1) Scope Detection

- [ ] Identify all new/changed PHP classes in current change set.
- [ ] Map each class to test type (`Unit` or `Feature`).
- [ ] Confirm no changed class is left without dedicated test coverage.

### 2) Baseline Rules

- [ ] Every new/changed class has dedicated automated tests.
- [ ] Prefer Pest syntax for new tests unless area is intentionally PHPUnit class-based.
- [ ] Use deterministic test setup (factories/states over ad-hoc inserts).
- [ ] Do not remove tests without explicit approval.

### 3) Coverage Matrix by Class Type

#### Models (`app/Models/*`)

- [ ] Dedicated model test file exists.
- [ ] CRUD covered (create/read/update/delete or soft delete).
- [ ] Casts/helpers asserted.
- [ ] Every relation tested for type and behavior.

#### Enums (`app/Enums/*`)

- [ ] Dedicated enum test file exists.
- [ ] All cases verified.
- [ ] `label()` and `color()` verified per case.
- [ ] Backward compatibility checked for persisted values.
- [ ] Model enum casting verified where used.

#### Jobs (`app/Jobs/*`)

- [ ] Dedicated job test file exists.
- [ ] Dispatch path tested.
- [ ] `handle()` delegation to Action/Service tested.
- [ ] Queue controls tested (`$tries`, `$backoff`, `$timeout`, `$queue`) where relevant.
- [ ] `failed()` behavior tested for critical jobs.

#### Actions (`app/Actions/*`)

- [ ] Dedicated action test file exists.
- [ ] Public contract (`execute` or equivalent) tested.
- [ ] Success/failure and side effects asserted.

#### Commands (`app/Console/Commands/*`)

- [ ] Dedicated command test file exists.
- [ ] Arguments/options matrix tested.
- [ ] Success/failure exit code assertions exist.
- [ ] Key console output assertions exist.

#### DTOs (`app/Data`, `app/DTO`, or project DTO path)

- [ ] Dedicated DTO test file exists.
- [ ] Construction/defaults/type invariants tested.
- [ ] Normalization/serialization round-trip tested.
- [ ] Invalid/edge input behavior tested where relevant.

#### Livewire Components (`app/Livewire/*`)

- [ ] Dedicated component test file exists.
- [ ] Render and interaction flow tested.
- [ ] Validation/authorization tested.
- [ ] Emitted events and persistence effects tested.

#### Notifications (`app/Notifications/*`)

- [ ] Dedicated notification test file exists.
- [ ] Channel routing tested (`mail`, `database`, `broadcast`, etc. as used).
- [ ] Payload/content contract tested.

#### Observers (`app/Observers/*`)

- [ ] Dedicated observer test file exists.
- [ ] Expected event-driven side effects tested.
- [ ] Explicit "no unintended side effects" assertion exists where relevant.

#### Policies (`app/Policies/*`)

- [ ] Dedicated policy test file exists.
- [ ] Method-by-method allow/deny matrix tested for relevant actor roles.

#### Providers (`app/Providers/*`)

- [ ] Dedicated provider test file exists.
- [ ] `register` bindings tested (binding/singleton resolution).
- [ ] `boot` behavior tested (macros/events/wiring where applicable).

#### Services (`app/Services/*`)

- [ ] Dedicated service test file exists.
- [ ] Public contract/orchestration tested.
- [ ] External dependencies are intentionally mocked/faked.
- [ ] Failure handling and side effects asserted.

#### Traits (`app/Traits/*` or project trait path)

- [ ] Dedicated trait test file exists.
- [ ] Trait tested via minimal fixture class.
- [ ] Method composition/collision behavior covered where relevant.

#### Controllers (`app/Http/Controllers/*`)

- [ ] Dedicated feature test file exists.
- [ ] Route/controller contract assertions cover status + payload shape.
- [ ] Validation/auth/authz/error paths covered.
- [ ] Side effects (DB/events/jobs/notifications) asserted where applicable.

#### Middleware (`app/Http/Middleware/*`)

- [ ] Dedicated middleware test file exists.
- [ ] Pass-through and blocked behavior covered.
- [ ] Matrix includes guest/authenticated/role/state combinations as relevant.
- [ ] Response effects asserted (redirect/status/headers/context).

### 4) Views and States (Mandatory)

Define and test all relevant states per changed view/component:

- [ ] `empty`
- [ ] `partialData`
- [ ] `fullData`
- [ ] `loading`
- [ ] `error`
- [ ] `unauthorized`
- [ ] `forbidden`
- [ ] `disabled`
- [ ] `archived`

Rules:

- [ ] Every changed view/template is called by tests (directly or through endpoint/component).
- [ ] A state matrix is explicitly documented in tests/comments.
- [ ] At least one key visible/behavior assertion exists per covered state.

### 5) Execution Gates

- [ ] Targeted tests for changed scope pass.
- [ ] Full test suite passes (`php artisan test`).
- [ ] PHPStan Level 9 passes (`vendor/bin/phpstan analyse`).
- [ ] Pint passes (`vendor/bin/pint`).

### 6) Required Evidence

Attach concise evidence for each run:

- [ ] Scope inventory (changed classes and mapped test files).
- [ ] Test results summary (targeted + full).
- [ ] PHPStan summary (Level 9, error count).
- [ ] Pint summary (violations/fixes).
- [ ] List of test files created or updated.

### 7) Final Report Template (Required)

Use this exact structure:

```text
Scope
- <changed class list>

Added/Updated Tests
- <test file list>

Checklist Status
- Section 1: PASS|FAIL|BLOCKED
- Section 2: PASS|FAIL|BLOCKED
- Section 3: PASS|FAIL|BLOCKED
- Section 4: PASS|FAIL|BLOCKED
- Section 5: PASS|FAIL|BLOCKED
- Section 6: PASS|FAIL|BLOCKED

Blockers
- <none or detailed blockers with remediation>

Final Verdict
- Full TESTS.md checklist passed
```
