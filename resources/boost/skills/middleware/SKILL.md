---
name: middleware
description: HTTP request/response pipeline handlers that inspect, modify, or reject requests before or after they reach a controller. Used for authentication, throttling, header manipulation, and logging.
compatible_agents:
  - implement
  - refactor
  - review
---

# Middleware

## When to Apply

- Use for cross-cutting HTTP concerns: authentication gates, throttling, headers, request correlation IDs.
- Use when logic must run before controller execution or after response creation.
- Prefer group/global middleware for repeated behavior across many routes.
- Do not use for model authorization (Policies), request body validation (Form Requests), or domain business rules (Actions/Services).

## Preconditions

- Middleware class path exists: `app/Http/Middleware/`.
- Registration point is available in `bootstrap/app.php` (`->withMiddleware(...)` for Laravel 11+).
- The behavior is request/response pipeline logic, not model/domain behavior.

## Process

### 1. Create a Focused Middleware Class

- Use a specific name (`EnsureUserIsSubscribed`, `ForceJsonResponse`).
- Implement `handle(Request $request, Closure $next): Response`.
- Return early on rejection, otherwise call `$next($request)`.

### 2. Decide Scope: Global vs Group vs Route

- Global (`append`) for behavior required on every request.
- Group/alias for feature-level behavior reused across routes.
- Route-level only for exceptional one-off cases.

### 3. Register in `bootstrap/app.php`

- Register global or alias entries in `->withMiddleware(...)`.
- Apply aliases on route groups when possible.

### 4. Test Behavior

- Use feature tests with middleware enabled for expected responses.
- Use `$this->withoutMiddleware()` only when isolating unrelated controller behavior.

## Examples

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isSubscribed()) {
            return response()->json(['message' => 'Subscription required.'], 403);
        }

        return $next($request);
    }
}
```

```php
// Middleware that inspects request and modifies response.
public function handle(Request $request, Closure $next): Response
{
    // Before controller
    $request->headers->set('X-Request-Id', Str::uuid());

    $response = $next($request);

    // After controller
    $response->headers->set('X-Powered-By', 'MyApp');

    return $response;
}
```

```php
// Laravel 11+ registration in bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // Global middleware (all routes)
    $middleware->append(ForceJsonResponse::class);

    // Alias for grouped/route usage
    $middleware->alias([
        'subscribed' => EnsureUserIsSubscribed::class,
    ]);
})

// Group application (preferred over repeating route-level usage)
Route::middleware('subscribed')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});
```

## Checklists

- [ ] Middleware concern is cross-cutting HTTP behavior.
- [ ] Class is named for one clear responsibility.
- [ ] Scope decision (global/group/route) is explicit.
- [ ] Registration is present in `bootstrap/app.php`.
- [ ] Feature tests cover allowed and rejected flows.

## Anti-Patterns

- Putting business logic inside middleware (belongs in Actions or Services)
- Putting model-level authorization in middleware (belongs in Policies)
- Validating the request body in middleware (belongs in Form Requests)
- Accessing validated input inside middleware — middleware runs before validation
- Using middleware for things that only apply to a single route

## References

- [Laravel Middleware](https://laravel.com/docs/middleware)
- [Laravel HTTP Tests](https://laravel.com/docs/http-tests)
- `resources/boost/skills/policies/SKILL.md` (authorization boundaries)
- `resources/boost/skills/formrequests/SKILL.md` (request validation boundaries)
