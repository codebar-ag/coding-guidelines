---
name: events
description: Decoupled communication between application layers. Events are plain data objects describing what happened; listeners react to those events with a single, specific side effect.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Events & Listeners

## When to Use

- One completed business action should trigger multiple independent side effects.
- You need decoupling between the producer and consumers of a domain event.
- Listeners can run asynchronously without changing core business result.

## When Not to Use

- You only need one simple side effect; call the Action directly.
- The side effect must happen synchronously before returning control.
- Event chaining would obscure flow and make failures hard to reason about.

## Rules

- Event classes live in `app/Events/`; listener classes live in `app/Listeners/`.
- Events are plain data containers; keep business logic out of events.
- Name events in past tense: `InvoicePaid`, `UserRegistered`, `OrderShipped`.
- Each listener handles one reaction only.
- Name listeners by reaction intent: `SendInvoicePaidNotification`, `UpdateInventory`.
- Dispatch events from an Action after successful operation.
- Register listeners in `EventServiceProvider`.
- Use `ShouldQueue` for deferrable listeners and provide `failed()` for critical paths.

## Examples

```php
// Event — data container only
namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}
}
```

```php
// Listener — one reaction
namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Notifications\InvoicePaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendInvoicePaidNotification implements ShouldQueue
{
    public function handle(InvoicePaid $event): void
    {
        $event->invoice->user->notify(new InvoicePaidNotification($event->invoice));
    }

    public function failed(InvoicePaid $event, \Throwable $exception): void
    {
        Log::error('Invoice paid notification listener failed.', [
            'invoice_id' => $event->invoice->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
```

```php
// Dispatching from an Action after successful state change
namespace App\Actions;

use App\Events\InvoicePaid;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class MarkInvoiceAsPaid
{
    public function execute(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->update(['paid_at' => now()]);
            InvoicePaid::dispatch($invoice);
        });
    }
}
```

```php
// Registration — one event, many listeners
protected $listen = [
    InvoicePaid::class => [
        SendInvoicePaidNotification::class,
        UpdateAccountingRecords::class,
        NotifyAccountManager::class,
    ],
];
```

```php
// Anti-pattern: listener bundles multiple unrelated reactions
class HandleInvoicePaid implements ShouldQueue
{
    public function handle(InvoicePaid $event): void
    {
        $event->invoice->user->notify(new InvoicePaidNotification($event->invoice));
        app(UpdateAccountingRecords::class)->execute($event->invoice);
    }
}
```

## Checklist

- [ ] Event name is past tense and describes a completed fact.
- [ ] Event class contains data only.
- [ ] Each listener performs exactly one side effect.
- [ ] Deferred listeners implement `ShouldQueue`.
- [ ] Critical queued listeners implement `failed()`.
- [ ] Event dispatch happens from an Action, not directly from controller/model.

## Anti-Patterns

- Adding business logic inside an Event class
- Bundling multiple reactions in a single Listener (one listener, one reaction)
- Dispatching events from controllers or models directly — dispatch from Actions
- Using events for simple, single side effects that can be triggered directly
- Not implementing `failed()` on queued listeners for critical side effects
- Creating event chains that obscure control flow and make debugging hard

## References

- [Laravel Events](https://laravel.com/docs/events)
- Related: `Actions/SKILL.md` — events should be dispatched from Actions
- Related: `Observers/SKILL.md` — for model lifecycle reactions
