---
name: documentation
description: Keep README, guidelines, and feature documentation in sync with the actual behaviour of the codebase, focusing on behaviour, examples, and how to apply the skills in practice.
---

# Documentation

**Name:** Documentation  
**Description:** Keep README, guidelines, and feature documentation in sync with the actual behaviour of the codebase, focusing on behaviour, examples, and how to apply the skills in practice.  
**Compatible Agents:** documentation, general-purpose, review  
**Tags:** docs, readme, guides, how-to, behaviour, examples

## When to Apply

- After implementing or refactoring a feature with **BackendImplementationAgent**, **FrontendImplementationAgent**, or **ImplementationAgent**.
- When the behaviour of a public API endpoint, console command, job, or user-facing screen changes.
- When adding a new pattern, skill, or workflow that other developers should follow.
- When onboarding material (`README.md`, `RULES.md`, or top-level docs) is out of date with current behaviour.

## Preconditions

- The relevant code changes are implemented or have at least a stable design from **ArchitectAgent**.
- You have access to:
  - `README.md`
  - `RULES.md`
  - Any feature or skill docs that describe the area being changed
- Tests or basic manual checks have confirmed the new or changed behaviour.

## Process

### 1. Identify the Behaviour to Document

- Summarize the change in 1–3 sentences:
  - What problem does it solve?
  - Who uses it (developer, end user, operator)?
  - What is the primary entry point (route, command, job, UI)?
- Locate the most relevant documentation home for this behaviour:
  - High-level or onboarding: `README.md`.
  - Project-wide rules or patterns: `RULES.md`.
  - Skill- or pattern-specific: the corresponding `resources/boost/skills/**/SKILL.md`.
  - Feature-specific: the closest existing doc file or a new one in the project’s docs area.

### 2. Update or Create Documentation

- Prefer **updating** existing sections over creating new, parallel explanations.
- Keep sections short and scannable:
  - Use clear headings and bullet points.
  - Lead with “what this is” and “when to use it”.
- Focus on:
  - **Inputs**: routes, request payloads, CLI flags, important configuration.
  - **Outputs**: responses, side effects (DB changes, events, emails, jobs).
  - **Constraints**: permissions, limits, feature flags, environment assumptions.
- Link to deeper technical details (code, skills) instead of duplicating them.

### 3. Add or Refresh Examples

- For backend features:
  - Provide at least one example request/response or CLI invocation.
  - Show the **happy path** and briefly mention notable error cases.
- For frontend features:
  - Show how a Blade view, Livewire component, or route is used.
  - Mention any important state, props, or events.
- Make examples:
  - Copy-pasteable where reasonable.
  - Minimal: avoid irrelevant noise or unrelated configuration.

### 4. Validate Against the Code

- Cross-check that the documentation matches the **current**:
  - Route names, HTTP methods, and URIs.
  - Request fields and validation rules.
  - Response structure or resource fields.
  - Flags, env vars, or configuration keys.
- If something is uncertain:
  - Prefer reading the code or tests over guessing.
  - If truly ambiguous, explicitly call it out as “behaviour depends on …” instead of inventing details.

### 5. Keep Changelogs and History Manageable

- Avoid narrating every refactor; focus on **observable behaviour** and how to use the system.
- When behaviour is intentionally changed:
  - Update the documentation as if it had always been correct.
  - Use commit messages or PR descriptions for historical context, not the docs themselves.

## Checklists

### Execution Checklist

- [ ] Identified which behaviours or features changed.
- [ ] Located the most appropriate docs to update (README, RULES, skill docs, feature docs).
- [ ] Updated or created sections with concise descriptions of behaviour and usage.
- [ ] Added or refreshed at least one practical example where helpful.
- [ ] Cross-checked docs against current routes, requests, responses, and configuration.

### Communication Checklist

- [ ] Summarized what changed in the docs (scope, files, and key points).
- [ ] Highlighted any new patterns or skills authors should follow.
- [ ] Noted any follow-up documentation that should be tackled separately (for example, larger guides or diagrams).

## Safety / Things to Avoid

- Do **not** describe internal implementation details that are likely to change unless they are critical for users.
- Do **not** duplicate large code snippets that will fall out of sync; prefer links or short, focused examples.
- Do **not** invent behaviour that does not exist in the code or tests.
- Do **not** mix unrelated documentation changes into the same batch; keep diffs focused on the current feature or area.

## Example Summary Format

When reporting documentation work back to the team, use a concise structure like:

- **Area**: `[feature / module / onboarding]`.
- **Docs touched**: `[README.md, RULES.md, path/to/feature-doc.md]`.
- **Changes**:
  - Updated behaviour for `[endpoint / screen / command]`.
  - Added examples for `[common task or workflow]`.
- **Follow-ups**: `[optional longer-form guides, diagrams, or cross-links to add later]`.

