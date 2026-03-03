---
name: blade
description: Laravel Blade template conventions covering components, output escaping, security, structure, and formatting.
compatible_agents:
  - implement
  - refactor
  - review
---

# Blade Templates

## When to Use

- Creating or refactoring server-rendered view templates in `resources/views/`.
- Building reusable UI fragments with Blade components.
- Rendering validated/authorized data from controllers, Livewire, and view models.

## When Not to Use

- Complex stateful interactions that should live in Livewire components.
- Heavy client-side application flows that require a dedicated SPA framework.
- Raw PHP templating where Blade directives provide clearer intent.

## Preconditions

- Laravel Blade is enabled (default Laravel app setup).
- If `x-*` Alpine directives are used, Alpine is loaded by the frontend build/layout.
- If `wire:*` directives are used, Livewire is installed and version-compatible.
- Data passed to views is already validated and authorized upstream.

## Process Checklist

- [ ] Decide whether this should be plain Blade, a Blade component, or Livewire.
- [ ] Keep markup declarative and move reusable blocks into components.
- [ ] Render user-provided values with `{{ }}` by default.
- [ ] Use `{!! !!}` only for trusted, pre-sanitized HTML.
- [ ] Keep scripts/styles out of Blade files unless explicitly required by framework conventions.
- [ ] Ensure text and labeling follow project language/content standards.

## Rules

- Use Blade components (`<x-component>`) for reusable UI.
- Prefer anonymous components for presentation-only pieces.
- Use class-based components when logic/DI is required.
- Use escaped output (`{{ }}`) by default.
- Avoid inline `<style>` and `<script>` tags in Blade templates.
- Use helper directives like `@class` and `@style` for conditional attributes.

## Examples

```blade
{{-- Escaped output — always use {{ }} for user data --}}
<h1>{{ $user->name }}</h1>
<p>{{ $post->excerpt }}</p>

{{-- Raw output — only for pre-sanitized content --}}
{!! $article->sanitizedBody !!}

{{-- Anonymous component --}}
<x-card>
    <x-slot:title>{{ $invoice->title }}</x-slot:title>
    <p>{{ $invoice->amount }}</p>
</x-card>
```

```blade
{{-- @class directive for conditional classes --}}
<div @class(['p-4 rounded', 'bg-green-100' => $active, 'bg-red-100' => $error])>
    {{ $message }}
</div>

{{-- @style directive for conditional styles --}}
<div @style(['color: green' => $success, 'color: red' => $failure])>
    {{ $text }}
</div>
```

```blade
{{-- Alpine.js via directive — no inline script tags --}}
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

```blade
{{-- Workflow example: convert repeated form markup into a component --}}
{{-- Step 1: Before --}}
<label for="email">Email</label>
<input id="email" name="email" type="email" value="{{ old('email', $user->email) }}">
@error('email') <p>{{ $message }}</p> @enderror

{{-- Step 2: After --}}
<x-form.input
    name="email"
    type="email"
    label="Email"
    :value="old('email', $user->email)"
/>
```

## Security Checklist

- [ ] User-controlled output uses `{{ }}` (escaped) and not raw output.
- [ ] Any `{!! !!}` usage is documented as sanitized/trusted.
- [ ] No inline scripts/styles that bypass normal frontend safeguards.
- [ ] Conditional attributes/classes do not expose sensitive state unintentionally.

## Testing Guidance

- Add/adjust feature tests for view-level behavior (form errors, conditional blocks, auth gating).
- Add component tests (or focused feature coverage) for reusable Blade components.
- Validate escaping behavior in tests when rendering user-provided content.

## Anti-Patterns

- Using `{!! $user->input !!}` for user-provided data — XSS vulnerability
- Adding `<style>` or `<script>` tags directly in Blade files
- Putting complex PHP logic directly in Blade templates (use components or Livewire)
- Using inconsistent indentation for `@if`, `@foreach`, `@while` blocks
- Hardcoding non-English text directly in Blade (use translation helpers for user-facing strings)

## References

- [Laravel Blade Templates](https://laravel.com/docs/blade)
- [Alpine.js](https://alpinejs.dev/)
- Related: `Livewire/SKILL.md` — interactive UI in Blade templates
- Related: `Design/SKILL.md` — component-first design system
