---
name: icons
description: Icon conventions. Lucide is the default icon set in every codebar project (Blade, Livewire, Vue); brand logos come from Simple Icons. Use when adding, replacing or reviewing icons. Font Awesome is not used.
compatible_agents:
  - implement
  - refactor
  - review
---

# Icons

## When to Use

- Adding an icon to a Blade view, Livewire or Flux component, Vue component or Nova resource.
- Replacing icons from another set (Font Awesome, Heroicons in new code).
- Storing an icon name as data (database column, enum, config).

## When Not to Use

- Illustrations, logos of the product itself and photos (use image assets).

## Preconditions

- Blade/Livewire: `mallardduck/blade-lucide-icons` (Blade Icons components `<x-lucide-{name} />`, `@svg('lucide-{name}')`).
- Vue: `lucide-vue-next` (`import { Search } from 'lucide-vue-next'`).
- Brand logos: `codeat3/blade-simple-icons` (`<x-si-{name} />`, CC0) or the Simple Icons SVG; Lucide has few brand icons and deprecates them.
- Font Awesome is **not** installed: no `@fortawesome/*`, no `owenvoke/blade-fontawesome`, no kit script, no `npm.fontawesome.com` registry, no `FONTAWESOME_NPM_AUTH_TOKEN`.

## Process Checklist

- [ ] Find the icon on [lucide.dev/icons](https://lucide.dev/icons/) and use its exact name.
- [ ] Size it with Tailwind (`size-4`, `size-5`), colour it with `text-*` (Lucide uses `currentColor`).
- [ ] Decorative icons get `aria-hidden="true"`; icon-only controls get an `aria-label` on the control.
- [ ] Icons stored as data hold the Lucide name (`map-pin`), never a CSS class string.

## Rules

- Lucide is the default. Other sets only for what Lucide does not have (brand logos → Simple Icons).
- Use components (`<x-lucide-… />`, Vue components), not icon fonts or copied SVG files.
- Store icon names, not markup or class strings, in the database; render them with `<x-dynamic-component :component="'lucide-'.$icon" />` or `svg('lucide-'.$icon)`.
- Don't add new Heroicons or Font Awesome usages; replace them when touching the code.
- No CDN icon scripts and no CSP entries for icon CDNs.

## Examples

```blade
{{-- Decorative icon next to text --}}
<span class="inline-flex items-center gap-2">
    <x-lucide-map-pin class="size-4 text-gray-500" aria-hidden="true" />
    {{ $location->name }}
</span>

{{-- Icon-only button --}}
<button type="button" aria-label="{{ __('Close dialog') }}" class="min-h-[44px] min-w-[44px] rounded-lg focus-visible:ring-2">
    <x-lucide-x class="size-5" aria-hidden="true" />
</button>

{{-- Icon name stored as data --}}
<x-dynamic-component :component="'lucide-'.$channel->icon" class="size-5" aria-hidden="true" />

{{-- Brand logo --}}
<x-si-instagram class="size-5" aria-hidden="true" />
```

```vue
<script setup>
import { Search } from 'lucide-vue-next'
</script>

<template>
  <Search class="size-4" aria-hidden="true" />
</template>
```

## Testing Guidance

- Feature/browser tests assert the rendered `<svg>` (or the component) where an icon carries meaning.
- A migration that rewrites stored icon names gets a test: old value in, Lucide name out.

## Anti-Patterns

- Font Awesome in any form (`<i class="fa-solid fa-…">`, `@fortawesome/*`, kit scripts, copied Pro SVGs or webfonts)
- Storing `far fa-unicorn`-style class strings in the database
- Icon-only buttons without an accessible name
- Hard-coded pixel sizes instead of Tailwind size utilities

## References

- [Lucide icons](https://lucide.dev/icons/)
- [blade-lucide-icons](https://github.com/mallardduck/blade-lucide-icons)
- [Simple Icons](https://simpleicons.org/)
- Related: `design/SKILL.md`, `blade/SKILL.md`, `tailwind/SKILL.md`
