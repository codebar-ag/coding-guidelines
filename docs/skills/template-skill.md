---
name: [skill-name]
description: [one-line description of when/why to use this skill]
---

# [Skill Title]

> **Purpose**: One-line summary of what this skill does and when to use it.
>
> **How to use this template**
>
> - Copy this file into `.cursor/skills/[skill-name]/SKILL.md` (or into your project’s preferred skills directory).
> - Replace all bracketed placeholders like `[Skill Title]`, `[tool-binary]`, `[main workflow trigger]`, and `[path/to/module]` with concrete values.
> - Trim optional sections (**Concept diagram**, **Variants**, **Troubleshooting**) if they are not needed for this workflow.
> - Keep each final skill focused on a single primary workflow to stay readable and maintainable.

## Concept diagram

High-level flow for authoring and using this kind of skill:

- (Optional) Add a mermaid diagram reference here, for example: `[path/to/skill-diagram.mmd]`.

## When to Apply

- **Primary use cases**:
  - After / during **[main workflow trigger]** (for example: completing a feature or bug fix).
  - When **[secondary trigger]** (for example: upgrading a dependency, level, or tool configuration).
- **Explicitly avoid**:
  - Do **NOT** run during unrelated tasks; only apply when this workflow is part of the current objective or has been explicitly requested.

## Preconditions

- **Environment**:
  - Project is checked out and dependencies installed.
  - Required tools are available (for example: `[tool-binary]`, `[runtime-1]`, `[runtime-2]`, `[package-manager]`).
- **Repository state**:
  - Working tree is clean or only contains changes relevant to this workflow.
  - On the correct branch (for example: `main`, `develop`, or the feature branch under review).
- **Configuration / tool setup**:
  - Any required config files are present (for example: `[tool-config].neon`, `.env`, `phpunit.xml`, `[tool-config]-baseline.neon`).
  - If a baseline or ignore configuration exists, it is committed and understood (do not assume it is safe to delete or rewrite).

## Process

Copy this section and replace placeholders for each workflow. Prefer **numbered sub-sections** for major phases.

### 1. Initial Analysis

Explain how to run the main tool in **read-only / analysis** mode and how to interpret the output.

```bash
# Example command (replace with real one)
[tool-binary] [subcommand] [path] --flag-1 --flag-2
```

- Capture and skim the full output to understand:
  - Error or finding categories.
  - Affected files, modules, or namespaces.
  - Whether a baseline or ignore configuration is already in place.
- If a baseline file exists, **respect it by default** – do not fix errors that are intentionally baselined unless explicitly requested.
- Focus first on **new** or **out-of-baseline** findings that were introduced by recent work.

### 2. Categorize Issues

Group findings or work items by type before fixing. Common categories:

- **Type or schema issues**: Missing / wrong param & return types, mismatched generics, invalid payload shapes.
- **Undefined references**: Missing methods, properties, variables, constants, routes, or services.
- **Dead code**: Unused variables, unreachable branches, obsolete functions or classes.
- **Logic issues**: Incorrect comparisons, impossible conditions, misuse of nullable or optional values.
- **Documentation mismatches**: Docblocks or comments contradicting actual signatures or behavior.

Decide which categories (and which paths/modules) are in-scope for this run. Prefer handling one or two categories or a single module per batch to keep diffs focused and reviewable.

### 3. Fix Iteratively

Work in **small, reviewable batches**, not all at once:

1. Pick a category (or a small set of files, modules, or namespaces).
2. Apply minimal, conservative fixes that respect existing project conventions.
3. Re-run the tool for only the affected scope when possible.

```bash
# Example: re-run only for a subset of paths
[tool-binary] [subcommand] [path/to/module] --flag-1 --flag-2
```

- After each batch, confirm that:
  - The total issue count for that scope is decreasing.
  - No new categories of issues have been introduced.
  - Fixes align with project coding standards and do not mix in unrelated refactors.
  - You are not introducing broad new ignores or baselines without clear justification.

### 4. Validate with Tests / Secondary Checks

Once the primary tool reports success (or acceptable remaining issues for the chosen scope):

- Run relevant automated tests or additional checks:

```bash
# Example commands – replace or trim as needed
[test-runner-or-secondary-tool]
```

- If specific tests or checks map directly to the changed code, run those first for faster feedback.
- Only consider the workflow complete when:
  - The primary tool is passing for the chosen scope (or remaining findings are explicitly accepted and documented), and
  - Tests or secondary checks are green for the affected areas.

## Checklists

Use these checklists to track progress during the workflow.

### Execution Checklist

- [ ] Preconditions verified (environment, repo state, configuration / tool setup).
- [ ] Initial analysis run and output reviewed (including any baselines or ignores).
- [ ] Issues categorized by type and in-scope paths/modules selected.
- [ ] Fixes applied iteratively in small, reviewable batches.
- [ ] Tool re-run after each batch until results are stable for the chosen scope.
- [ ] Tests / secondary checks executed and passing (or failures documented and accepted).

### Communication Checklist (Optional)

- [ ] Summarized what was run (commands, scope, and options).
- [ ] Noted any trade-offs (for example: findings intentionally ignored or baselined).
- [ ] Highlighted any follow-up work that should be scheduled separately.

## Variants (Optional)

Describe common variations of the workflow, for example:

- **Quick check**: Lightweight run over a narrow path for fast feedback.
- **Full project scan**: Comprehensive run across the entire codebase (more expensive; avoid during tight feedback loops).
- **CI mode**: Stricter flags or paths used in CI only.

Be explicit about when to choose each variant.

## Troubleshooting (Optional)

Common failure modes and how to address them:

- **Tool fails to start**:
  - Verify the binary exists and is executable.
  - Ensure dependencies are installed (for example: `[dependency-manager-install-command]`).
- **Configuration errors**:
  - Point to the relevant config file(s) and typical fixes.
- **Too many findings**:
  - Recommend creating / updating a baseline.
  - Narrow the scope to a single module or category for the current run.

## Safety / Things to Avoid

- Do **not** apply automatic fixes without reviewing the diff.
- Do **not** mix large refactors with unrelated cosmetic changes in the same batch.
- Do **not** silence errors via ignores or baselines unless there is a clear, documented justification.

## Example Summary Format

When reporting results back to the user, use a concise structure like:

- **Tool / mode**: `[tool-binary]` in `[quick check | full scan | CI mode]`.
- **Scope**: `[paths / modules]`.
- **Result**:
  - `[N] issues fixed`
  - `[M] issues remaining` (with brief explanation).
- **Follow-ups**: Short list of recommended next steps.
