---
name: design
description: Component-first design system for Blade views. Use when creating UI components, designing responsive layouts, or implementing accessible interfaces.
compatible_agents:
  - implement
  - refactor
  - review
---

# Design System

## When to Apply

- Apply to all user-facing Blade views and shared UI components.
- Apply when creating new components or changing interaction/accessibility behavior.
- Apply both when implementing new UI and when incrementally refactoring legacy Blade screens.
- Do not apply to JSON APIs, console output, or plain-text emails.
- For Markdown/email template styling rules, use the relevant mail/documentation skill instead.

## Preconditions

- Existing components are reviewed in `/styleguide` first.
- Component registry updates are possible in `config/styleguide.php`.
- Target view is a Blade UI surface, not a backend-only endpoint.

## Process

### 1. Discover Existing Components First

- Check `/styleguide` before creating a new component.
- Reuse an existing component when it satisfies the same behavior.

### 2. Build/Update Components with Consistent Structure

- Use Blade components, not raw interactive HTML in page views.
- Distinguish concerns: Blade components define structure/composition; the design system defines visual tokens, states, spacing, and interaction rules.
- Accept `$attributes` and merge defaults with `$attributes->merge([...])`.
- Use named slots and `@props` defaults for composable APIs.

### 2.1 Legacy Adoption Strategy

- Do not rewrite whole legacy pages at once.
- Replace repeated UI fragments first (buttons, alerts, cards), then migrate larger sections.
- Keep old and new markup interoperable during rollout, but route all new UI through design-system components.

### 3. Apply Accessibility and Responsive Rules

- Ensure interactive controls meet touch target size (`min-h-[44px]` and often `min-w-[44px]`).
- Add visible focus (`focus-visible`) and disabled/hover states.
- Add `aria-label` or `sr-only` text for icon-only controls.
- Build mobile-first and provide card fallback for tabular content on small screens.
- Prefer design tokens over one-off values for spacing, radius, and colors.

### 4. Register and Verify

- Add new components to `config/styleguide.php`.
- Add/update styleguide examples so usage is discoverable.

## Examples

```blade
{{-- Anonymous component with $attributes and @props --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
])

<button
    {{ $attributes->merge(['class' => 'min-h-[44px] rounded-lg px-4 transition ease-in-out duration-150 focus-visible:ring-2 focus-visible:ring-accent-500']) }}
>
    {{ $slot }}
</button>
```

```blade
{{-- Bad -> Good: raw button replaced by design-system component --}}
{{-- Bad: <button class="px-4 py-2 bg-blue-600">Save</button> --}}
<x-button variant="primary">Save</x-button>
```

```blade
{{-- Composable layout with named slots --}}
<x-card>
    <x-slot:header>
        <x-heading level="2">{{ $title }}</x-heading>
    </x-slot:header>
    <x-slot:content>
        {{ $slot }}
    </x-slot:content>
</x-card>
```

```blade
{{-- Visual conventions: shadows, radius, color system --}}
<div class="rounded-lg shadow-sm p-6 transition ease-in-out duration-150">
    <span class="rounded-full bg-green-100 text-green-800 px-3 py-1">Active</span>
</div>
```

```blade
{{-- Icon-only button with ARIA label for screen readers --}}
<button type="button" aria-label="Close dialog" class="min-h-[44px] min-w-[44px] rounded-lg focus-visible:ring-2">
    <x-icon name="x" />
</button>
```

```blade
{{-- Mobile table-to-card fallback mini pattern --}}
<table class="hidden md:table w-full">...</table>
<div class="grid gap-3 md:hidden">
    <x-card>{{-- same row content as stacked key/value pairs --}}</x-card>
</div>
```

```blade
{{-- Prefer token classes instead of arbitrary values --}}
{{-- Good: bg-accent-600 text-on-accent rounded-md --}}
{{-- Avoid: bg-[#1248ff] text-white rounded-[7px] unless token is unavailable --}}
```

## Accessibility Testing

- Keyboard-test interactive flows (Tab/Shift+Tab/Enter/Space/Escape) after UI changes.
- Verify visible focus states in both light/dark themes where applicable.
- Run at least one automated pass (for example Axe/Lighthouse) and fix critical issues.
- Confirm icon-only controls, form fields, and landmark regions have accessible names.

## Checklists

- [ ] Existing `/styleguide` component options were checked first.
- [ ] New component (if any) is registered in `config/styleguide.php`.
- [ ] Interactive elements include focus, hover/disabled states, and accessibility labels.
- [ ] Mobile-first layout and responsive fallbacks are implemented.
- [ ] Design tokens were used before introducing one-off values.
- [ ] Accessibility checks (keyboard + automated scan) were completed.

## Anti-Patterns

- Using raw HTML elements instead of Blade components in page views
- Creating new components without checking the styleguide first
- Forgetting to add new components to `config/styleguide.php`
- Icon-only buttons without `aria-label` or `sr-only` alternative text
- Tables that do not fall back to stacked cards on mobile
- Missing `focus-visible` rings on interactive elements
- Using raw `<h1>`-`<h6>` instead of `<x-heading>`

## References

- [Laravel Blade Components](https://laravel.com/docs/blade#components)
- [Web Content Accessibility Guidelines (WCAG)](https://www.w3.org/WAI/standards-guidelines/wcag/)
- `resources/boost/skills/blade/SKILL.md` (required for Blade syntax and composition)
- `resources/boost/skills/tailwind/SKILL.md` (supplementary for utility class conventions)
