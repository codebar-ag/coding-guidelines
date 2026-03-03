---
name: resources
description: API resource classes that transform Eloquent models into JSON-ready arrays. Resources control exactly what is exposed in API responses and handle relationships, conditional attributes, and date formatting.
compatible_agents:
  - implement
  - refactor
  - review
---

# Resources

## When to Apply

- Apply when API endpoints return Eloquent models or model collections as JSON.
- Apply when response fields must be explicitly whitelisted and conditionally included.
- Do not use for internal CLI output, file downloads, binary responses, or non-JSON rendering.

## Preconditions

- Resource classes can be created in `app/Http/Resources/`.
- Models and required relationships are loaded before resource transformation.
- Controller/service owns query execution; resource owns serialization only.

## Process

### 1. Create the Appropriate Resource Type

- Use `ModelResource` (for example `UserResource`) for single entities.
- Use `UserResource::collection(...)` for simple lists.
- Create a dedicated `ResourceCollection` only when custom top-level meta is required.

### 2. Build a Safe `toArray()` Contract

- Expose only fields needed by API consumers.
- Use `whenLoaded()` for relationships.
- Use `when()`, `whenHas()`, `whenNotNull()`, and `mergeWhen()` for conditional fields.

### 3. Keep Loading and Queries Outside Resources

- Eager-load in controllers/services before passing models to resources.
- Never trigger additional queries inside `toArray()`.

### 4. Verify Response Shape

- Check pagination and links by returning paginator instances directly.
- Confirm role/permission-based fields are hidden for unauthorized users.

## Examples

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'posts'      => PostResource::collection($this->whenLoaded('posts')),
            'secret'     => $this->when($request->user()->isAdmin(), $this->secret),
            'bio'        => $this->whenNotNull($this->bio),
            'created_at' => DateHelper::format($this->created_at),
        ];
    }
}
```

```php
// Critical: eager-load in controller before passing to resource.
return UserResource::collection(User::with('posts')->paginate());
```

```php
// Dedicated collection — only when custom meta is needed
class UserCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data'  => $this->collection,
            'links' => ['self' => route('users.index')],
        ];
    }
}
```

## Checklists

- [ ] Resource exposes only required public API fields.
- [ ] Relationships are eager-loaded before resource transformation.
- [ ] `toArray()` has no DB queries or lazy-load triggers.
- [ ] Conditional fields use `when*` helpers.
- [ ] Pagination meta is framework-provided, not manually duplicated.

## Anti-Patterns

- Exposing all model attributes without whitelisting
- Eager/lazy loading relationships inside `toArray()` (causes N+1 query issues)
- Using `if` statements inside the response array instead of `when()`, `whenLoaded()`, `whenNotNull()`
- Creating a dedicated collection class just for a simple list (use `UserResource::collection()`)
- Putting business logic or database queries in a resource
- Manually adding pagination meta that Laravel already provides automatically

## References

- [Laravel API Resources](https://laravel.com/docs/eloquent-resources)
- `resources/boost/skills/controllers/SKILL.md` (query/loading responsibilities)
- `resources/boost/skills/helpers/SKILL.md` (formatting helpers used in resources)
