---
name: requests
description: Dedicated Form Request validation classes for all controller input. Every endpoint that accepts user input must use a `FormRequest` class — validation never happens directly inside a controller.
compatible_agents:
  - implement
  - refactor
  - review
---

# Requests

## When to Use

- When HTTP endpoints accept client-provided input.
- When authorization and validation should be reusable and independently testable.
- When request payloads include nested structures or conditional fields.
- During implementation (new endpoints) and refactoring (moving inline validation out of controllers).

## When NOT to Use

- For endpoints with no input payload to validate.
- For internal jobs/actions that are not request-driven.
- For protocol-level checks that belong in middleware.

## Preconditions

- Standard Laravel `app/Http/Requests/` structure exists.
- Gate/Policy setup is available for authorization checks.
- Controllers are injecting Form Requests rather than validating inline.

## Rules

- Every controller action that accepts user input **must** use a dedicated `FormRequest` class
- Never call `$request->validate()` or `Validator::make()` inside a controller
- Place Form Requests in `app/Http/Requests/` or a subdirectory matching the domain (e.g. `Auth/`)
- Naming: `Store{Resource}Request`, `Update{Resource}Request`
- Define `authorize(): bool` with intentional access control
- Define `rules(): array` with a PHPDoc `@return` array shape annotation
- Use **array-based** rule definitions — not pipe-delimited strings
- Override `messages(): array` for user-friendly or localized error messages
- Treat `return true` in `authorize()` as explicit and safe only for genuinely public/non-sensitive operations
- Prefer FormRequest helpers over raw input access: `validated()`, `safe()`, and typed retrieval methods where needed

## Examples

```php
class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'due_date' => ['required', 'date', 'after:today'],
            'notes'    => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.exists'  => __('The selected order does not exist.'),
            'due_date.after'   => __('The due date must be a future date.'),
        ];
    }
}
```

```php
// Custom authorization logic options
public function authorize(): bool
{
    // Allow all authenticated users
    return $this->user() !== null;

    // Scope to admin role
    return $this->user()->isAdmin();

    // Delegate to a policy
    return $this->user()->can('update', $this->route('invoice'));
}
```

```php
// Nested + conditional validation example
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'lines' => ['required', 'array', 'min:1'],
        'lines.*.sku' => ['required', 'string'],
        'lines.*.quantity' => ['required', 'integer', 'min:1'],
        'internal_note' => Rule::when(
            $this->user()?->isAdmin() === true,
            ['nullable', 'string', 'max:500'],
            ['prohibited']
        ),
    ];
}
```

## Refactor Workflow (Inline Validation to FormRequest)

1. Move inline validation rules from controller to a new `FormRequest`.
2. Move authorization logic from controller conditionals to `authorize()`.
3. Inject the new `FormRequest` into the controller action signature.
4. Replace raw input usage with `$request->validated()`.
5. Add targeted tests for validation rules and authorization outcomes.

```php
// Before: controller owns validation and input concerns
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $project = Project::create($validated);

    return response()->json($project, 201);
}
```

```php
// After: controller delegates to FormRequest
public function store(StoreProjectRequest $request): JsonResponse
{
    $project = Project::create($request->validated());
    $meta = $request->safe()->only(['name']);

    return response()->json(['data' => $project, 'meta' => $meta], 201);
}
```

## Anti-Patterns

- Using `$request->validate([...])` inside a controller
- Using pipe-delimited rules: `'required|string|max:255'` instead of `['required', 'string', 'max:255']`
- Leaving `authorize()` as a passive `return true` without a comment explaining intent
- Not using a `messages()` method when default Laravel messages are unclear to end users
- Skipping the `@return` PHPDoc annotation on `rules()` (breaks PHPStan analysis)

## References

- [Laravel Form Requests](https://laravel.com/docs/validation#form-request-validation)
- Related: `Controllers/SKILL.md` — controllers that inject and use Form Requests
- Related: `Policies/SKILL.md` — policies referenced in `authorize()`
