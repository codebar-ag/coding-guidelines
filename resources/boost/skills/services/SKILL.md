---
name: services
description: Orchestration classes that coordinate multiple Actions, external APIs, or domain operations into a cohesive workflow. Services own transaction boundaries and third-party API integrations.
---

# Services

**Name:** Services  
**Description:** Orchestration classes that coordinate multiple Actions, external APIs, or domain operations into a cohesive workflow. Services own transaction boundaries and third-party API integrations.  
**Compatible Agents:** general-purpose, backend  
**Tags:** app/Services/**/*.php, laravel, php, backend, service, orchestration, api-integration

## When to Apply

- When coordinating **multi-step workflows** that span multiple models or domains.
- When wrapping **third-party SDKs or APIs** behind a stable internal interface.
- When you need to own transaction boundaries across several operations.
- When logic is reused from multiple controllers, jobs, or listeners and is **broader than a single Action**.

## Preconditions

- The Laravel project is configured with a database connection and, where needed, queue workers.
- Required Actions and models already exist or have clear designs.
- Third-party SDKs or HTTP clients (for example, Saloon) are installed and configured.

## Process

### 1. Decide Between Action and Service

- Use a Service when:
  - The workflow coordinates multiple Actions or external calls.
  - The logic spans multiple domains or aggregates several operations into a cohesive flow.
- Use an Action when the behavior is a **single, focused business operation**.

### 2. Design the Service

- Place the class in `app/Services/`.
- Name the Service after the domain or integration it serves:
  - Examples: `PaymentService`, `SubscriptionService`, `StripeService`.
- Avoid vague or generic suffixes such as `Manager` or `Handler`.
- Inject dependencies via the constructor:
  - Actions, repositories, external API clients, etc.

### 3. Implement Orchestration and Transactions

- For multi-step operations that must all succeed or fail together:
  - Wrap the logic in `DB::transaction()`.
- Call individual Actions or lower-level services inside the transaction where appropriate.
- Keep public methods cohesive and domain-focused (for example, `processOrderPayment`, `refund`).

### 4. Integrate External Services

- Wrap external SDKs and APIs behind Service classes with clean methods.
- Register external service wrappers as **singletons** in a service provider to centralize configuration.
- Keep HTTP-specific or SDK-specific details inside the Service; expose domain-centric methods to callers.

## Examples

```php
namespace App\Services;

use App\Actions\CreateInvoice;
use App\Actions\ChargePaymentMethod;
use App\Actions\SendPaymentConfirmation;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly CreateInvoice           $createInvoice,
        private readonly ChargePaymentMethod     $chargePaymentMethod,
        private readonly SendPaymentConfirmation $sendConfirmation,
    ) {}

    public function processOrderPayment(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $invoice = $this->createInvoice->execute($order);
            $payment = $this->chargePaymentMethod->execute($order, $invoice);
            $this->sendConfirmation->execute($payment);

            return $payment;
        });
    }

    public function refund(Payment $payment): void
    {
        // refund orchestration logic
    }
}
```

```php
// Registering an external service wrapper — AppServiceProvider
$this->app->singleton(StripeService::class, function () {
    return new StripeService(config('services.stripe.secret'));
});
```

```php
// Controller
class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, PaymentService $service): JsonResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));
        $payment = $service->processOrderPayment($order);

        return new JsonResponse(new PaymentResource($payment), 201);
    }
}
```

## Checklists

### Execution Checklist

- [ ] Confirmed the workflow spans multiple steps or domains and is appropriate for a Service.
- [ ] Created or updated a class under `app/Services/` with a domain-specific name.
- [ ] Injected required Actions, models, and API clients via the constructor.
- [ ] Wrapped multi-step operations that must succeed together in `DB::transaction()`.
- [ ] Exposed clear, domain-focused public methods (for example, `processOrderPayment`, `refund`).
- [ ] Registered external integrations as singletons in a service provider when needed.
- [ ] Controllers, jobs, or listeners delegate orchestration logic to the Service.

## Safety / Things to Avoid

- Using a Service for a single discrete operation (use an Action instead).
- Putting HTTP concerns (request, response) inside a Service.
- Naming a service `DataManager`, `UserHandler`, or other generic names instead of a domain-specific name.
- Putting model attribute or persistence logic in a Service that belongs in the Model.
- Putting authorization checks inside a Service (keep them in Policies, controllers, or middleware).
- Making raw HTTP calls directly instead of using the project’s standard integration layer (for example, Saloon).

## References

- [Laravel Service Container](https://laravel.com/docs/container)
- Related: `Actions/SKILL.md` — individual operations that Services orchestrate
- Related: `Saloon/SKILL.md` — the pattern for external API integrations
