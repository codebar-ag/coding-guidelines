# Frontend Criteria Checklist

This document is an execution checklist for `FrontendImplementationAgent`.

Goal: launch `FrontendImplementationAgent` (or `ImplementationAgent` for mixed scope) and have it run the full frontend workflow end-to-end, not just adjust one or two classes.

## Agent to Launch

- Primary: `FrontendImplementationAgent`
- Supporting (if needed): `ImplementationAgent`, `ReviewAgent`, `DocumentationAgent`

## Skills to Load First

- Core frontend UI architecture: `design`, `tailwind`, `blade`
- Quality and consistency: `general`
- Domain-specific (based on touched files): `livewire`, `resources`, `translations`

Skill file references:

- `resources/boost/skills/design/SKILL.md`
- `resources/boost/skills/tailwind/SKILL.md`
- `resources/boost/skills/blade/SKILL.md`
- `resources/boost/skills/general/SKILL.md`
- `resources/boost/skills/livewire/SKILL.md`
- `resources/boost/skills/resources/SKILL.md`
- `resources/boost/skills/translations/SKILL.md`

## Copy-Paste Prompt (Run Full Checklist)

Use this when launching `FrontendImplementationAgent`:

> Act as **FrontendImplementationAgent** for this Laravel project.  
> Read and follow `DESIGN.md`, `AGENTS.md`, and the relevant skills in `resources/boost/skills/**/SKILL.md`.  
> Run the **entire Frontend Criteria Checklist** end-to-end:  
> 1. detect all changed/new UI surfaces and reusable frontend opportunities,  
> 2. create/update missing Blade components,  
> 3. enforce Tailwind CSS v4 and design-token usage,  
> 4. enforce component reuse across all touched views,  
> 5. verify accessibility and responsive states across mobile, tablet, and desktop,  
> 6. run required execution gates,  
> 7. return a final checklist report with pass/fail per item and remaining gaps.  
> Do not stop early after partial success.

## Frontend Criteria Checklist (Mandatory)

### 1) Scope Detection

- [ ] Identify all new/changed UI surfaces in the current change set (`resources/views/**`, component views, Livewire view files, and relevant frontend assets).
- [ ] Use a no-silent-skip rule: every touched UI file is listed in the final report, even if no visual changes were needed.
- [ ] Identify repeated UI fragments that should become reusable Blade components (candidate threshold: repeated in 2+ locations, or likely to repeat based on feature direction).
- [ ] Map each touched UI surface to either:
  - [ ] dedicated reusable Blade component
  - [ ] page-specific composition layer only (document why extraction is not yet justified)
- [ ] Confirm no changed surface is left without frontend and componentization review.

### 2) Baseline Frontend Rules

- [ ] Tailwind CSS **v4** conventions are applied throughout touched UI.
- [ ] Styling is utility-first in markup (avoid ad-hoc custom selector CSS for component styling).
- [ ] Frontend implementation is mobile-first with explicit responsive behavior (`sm:`, `md:`, `lg:`, etc.) where needed.
- [ ] Add responsive intent for each major touched surface (what changes across mobile, tablet, desktop and why).
- [ ] Maintain interaction parity across breakpoints; key actions should remain discoverable and operable at all target sizes.
- [ ] Dark mode variants are added where relevant for the feature context, or explicitly documented as intentionally not applicable.
- [ ] UI behavior is deterministic and state-focused (not just static appearance-focused).
- [ ] Do not remove existing reusable components without explicit approval.

### 3) Component-First Coverage Matrix (Dedicated Component Required)

Each touched UI type below must be reviewed for dedicated component extraction and reuse.

#### Blade Components (`resources/views/components/**`)

- [ ] Every repeated UI element is implemented as a Blade component.
- [ ] Semantic HTML is preserved during extraction (headings, lists, landmarks, buttons/links, form control semantics).
- [ ] Component API is consistent with `@props`, named slots, and `$attributes->merge([...])`.
- [ ] Variants and sizes are handled through component API (not duplicated markup blocks) using a consistent variant naming taxonomy.
- [ ] Components expose proper states (default/hover/focus/disabled/loading/error where relevant).

#### Blade Page Views (`resources/views/**`)

- [ ] Page views compose from components; they do not duplicate raw repeated element markup.
- [ ] Headings, buttons, inputs, badges, cards, alerts, and tables/lists use reusable components where possible.
- [ ] No repeated element pattern appears in multiple pages without component extraction.
- [ ] Tablet and desktop content density/readability is checked (line length, spacing rhythm, and visual hierarchy remain readable beyond mobile).

#### Livewire UI Views (`resources/views/livewire/**` or project path)

