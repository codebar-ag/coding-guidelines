---
name: controllers
description: Thin HTTP entry points that validate input, delegate to Actions or Services, and return a response. Controllers contain no business logic.
compatible_agents:
  - implement
  - refactor
  - review
---

# Controllers

## When to Apply

- When creating or refactoring HTTP endpoints in `app/Http/Controllers/**`.
- When moving **business logic out of controllers** into Actions, Services, or Jobs.
- When ensuring API and web controllers follow consistent patterns for validation, authorization, and responses.
- When reviewing controllers for thinness, correct status codes, and proper delegation.

## Preconditions

- **Required:** Routing and controller setup exists (`routes/web.php`, `routes/api.php`).
- **Required:** Validation and authorization paths are defined (Form Requests, policies, or middleware).
- **Recommended:** Actions/Services for business logic are implemented before wiring the endpoint.
- **Recommended:** API/web response conventions (JSON vs views) are documented for the project.

## Process

### 1. Keep Controllers Thin

- Restrict controller responsibilities to:
  - Validating and authorizing incoming requests.
  - Delegating work to Actions, Services, or Jobs.
  - Returning appropriate HTTP responses (JSON or views).
- Remove any:
  - Direct database queries from controller methods.
  - Complex branching or multi-step domain logic.

### 2. Use Form Requests and Authorization

- Use dedicated `FormRequest` classes for **all validation**:
  - Avoid `$request->validate()` or `Validator::make()` directly in the controller.
- Enforce authorization at the controller level via:
  - `$this->authorize()` calls.
  - Form Request `authorize()` methods.
  - Route or controller middleware (e.g., `can:` or `auth`).

### 3. Design Controller Structure and Naming

- For single-action endpoints, use **invokable controllers** (`__invoke`):
  - Name them after the action: `StoreInvoiceController`, `ProcessWebhookController`.
- For resource controllers:
  - Keep each method thin and delegate to appropriate Actions or Services.
  - Only define the methods actually needed; prefer invokable controllers when only one or two actions exist.

### 4. Return Consistent Responses

- For API controllers:
  - Return JSON responses (e.g., `JsonResponse` or `Resource` / `ResourceCollection`).
  - Use appropriate HTTP status codes: 200, 201, 204, 403, 404, 422, etc.
  - Use explicit `JsonResponse` when status/header control matters; otherwise Laravel response casting is acceptable for simple payloads.
- For web controllers:
  - Return views or redirects, not JSON.
  - Keep view data preparation simple and delegated where possible.

### 5. Keep Controllers Testable

- Thin controllers push logic and most assertions into Action/Service tests.
- Controller tests should focus on HTTP contract: validation, authorization, status code, and response shape.

## Examples

```php
// Invokable single-action controller
class StoreInvoiceController extends Controller
{
    public function __invoke(StoreInvoiceRequest $request, CreateInvoice $action): JsonResponse
    {
        $invoice = $action->execute(
            orderId: (int) $request->validated('order_id'),
        );

        return new JsonResponse(new InvoiceResource($invoice), 201);
    }
}
```

```php
// Resource controller — thin, delegates to actions
class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        // Explicit JsonResponse keeps status/header behavior clear for API controllers.
        return new JsonResponse(InvoiceResource::collection(Invoice::paginate()), 200);
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $action): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $action->execute(orderId: (int) $request->validated('order_id'));

        return new JsonResponse(new InvoiceResource($invoice), 201);
    }

    public function destroy(Invoice $invoice, DeleteInvoice $action): JsonResponse
    {
        $this->authorize('delete', $invoice);
        $action->execute($invoice);

        return new JsonResponse(null, 204);
    }
}
```

## Checklists

### Execution Checklist

- [ ] Controller methods contain **no business logic** or direct database queries.
- [ ] All validation is handled by **FormRequest** classes, not inline in the controller.
- [ ] Authorization is enforced via policies, Form Requests, or middleware.
- [ ] Controllers delegate to Actions, Services, or Jobs for business operations.
- [ ] Invokable controllers are used for single-action endpoints where appropriate.
- [ ] Responses use correct types and HTTP status codes (JSON for API, views for web).

## Safety / Things to Avoid

- Calling `$request->validate()` directly in a controller (use a FormRequest).
- Writing database queries or heavy business logic directly in controller methods.
- Using resource controllers with seven methods when only one or two are needed (prefer invokable controllers).
- Ignoring HTTP status codes or returning incorrect codes for errors and success.
- Performing authorization inside Actions instead of at the controller level.

## References

- [Laravel Controllers](https://laravel.com/docs/controllers)
- Related (read first): `Actions/SKILL.md` — the delegation pattern used by thin controllers
- Related: `FormRequests/SKILL.md` — all input validation
- Related: `Policies/SKILL.md` — authorization enforced in controllers
