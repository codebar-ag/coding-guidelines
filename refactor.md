# Codebase Refactor — Full Guidelines Compliance

You are refactoring a Laravel codebase to achieve 100% alignment with **codebar-ag coding guidelines**. This prompt is used when (a) newly adopting guidelines in an existing project, or (b) cross-checking AI-generated implementations for compliance.

---

## 1. Skill Discovery

Locate skills from one of these paths (check in order; first existing wins):

- `resources/boost/skills/` (when running inside this guidelines package repo)
- `vendor/codebar-ag/coding-guidelines/resources/boost/skills/`
- `guidelines/resources/boost/skills/`

If none exist, search the workspace for `**/resources/boost/skills/*/SKILL.md` and use the nearest matching root as the active skill source path.

Project overrides in `.ai/skills/{skill-name}/SKILL.md` take precedence over package defaults.

**Action:** List all `SKILL.md` files. For each skill, extract:

- `name` (from frontmatter or folder)
- `Tags` — the first path-like value (e.g. `app/Models/**/*.php`) is the glob for file discovery
- `Rules`, `Anti-Patterns`, `Examples` — apply these when analyzing and refactoring

---

## 2. Processing Order (Dependency-Aware)

Process skills in this order to avoid repeated edits and respect dependencies:

| Phase | Skills |
|-------|--------|
| Foundation | `general`, `php`, `helperfunctions` |
| Data layer | `migrations`, `enums`, `models`, `traits` |
| Backend core | `actions`, `services`, `dto`, `exceptions`, `interfaces` |
| HTTP layer | `formrequests`, `controllers`, `middleware`, `requests`, `resources`, `routing` |
| Infrastructure | `commands`, `jobs`, `events`, `observers`, `policies` |
| Frontend | `blade`, `design`, `livewire`, `tailwind`, `translations` |
| Integrations | `saloon`, `docuware`, `albatros` (only if matching files exist) |
| Helpers | `helpers` |
| Testing | `phpunit`, `pesttesting`, `phpstan`, `dusk` |

---

## 3. Per-Skill Workflow

For each skill in order:

1. **Match files** — Find all files in the workspace that match the skill’s glob patterns from Tags (e.g. `app/Http/Controllers/**/*.php`).
2. **Skip if empty** — If no files match (e.g. no Albatros service), skip the skill.
3. **Read skill** — Load the full `SKILL.md` content for Rules, Examples, and Anti-Patterns.
4. **Analyze** — Check each matching file against the Rules and Anti-Patterns.
5. **Refactor** — Fix violations using the skill’s Examples. Preserve behavior; do not introduce breaking changes.
6. **Batch edits** — Group edits by skill (e.g. all controller fixes in one pass).

---

## 4. Efficiency Directives

- **Incremental changes** — Prefer small, reviewable edits over large rewrites.
- **Prioritize when large** — If the scope is large, produce a prioritized compliance report and refactor in phases.
- **Cross-check mode** — When validating recent AI work, focus first on recently changed files, then broaden if needed.
- **Avoid redundant passes** — Process skills in the defined order so earlier fixes are not undone by later ones.

---

## 5. Output Format

### Phase 0: Plan and Confirm (mandatory)

Before making any code changes:

- Enter planning mode and produce a concrete implementation plan.
- Include: applicable skills, target files, prioritized violation groups, and intended edit batches.
- Present the plan to the user and request explicit approval.
- Do not edit files, run formatters, or execute write operations until the user confirms the plan.

### Phase A: Compliance Report (before edits)

Produce a concise report:

- Skills that apply (have matching files)
- Skills that are skipped (no matching files)
- Per skill: files with violations, brief description of each violation
- Summary: total violations, suggested commit grouping

### Phase B: Refactoring (skill-by-skill)

For each skill with violations:

1. State the skill name and what is being fixed.
2. Apply the changes.
3. Suggest a commit message for that batch of changes.

---

## 6. Tags-to-Glob Mapping

Skills use Tags like:

```
**Tags:** app/Models/**/*.php, laravel, php, backend, eloquent, model, database
```

Use the **first path-like Tag** (e.g. `app/Models/**/*.php`, `database/migrations/**/*.php`, `resources/views/**/*.blade.php`) as the glob. Some skills have multiple paths (e.g. `app/Events/**/*.php`, `app/Listeners/**/*.php`); include all path-like Tags for that skill.

---

## 7. Non-Breaking Refactors Only

- Do not change observable behavior.
- Do not remove or rename public APIs without explicit approval.
- Prefer additive fixes (add missing types, FormRequests, transactions) over removals.
- If a refactor is ambiguous or risky, report it and do not apply it automatically.

---

**Begin by discovering skills, then producing the compliance report, then executing refactors in the specified order.**
