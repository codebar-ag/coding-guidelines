---
name: tailwind
description: Tailwind CSS v4 styling conventions. Use when working with CSS, Tailwind utilities, or customizing the theme in Laravel projects.
compatible_agents:
  - implement
  - refactor
  - review
---

# Tailwind CSS

## When to Use

- Building or refactoring user-facing styles in Blade and Livewire templates.
- Implementing a design handoff with utility-first classes.
- Updating theme tokens and scales in `resources/css/app.css`.
- Standardizing responsive and dark mode behavior across components.

## When Not to Use

- Defining business logic or presentation flow (use Blade/Livewire skills).
- Building a fully separate design system process (defer to `Design/SKILL.md`).
- Creating one-off inline style overrides when a reusable utility composition is possible.

## Preconditions

- Tailwind v4 is installed and imported in `resources/css/app.css`.
- Vite is configured with `@tailwindcss/vite`.
- `@source` directives include relevant Blade/Livewire paths.
- The main CSS entrypoint is referenced by Vite in the app layout.

## Process Checklist

- [ ] Confirm Tailwind build setup works (`npm run dev` or `npm run build` succeeds).
- [ ] Implement styles with utility classes in markup, not custom class selectors.
- [ ] Use `@theme` for tokens (colors, fonts, spacing) instead of hardcoded duplicates.
- [ ] Add responsive variants (`sm:`, `md:`, `lg:`) where layout changes by viewport.
- [ ] Add `dark:` variants where dark mode behavior is required.
- [ ] Extract repeated utility groups into Blade components when class strings get long.

## Rules

- Tailwind utility classes only; avoid custom `.css` class selectors.
- Theme customization belongs in `@theme` within `resources/css/app.css`.
- Keep utility composition in markup and components, not in ad-hoc CSS files.
- Keep class scanning paths accurate via `@source` directives.

## Examples

```css
/* resources/css/app.css — theme customization via @theme */
@import "tailwindcss";

@theme {
    --color-primary-500: #3b82f6;
    --color-accent-500: #8b5cf6;
    --font-sans: "Inter", system-ui, sans-serif;
}

@source "../views/**/*.blade.php";
@source "../../app/Livewire/**/*.php";
```

```blade
{{-- Compose utilities in markup --}}
<div class="rounded-lg shadow-sm p-4 sm:p-6 lg:p-8 transition ease-in-out duration-150">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Title</h2>
</div>
```

```blade
{{-- Before: long repeated utility strings across views --}}
<button class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
    Save
</button>

{{-- After: extract to a reusable component --}}
<x-card class="p-4">
    {{ $slot }}
</x-card>
```

## Testing Guidance

- Run `npm run build` after style/token changes to catch invalid Tailwind usage.
- Validate responsive breakpoints and dark mode manually in browser devtools.
- For reusable components, verify class output in at least one feature/UI test path.

## Anti-Patterns

- Creating custom `.my-class { ... }` selectors in CSS files
- Using inline styles or `<style>` tags instead of Tailwind utilities
- Long, repeated utility strings in Blade views — extract to components
- Missing `@source` directives for Blade/Livewire paths (classes won't be scanned)
- Not using responsive prefixes when layout changes by viewport
- Hardcoding colors instead of using theme variables via `@theme`

### Common Pitfalls

- Purge/scanning misses because new template paths were not added to `@source`.
- Utility conflicts caused by copying legacy classes instead of using shared components.
- Theme drift from adding raw hex values in templates instead of token variables.

## References

- [Tailwind CSS v4 Documentation](https://tailwindcss.com/docs)
- [Vite Plugin](https://tailwindcss.com/docs/installation/framework-guides/vite)
- Related: `Design/SKILL.md` — design system and component conventions
- Related: `Blade/SKILL.md` — Blade template structure
