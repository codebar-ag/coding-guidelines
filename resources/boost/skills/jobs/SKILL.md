---
name: jobs
description: Queueable units of work for background processing. Jobs handle queue configuration and failure handling — they delegate business logic to Actions or Services.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Jobs

## When to Use

- A task should run outside the request/response cycle (emails, imports, webhooks, heavy calculations).
- The operation can tolerate retries and short execution delay.
- You need queue controls such as `$tries`, `$backoff`, `$timeout`, or queue selection.

## When Not to Use

- The operation must complete before returning the HTTP response.
- The operation is trivial and queue overhead is unnecessary.
- You need strict synchronous ordering in-process; use an Action/Service call instead.

## Preconditions

- Queue driver is configured (`QUEUE_CONNECTION` in `.env`).
- A queue worker is available in target environments (`php artisan queue:work`).
- The job delegates business logic to an existing Action or Service class.
- Payload data is serialization-safe (IDs/scalars/DTOs), not heavy service instances or closures.

## Principles

- Jobs own delivery concerns (queue, retries, timeout, failure hooks).
- Actions/Services own business rules and domain decisions.
- One job class should represent one clearly named unit of background work.

## Rules

- Place jobs in `app/Jobs/`.
- Use verb-noun names: `ProcessInvoicePayment`, `SendWeeklyReport`, `ImportProductCsv`.
- Implement `ShouldQueue` for asynchronous jobs.
- Use `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`.
- Declare queue behavior explicitly on the class (`$queue`, `$tries`, `$backoff`, `$timeout`).
- Use job middleware when needed (`WithoutOverlapping`, `RateLimited`, tenant/context middleware).
- Keep `handle()` thin and delegate to an Action or Service.
- Define `failed(Throwable $exception)` for permanent failure handling.
- Define dead-letter/failure strategy for critical jobs (for example failed_jobs monitoring, alerts, replay flow).
- Use fixed backoff for predictable transient failures; use exponential backoff for overloaded/unstable dependencies.
- Use chaining/batching intentionally for ordered pipelines or grouped bulk workflows.

## Examples

```php
namespace App\Jobs;

use App\Actions\ProcessInvoicePayment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessInvoicePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;
    public string $queue = 'invoices';

    public function __construct(
        private readonly Invoice $invoice,
    ) {}

    public function handle(ProcessInvoicePayment $action): void
    {
        $action->execute($this->invoice);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Invoice payment job failed permanently.', [
            'invoice_id' => $this->invoice->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
```

```php
// Dispatch from a controller
class InvoicePaymentController extends Controller
{
    public function __invoke(Invoice $invoice): JsonResponse
    {
        ProcessInvoicePaymentJob::dispatch($invoice);

        return new JsonResponse(['status' => 'queued'], 202);
    }
}
```

```php
// Dispatch from a listener
class QueueInvoicePayment
{
    public function handle(InvoiceApproved $event): void
    {
        ProcessInvoicePaymentJob::dispatch($event->invoice)
            ->delay(now()->addMinutes(5));
    }
}
```

```php
// Synchronous dispatch for tests or strict inline flow
ProcessInvoicePaymentJob::dispatchSync($invoice);
```

```php
// Backoff strategy examples
public int|array $backoff = 60; // fixed: retry every 60s
// public array $backoff = [10, 30, 90, 300]; // exponential-ish progression
```

```php
// Job middleware example
public function middleware(): array
{
    return [
        new WithoutOverlapping("invoice:{$this->invoice->id}"),
    ];
}
```

```php
// Chaining and batching examples
Bus::chain([
    new PrepareInvoiceDataJob($invoiceId),
    new ProcessInvoicePaymentJob($invoiceId),
])->dispatch();

Bus::batch([
    new ImportLineItemJob($rowA),
    new ImportLineItemJob($rowB),
])->dispatch();
```

## Checklist

- [ ] Job name clearly describes the background task.
- [ ] `handle()` delegates business logic to an Action/Service.
- [ ] Queue controls (`$tries`, `$backoff`, `$timeout`, `$queue`) are explicit.
- [ ] `failed()` captures actionable context (IDs, error message, alert/log).
- [ ] Dispatch location is intentional (controller, action, listener, scheduler).

## Anti-Patterns

- Putting business logic directly in a job's `handle()` method instead of delegating to an Action or Service
- Omitting `$tries`, `$backoff`, or `$timeout` and relying on global queue defaults
- Not implementing a `failed()` method for jobs that process critical data
- Using jobs for work that must complete synchronously before returning a response
- Using jobs for simple, fast operations that don't justify queue overhead
- Passing non-serializable/heavy payloads that break queue transport or inflate memory usage

## References

- [Laravel Queues](https://laravel.com/docs/queues)
- Related: `Actions/SKILL.md` — for the business logic jobs delegate to
- Related: `Services/SKILL.md` — for complex orchestration delegated from jobs
