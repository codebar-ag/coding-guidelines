---
name: livewire
description: Laravel Livewire conventions for building interactive UI without custom JavaScript. Components are PHP classes with reactive properties, computed properties, and event dispatching.
compatible_agents:
  - implement
  - refactor
  - review
---

# Livewire Components

## When to Use

- Building server-driven interactive UI with moderate complexity.
- Replacing jQuery/inline JS patterns with PHP-first reactive components.
- Creating forms, filters, paginated lists, and interactive CRUD flows.

## When Not to Use

- Fully client-heavy SPAs with complex offline/local state synchronization.
- Static pages without meaningful interactivity.
- Cross-team frontend architectures that already mandate a JS SPA stack.

## Preconditions

- `livewire/livewire` is installed and configured in the Laravel app.
- App layouts include Livewire scripts/styles as required by the project version.
- Component namespaces/paths follow project conventions (`app/Livewire`, `resources/views/livewire`).
- Validation, authorization, and domain actions are available for delegated business logic.

## Process Checklist

- [ ] Confirm the feature has one clear interactive responsibility.
- [ ] Keep domain/business logic in Actions/Services, not directly in UI methods.
- [ ] Use reactive public properties with explicit typing where practical.
- [ ] Use `#[Computed]` for derived values and avoid repeated query logic.
- [ ] Prefer `wire:model.live` or `wire:model.lazy` intentionally based on UX needs.
- [ ] Extract form objects and child components when complexity grows.

## Rules

- Keep component logic in the PHP class and templates declarative.
- Use one component per feature concern; split when responsibilities diverge.
- Use computed properties for derived/read models.
- Use events for cross-component coordination instead of tight coupling.

## Examples

```php
namespace App\Livewire;

use App\Actions\CreateInvoice;
use App\Models\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InvoiceList extends Component
{
    public string $search = '';

    #[Computed]
    public function invoices()
    {
        return Invoice::where('title', 'like', "%{$this->search}%")
            ->paginate(15);
    }

    public function delete(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $this->authorize('delete', $invoice);
        $invoice->delete();
    }

    public function render()
    {
        return view('livewire.invoice-list');
    }
}
```

```blade
{{-- Blade template — declarative, no logic --}}
<div>
    <input wire:model.live="search" type="text" placeholder="Search invoices...">

    @foreach ($this->invoices as $invoice)
        <div>
            {{ $invoice->title }}
            <button wire:click="delete({{ $invoice->id }})">Delete</button>
        </div>
    @endforeach

    {{ $this->invoices->links() }}
</div>
```

```php
// Livewire Form Object for complex forms
use Livewire\Form;

class InvoiceForm extends Form
{
    public string $title = '';
    public int $order_id = 0;
    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
```

```php
// Cross-component event dispatching
$this->dispatch('invoice-created', invoiceId: $invoice->id);

// Listening for events
#[On('invoice-created')]
public function refreshList(int $invoiceId): void { ... }
```

## Testing Guidance

- Use Livewire component tests for state transitions, validation, and emitted events.
- Add feature tests around critical rendered output and authorization constraints.
- Verify pagination, filters, and form submissions in at least one happy-path and one failure-path test.

## Anti-Patterns

- Writing custom JavaScript when Livewire can handle the interaction
- Putting display logic in the PHP class instead of the Blade template
- Growing a single component to handle multiple unrelated features
- Using `wire:model` without `.live` or `.lazy` when immediate reactivity is not needed (unnecessary requests)
- Not using form objects for forms with more than 3-4 fields

## References

- [Livewire Documentation](https://livewire.laravel.com/)
- Related: `Blade/SKILL.md` — Blade templates used by Livewire components
- Related: `Actions/SKILL.md` — Livewire components delegate business logic to Actions
