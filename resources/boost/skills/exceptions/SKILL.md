---
name: exceptions
description: Named, meaningful failure states in your domain. Custom exceptions communicate precise failure reasons to callers and optionally carry domain-specific data.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Exceptions

## When to Use

- When a domain operation fails in a meaningful, reusable way (`InvoiceAlreadyPaidException`).
- When callers need a typed failure to branch behavior or produce a specific HTTP response.
- When failure context (IDs, entities, external references) must travel with the exception.

## When NOT to Use

- For normal request validation failures handled by Form Requests.
- For simple guard clauses where returning early is clearer than throwing.
- As generic wrappers that hide useful error context.
- For infrastructure transport failures that should stay in integration-layer exception types unless mapped intentionally.

## Exception Hierarchy

- Default custom domain exceptions should extend `RuntimeException`.
- Use a shared domain parent when multiple related failures exist (for example `BillingException`).
- Reserve framework/base exceptions for infrastructure and integration concerns.
- Keep hierarchy shallow: prefer one domain base + specific leaf exceptions; avoid deep inheritance trees.

## Rules

- Exception classes live in `app/Exceptions/`
- Custom exceptions represent **named, meaningful failure states** — not generic error wrappers
- Use a clear noun phrase describing what went wrong: `InvoiceAlreadyPaid`, `InsufficientFunds`, `PaymentGatewayUnavailable`
- Always suffix with `Exception`: `InvoiceAlreadyPaidException`
- Use named static constructors (`static for()`) to make throw sites readable
- Set the message via `parent::__construct()` inside the constructor
- Expose domain-specific context data as `public readonly` properties
- Implement `render()` directly on exceptions that always map to the same HTTP response
- Register exception rendering in `bootstrap/app.php` using `withExceptions()`
- Avoid `try-catch` blocks — let exceptions bubble up to the framework handler unless you must react locally
- Log unexpected exceptions; skip noisy reporting for expected domain exceptions via `dontReport`

## Examples

```php
namespace App\Exceptions;

use App\Models\Invoice;
use RuntimeException;

class InvoiceAlreadyPaidException extends RuntimeException
{
    public function __construct(
        public readonly Invoice $invoice,
    ) {
        parent::__construct(
            "Invoice #{$invoice->id} has already been paid and cannot be modified."
        );
    }

    public static function for(Invoice $invoice): static
    {
        return new static($invoice);
    }
}
```

```php
// Throwing — from inside an Action
class MarkInvoiceAsPaid
{
    public function execute(Invoice $invoice): void
    {
        if ($invoice->isPaid()) {
            throw InvoiceAlreadyPaidException::for($invoice);
        }

        $invoice->update(['paid_at' => now()]);
    }
}
```

```php
// Registering in bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (InvoiceAlreadyPaidException $e, Request $request) {
        return response()->json([
            'ok' => false,
            'message' => $e->getMessage(),
            'code' => 'invoice_already_paid',
        ], 422);
    });

    $exceptions->dontReport([
        InvoiceAlreadyPaidException::class,
    ]);
})
```

```php
// Catch + rethrow with domain context (do not swallow)
try {
    $this->gateway->capture($paymentIntentId);
} catch (GatewayException $exception) {
    throw PaymentGatewayUnavailableException::forIntent(
        paymentIntentId: $paymentIntentId,
        previous: $exception,
    );
}
```

```php
// Successful response shape in controller for contrast
return response()->json([
    'ok' => true,
    'data' => $invoiceResource,
], 200);
```

## Logging Guidance

- Expected domain exceptions: normally `dontReport` and map to clear client responses.
- Unexpected/infrastructure exceptions: report and log with context.
- If catching locally, either recover intentionally or log and re-throw.

## Testing Guidance

- Add feature tests for mapped HTTP responses (status, code, message) produced by custom exceptions.
- Add unit tests for static constructors (`for()`) to verify message and attached context properties.
- Add coverage for rethrow/mapping paths so integration failures preserve useful context.

## Anti-Patterns

- Creating generic exception wrappers instead of named domain exceptions
- Not using a named static constructor — verbose `new InvoiceAlreadyPaidException($invoice)` at every throw site
- Putting business logic or recovery logic inside an exception class
- Silently swallowing exceptions without logging or re-throwing
- Using built-in exceptions where a domain-specific exception would communicate intent better
- Creating an exception for cases already handled cleanly by validation or early returns

## References

- [Laravel Error Handling](https://laravel.com/docs/errors)
- Related: `Actions/SKILL.md` — the typical place where domain exceptions are thrown
- Related: `PHP/SKILL.md` — for error handling philosophy (avoid try-catch, let bubble)

## Review Checklist

- [ ] Class name uses `Exception` suffix
- [ ] Exception has a clear, domain-specific failure meaning
- [ ] Message is constructed in constructor or named constructor path
- [ ] Domain context is exposed through typed `public readonly` properties when needed
- [ ] Rendering/reporting behavior is intentional (`render`, `withExceptions`, `dontReport`)
