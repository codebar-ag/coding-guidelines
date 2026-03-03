---
name: traits
description: Reusable behaviour shared across multiple unrelated classes. Traits provide shared Eloquent scopes, accessors, lifecycle hooks, and small stateless helper methods.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Traits

## When to Apply

- Use when the same behavior is needed across multiple unrelated classes.
- Use for shared Eloquent scopes, lightweight accessors, and lifecycle hooks.
- Prefer composition/services when behavior needs dependencies or heavy orchestration.
- Do not use traits to avoid proper architecture decisions (single-class reuse or hidden business logic).

## Preconditions

- Trait file will live in `app/Traits/`.
- Behavior is self-contained and has one clear responsibility.
- If used on models, boot/init method names are unique and intentional (`bootTraitName`, `initializeTraitName`).

## Process

### 1. Confirm Trait Is the Right Abstraction

- Reuse must span multiple unrelated classes.
- If reuse is only one class, keep logic inline.
- If logic needs external services, use an Action/Service class instead.

### 2. Implement a Focused Trait

- Name by capability (`Archivable`, `Sluggable`) or coherent concern (`HasAddress`).
- Keep methods small and deterministic.
- Avoid hidden side effects outside the stated capability.

### 3. Handle Lifecycle Hooks Safely

- Use `bootTraitName()` for model event hooks.
- Use `initializeTraitName()` for default property setup.
- When stacking traits, ensure hook behavior remains predictable and does not duplicate writes.

### 4. Test Trait Behavior

- Add focused tests via a model or test double that uses the trait.
- Test positive and negative paths for scopes/hooks.

## Examples

```php
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Archivable
{
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
```

```php
// Counter-example: avoid business orchestration in traits.
trait InvoiceSettlementTrait
{
    public function settleInvoice(): void
    {
        // Bad: side effects, payment gateway, and notifications in trait.
        $this->paymentGateway->charge($this->invoice_total);
        Mail::to($this->user)->send(new InvoiceSettledMail($this));
    }
}
```

```php
trait Sluggable
{
    public static function bootSluggable(): void
    {
        static::creating(function ($model) {
            $model->slug = str($model->name)->slug();
        });
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
```

```php
// Usage across unrelated models
class Post extends Model { use Archivable; }
class Supplier extends Model { use Archivable; }

$post->archive();
Post::archived()->get();
```

```php
// Trait testing approach (feature/unit test setup)
$post = Post::factory()->create(['archived_at' => null]);
$post->archive();
$this->assertTrue($post->isArchived());
```

## Checklists

- [ ] Trait reuse is across multiple unrelated classes.
- [ ] Trait has one responsibility and no service dependencies.
- [ ] Lifecycle hooks use proper `bootTraitName` / `initializeTraitName` methods.
- [ ] Tests cover scopes/hooks and side effects.

## Anti-Patterns

- Creating a trait for logic that only exists in one class (keep it inline)
- Putting complex business rules in a trait (use an Action or Service)
- Putting external service dependencies in a trait (use a Service class)
- Using a trait to avoid inheritance instead of reconsidering the architecture
- Creating catch-all traits that bundle unrelated behaviour

## References

- [PHP Traits](https://www.php.net/manual/en/language.oop5.traits.php)
- [Laravel Model Booting](https://laravel.com/docs/eloquent#boot-and-initialize-traits)
- [Laravel Testing](https://laravel.com/docs/testing)
- `resources/boost/skills/models/SKILL.md` (trait usage in Eloquent models)
