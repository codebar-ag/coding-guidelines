---
name: observers
description: Centralised classes that react to Eloquent model lifecycle events. Used for side effects that should always fire regardless of where a model is mutated — such as notifications, cache invalidation, and audit logging.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Observers

## When to Use

- A side effect must run on every model lifecycle mutation, regardless of call site.
- The behavior is model-centric (cache invalidation, audit trail, lightweight notifications).
- Multiple code paths mutate the same model and should trigger consistent reactions.

## When Not to Use

- Side effects should run only in one explicit workflow (use an Action).
- You need precise control over exactly when logic runs in a business transaction.
- The logic is complex orchestration (use a Service/Event pipeline).

## Rules

- Observer classes live in `app/Observers/`
- Naming: `PascalCase` with an `Observer` suffix, named after the model they observe: `UserObserver`, `InvoiceObserver`
- Only define the event methods you actually need — do not scaffold empty methods
- Register observers in `AppServiceProvider` using the `observe()` method
- Use observers for side effects tied to model lifecycle events that should always fire
- Never put business logic that should be explicitly controlled in an observer — use an Action instead

## Examples

```php
namespace App\Observers;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function created(User $user): void
    {
        $user->notify(new WelcomeNotification());
    }

    public function updated(User $user): void
    {
        Cache::forget("user:{$user->id}");
    }

    public function deleted(User $user): void
    {
        Cache::forget("user:{$user->id}");
    }
}
```

```php
// Registration in AppServiceProvider
use App\Models\User;
use App\Observers\UserObserver;

public function boot(): void
{
    User::observe(UserObserver::class);
}
```

```php
// Anti-pattern: observer used where explicit Action flow is required
class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if ($invoice->status === 'paid') {
            app(SendPaidInvoiceEmail::class)->execute($invoice);
        }
    }
}

// Better: explicit flow in Action for predictable control
class MarkInvoicePaid
{
    public function execute(Invoice $invoice): void
    {
        $invoice->update(['status' => 'paid']);
        app(SendPaidInvoiceEmail::class)->execute($invoice);
    }
}
```

**Available lifecycle events:**

| Method | Fires when... |
|---|---|
| `creating` | Before a model is first saved |
| `created` | After a model is first saved |
| `updating` | Before an existing model is saved |
| `updated` | After an existing model is saved |
| `saving` | Before creating or updating |
| `saved` | After creating or updating |
| `deleting` | Before a model is deleted |
| `deleted` | After a model is deleted |
| `restoring` | Before a soft-deleted model is restored |
| `restored` | After a soft-deleted model is restored |

`creating`, `updating`, `saving`, `deleting`, and `restoring` run before persistence and may cancel the operation by returning `false` when needed.

## Anti-Patterns

- Using an observer for side effects specific to one particular action (use the Action directly instead)
- Scaffolding empty observer methods for all lifecycle events when only one is needed
- Putting complex orchestration inside an observer (use a Service)
- Using an observer for validation (use Form Requests)
- Using an observer for authorization (use Policies)
- Relying on observers when you need fine-grained control over when side effects fire

## References

- [Laravel Observers](https://laravel.com/docs/eloquent#observers)
- [Laravel Model Events](https://laravel.com/docs/eloquent#events)
- Related: `Models/SKILL.md` — Eloquent model conventions
- Related: `Events/SKILL.md` — alternative for decoupled side effects
