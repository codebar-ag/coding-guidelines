---
name: actions
description: Single-purpose business logic classes that encapsulate one well-defined business operation. Actions are the primary location for business logic in Laravel applications, invoked from controllers, commands, or jobs.
---

# Actions

**Name:** Actions  
**Description:** Single-purpose business logic classes that encapsulate one well-defined business operation. Actions are the primary location for business logic in Laravel applications, invoked from controllers, commands, or jobs.  
**Compatible Agents:** general-purpose, backend  
**Tags:** app/Actions/**/*.php, laravel, php, backend, business-logic, action

## When to Apply

- When implementing or refactoring a **single business operation** that can be described in one sentence.
- When slimming **fat controllers, commands, or jobs** by moving business logic out of them.
- When multiple entry points (controller, command, job, listener) should reuse the **same business logic**.
- When aligning legacy code with the project’s pattern of **`app/Actions/` classes with `execute()`**.

## Preconditions

- The Laravel project is installed and bootstrapped.
- The `app/Actions/` directory exists (or will be created) and is autoloaded by Composer.
- Related models, notifications, events, and DTOs required by the action already exist or have a clear design.
- Authorization and validation rules are defined at the controller, policy, or Form Request level.

## Process

### 1. Decide if an Action is Appropriate

- Confirm the work is a **single business operation**, not multi-step orchestration across domains.
- Prefer an Action when:
  - The behavior will be reused from multiple places.
  - The behavior is business logic, not HTTP or infrastructure logic.
- If the behavior crosses multiple domains or requires complex orchestration, prefer a **Service** instead.

### 2. Design the Action

- Place the class in `app/Actions/`.
- Use a clear verb–noun naming pattern:
  - Examples: `CreateInvoice`, `SendPasswordResetEmail`, `ArchiveExpiredSubscriptions`.
  - Avoid vague names like `InvoiceAction`, `UserHandler`, `DataProcessor`.
- Plan for a **single public `execute()` method** that represents the operation.
- Keep the constructor for dependency injection only (repositories, services, helpers, etc.).

### 3. Implement the Action

- Implement `execute()` with the full business operation:
  - Perform any database writes and domain logic required for the operation.
  - Call other Actions or Services as needed, but keep the responsibility focused.
- Resolve the Action through the **service container** (type-hint it in controllers, commands, or jobs).
- Do **not**:
  - Include HTTP concerns (`Request`, `Response`, redirects) inside the Action.
  - Include cross-domain orchestration that belongs in a Service.
  - Put reusable formatting or generic utility logic here (use Helpers instead).

### 4. Integrate from Controllers, Commands, and Jobs

- In controllers:
  - Validate input via Form Requests.
  - Authorize via policies or `$this->authorize()`.
  - Inject the Action and call `execute()` with validated data or models.
- In console commands or queued jobs:
  - Resolve the Action from the container.
  - Iterate over models or DTOs and delegate the business operation to `execute()`.

## Examples

```php
namespace App\Actions;

use App\Models\Invoice;
use App\Models\Order;
use App\Notifications\InvoiceCreatedNotification;

class CreateInvoice
{
    public function __construct(
        private readonly GenerateInvoicePdf $generatePdf,
    ) {}

    public function execute(Order $order): Invoice
    {
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'amount'   => $order->total,
            'due_date' => now()->addDays(30),
        ]);

        $this->generatePdf->execute($invoice);

        $order->user->notify(new InvoiceCreatedNotification($invoice));

        return $invoice;
    }
}
```

```php
// Controller usage
class InvoiceController extends Controller
{
    public function store(StoreInvoiceRequest $request, CreateInvoice $action): JsonResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));
        $invoice = $action->execute($order);

        return new JsonResponse(new InvoiceResource($invoice), 201);
    }
}

// Command usage
class GenerateInvoicesCommand extends Command
{
    public function handle(CreateInvoice $action): int
    {
        Order::pending()->each(fn ($order) => $action->execute($order));

        return self::SUCCESS;
    }
}
```

## Checklists

### Execution Checklist

- [ ] Verified the behavior is a **single business operation** appropriate for an Action.
- [ ] Created or updated a class under `app/Actions/` with a clear verb–noun name.
- [ ] Implemented a single public `execute()` method for the operation.
- [ ] Kept the constructor for dependency injection only.
- [ ] Removed HTTP concerns from the Action (validation, requests, responses, redirects).
- [ ] Ensured authorization is handled in controllers, policies, or Form Requests.
- [ ] Updated controllers, commands, or jobs to **delegate to the Action** instead of duplicating logic.

## Safety / Things to Avoid

- Putting HTTP concerns (`Request`, `Response`, redirects) inside an Action.
- Creating multi-step orchestration across domains in a single Action (use a Service instead).
- Naming an Action vaguely, for example `InvoiceAction`, `UserHandler`, `DataProcessor`.
- Adding multiple `execute()` methods or extra public methods beyond the single operation.
- Adding business logic in a constructor — keep it in `execute()`.
- Performing database queries unrelated to the Action’s single responsibility.

## References

- [Laravel Service Container](https://laravel.com/docs/container)
- Related: `Services/SKILL.md` — for multi-domain orchestration
- Related: `Jobs/SKILL.md` — for deferring actions to the queue
- Related: `Controllers/SKILL.md` — for how controllers delegate to actions