- [ ] Reused Livewire UI fragments are rendered via shared Blade components where possible.
- [ ] Livewire states (loading/dirty/error/empty) are represented with consistent reusable UI elements.
- [ ] No direct style drift between Blade-only and Livewire-driven surfaces for shared patterns.
- [ ] Shared interactions keep equivalent behavior across mobile/tablet/desktop (same state meaning, not necessarily identical layout).

#### Styleguide and Component Discovery (`/styleguide`, `config/styleguide.php`)

- [ ] Existing styleguide options were checked before creating new components.
- [ ] New reusable components are registered/discoverable where the project expects registration.
- [ ] Usage examples exist for non-trivial components or variants.

### 4) Tailwind v4 Token and Styling Rules (Mandatory)

- [ ] Use design tokens and `@theme` values before introducing one-off values.
- [ ] Hardcoded hex colors, arbitrary spacing/radius, and inline style overrides are avoided unless justified.
- [ ] If one-off values are used, document the reason and why existing tokens are insufficient.
- [ ] Repeated utility groups are extracted into components instead of copied between files.
- [ ] Class scanning/source coverage is correct for touched templates/paths.
- [ ] Tailwind setup remains compatible with latest v4 conventions in the project.

### 5) Accessibility and UX States (Mandatory)

- [ ] All interactive controls have visible `focus-visible` states.
- [ ] Touch targets for interactive controls follow minimum usable size (`min-h-[44px]` / `min-w-[44px]` where relevant).
- [ ] Icon-only controls provide `aria-label` or equivalent accessible name.
- [ ] Icons use Lucide (`<x-lucide-… />`, `lucide-vue-next`); brand logos use Simple Icons; no Font Awesome (see the `icons` skill).
- [ ] Disabled, empty, loading, success, and error states are visually and semantically represented where applicable.
- [ ] Keyboard navigation flow is preserved for updated interactions.
- [ ] Orientation/viewport edge cases are reviewed for key interactions (mobile portrait/landscape, tablet portrait/landscape, desktop baseline widths).

### 6) Reuse and Consistency Gates

- [ ] Shared element patterns are implemented once and reused everywhere possible in the touched scope.
- [ ] Naming conventions for component files, props, and variants are consistent.
- [ ] No duplicate components exist for the same UI responsibility.
- [ ] Translation/localization usage stays consistent for user-facing copy in reusable components.
- [ ] Any acceptable exception to reuse/consistency is explicitly documented in the final report.

### 7) Compatibility Guidance and Execution Gates

Compatibility is treated as a required quality guideline. It is not a separate hard fail gate beyond the project build/lint requirements.

- [ ] Frontend build passes (`npm run build` or project-equivalent build command).
- [ ] Relevant formatting/linting checks pass for touched frontend files.
- [ ] Manual responsive verification is completed and documented for mobile, tablet, and desktop breakpoints.
- [ ] Manual interaction verification is completed for focus/hover/disabled/loading/error states across target device classes.

### 8) Final Report Format (Required)

- [ ] Provide file-by-file and component-by-component frontend coverage summary.
- [ ] Provide checklist pass/fail status for every section above.
- [ ] Include evidence notes for key decisions (component extracted, component deferred, token exception, dark mode exception, compatibility notes).
- [ ] List exact remaining gaps (if any) and blocked reasons.
- [ ] If all items pass, explicitly state: `Full DESIGN.md checklist passed`.

### 9) Challenged Top 20 Improvements (Reference Tracker)

Use this section as a quick validation tracker to confirm all major improvements were applied to this checklist version.

- [ ] 1. Explicit touched-surface inclusion rules are defined.
- [ ] 2. No-silent-skip reporting rule is enforced.
- [ ] 3. Component extraction threshold is documented.
- [ ] 4. Page-specific composition exceptions require rationale.
- [ ] 5. Variant naming taxonomy consistency is required.
- [ ] 6. Responsive intent is documented per major surface.
- [ ] 7. Mobile/tablet/desktop layout checks are explicitly required.
- [ ] 8. Orientation and viewport edge-case review is included.
- [ ] 9. Interaction parity across breakpoints is addressed.
- [ ] 10. Dark mode applicability and exceptions are explicit.
- [ ] 11. Semantic HTML requirement is included in extraction rules.
- [ ] 12. Icon-only accessible naming requirement is explicit.
- [ ] 13. Tablet/desktop readability and density checks are added.
- [ ] 14. Blade/Livewire state consistency is reinforced.
- [ ] 15. Token-first styling rule is explicit.
- [ ] 16. One-off style exception documentation is required.
- [ ] 17. Styleguide discovery and registration expectations are explicit.
- [ ] 18. Localization consistency checks are preserved and clarified.
- [ ] 19. Final report evidence expectations are strengthened.
- [ ] 20. Known acceptable exceptions must be documented.
