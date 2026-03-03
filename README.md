# codebar Solutions AG Coding Guidelines

Shared Laravel coding and architecture guidelines for codebar-ag projects, optimized for AI-assisted development with Laravel Boost, Claude CI, and Claude Desktop/Terminal.

**Rule index:** See [RULES.md](RULES.md) for the full rule structure and file reference.

---

## Requirements

- **PHP:** ^8.4  
- **Laravel:** 12.x (Illuminate 12 components)  
- **Laravel Boost:** ^2.2 (installed automatically as a dependency)

---

## Installation

### 1. Require the package

In your Laravel project:

```bash
composer require codebar-ag/coding-guidelines --dev
```

This will also install `laravel/boost` as a dependency.

### 2. Install and sync Boost

```bash
php artisan boost:install
php artisan boost:update
```

Laravel Boost automatically discovers skills from `vendor/codebar-ag/coding-guidelines/resources/boost/skills/` when the package is installed.

---

## Usage

Once the package is installed and `boost:update` has run:

- **Laravel Boost** exposes all skills under `resources/boost/skills/**/SKILL.md` to AI assistants that integrate with Boost.
- **Claude CI** can use these skills and `RULES.md` when reviewing pull requests.
- **Claude Desktop & Claude Terminal (PhpStorm or other editors)** can follow the same guidelines when you point them at this package.

No extra configuration is required beyond installing the package and running the Boost commands.

---

## Multi‑agent usage with Laravel Boost

Laravel Boost acts as the **index** between your Laravel project and AI agents.  
This package provides the **skills and rules** for that index; the agents are *personas* you prompt in your tools.

See `AGENTS.md` for full details. In short, the current role set includes:

- **ArchitectAgent**: designs features (routes, models, actions, services, events).
- **BackendImplementationAgent**: implements backend code from approved designs.
- **FrontendImplementationAgent**: implements frontend-facing pieces from backend contracts.
- **ImplementationAgent**: handles small, self-contained full-stack changes.
- **RefactorAgent**: brings existing code into guideline compliance safely.
- **TestAgent**: improves tests and static analysis.
- **ReviewAgent**: reviews changes and proposes refactors (used in CI).
- **DocumentationAgent**: keeps docs and guidance aligned with the current architecture.

### Example prompts (Claude Desktop / Terminal)

Copy-paste and adapt these when talking to Claude Desktop/Terminal with Boost enabled. The examples below are common starting points; for full role coverage see `AGENTS.md`.

- **ArchitectAgent**

  ```text
  Act as ArchitectAgent for this Laravel project.

  Use RULES.md and the skills under resources/boost/skills/**/SKILL.md from codebar-ag/coding-guidelines.

  For this feature, especially apply the general, models, migrations, routing, actions, and services skills.
  Design the routes, models, actions, and services needed to implement this ticket:
  - [paste ticket or description]
  ```

- **ImplementationAgent**

  ```text
  Act as ImplementationAgent.

  Use RULES.md and the controllers, formrequests, actions, services, resources, blade, livewire, tailwind, and translations skills.

  Starting from this design, implement the feature without changing unrelated behaviour:
  - [paste design or current code]
  ```

- **RefactorAgent**

  ```text
  Act as RefactorAgent.

  Using the controllers, actions, services, models, and helpers skills, refactor this legacy code to follow the guidelines.
  Keep behaviour the same, work in small, reviewable steps, and explain each batch of changes briefly:
  - [paste code or file paths]
  ```

- **TestAgent**

  ```text
  Act as TestAgent.

  Apply the phpunit, pesttesting, phpstan, and dusk skills to improve tests and static analysis for these changes.
  Propose concrete test files and assertions, and any PHPStan configuration updates needed:
  - [paste diff or files]
  ```

- **ReviewAgent**

  ```text
  Act as ReviewAgent.

  Using RULES.md and all skills under resources/boost/skills/**/SKILL.md, review this diff and produce:
  1) a short assessment,
  2) a file‑grouped refactor plan, and
  3) a few copy‑pasteable suggestions.

  - [paste diff or PR description]
  ```


---

## What’s included

Conceptually, this package covers:

- **Laravel conventions**: routes, controllers, configuration, models, migrations, form requests, helper functions.
- **Backend architecture**: actions, services, DTOs, enums, events, exceptions, helpers, interfaces, jobs, middleware, observers, policies, requests, resources, traits.
- **Frontend & UI**: Blade templates, design system, Livewire components, Tailwind CSS, translations.
- **Testing & quality**: PHPUnit, Pest, PHPStan, Dusk and testing best practices.
- **External services**: Saloon integrations, DocuWare, Albatros and other service boundaries.

Each area is implemented as a dedicated skill so AI assistants can follow focused, repeatable guidance.

---

## Skills overview

Skills are reusable convention and workflow guides that assistants use when working with your codebase. Each skill includes rules, examples, anti‑patterns, and often a step‑by‑step process and checklists.

| Category | Skills | Description |
|----------|--------|-------------|
| **Laravel** | General, HelperFunctions, Models, Controllers, Migrations, Routing, FormRequests | Framework conventions, config/logging, Eloquent, routes, validation |
| **Backend** | Actions, Commands, DTO, Enums, Events, Exceptions, Helpers, Interfaces, Jobs, Middleware, Observers, Policies, Requests, Resources, Services, Traits | PHP architecture, single-purpose classes, API resources, queue jobs |
| **Frontend** | Blade, Design, Livewire, Tailwind, Translations | Templates, design system, components, Tailwind CSS, i18n |
| **Testing** | PHPUnit, PestTesting, PHPStan, Dusk | Unit/feature tests, static analysis, browser tests |
| **Services** | Saloon, DocuWare, Albatros | External API integrations (Saloon, DocuWare, Albatros) |

**Total: 37 skills.**

---

## Full guidelines

For a complete, human-readable view of the guidelines:

- Start with **[RULES.md](RULES.md)** for the overall structure, categories, and skill index.
- Drill into individual skills under `resources/boost/skills/{skill-name}/SKILL.md`:
  - Each skill defines when to apply it, core rules, examples, and anti‑patterns.
  - Newer skills (for example `phpstan`) follow a workflow-style template with sections like **When to Apply**, **Preconditions**, **Process**, **Checklists**, and **Troubleshooting**.

Projects can treat `RULES.md` as the canonical index and the `SKILL.md` files as detailed playbooks.

---

## Keeping guidelines up to date

Update the package and refresh Boost’s view of the skills with:

```bash
composer update codebar-ag/coding-guidelines
php artisan boost:update
```

---

## Project overrides

To customize a skill for your project, create a file at `.ai/skills/{skill-name}/SKILL.md`. Your local version takes precedence over the package default.

Example: override the Models skill:

```text
your-project/
├── .ai/
│   └── skills/
│       └── models/
│           └── SKILL.md    ← Your custom version
```

Project overrides can also adjust the `compatible_agents` frontmatter for a skill to fit your local workflows.  
See `RULES.md` for the agent/phase mapping and the `compatible_agents` convention.

---

## How it works

This package places skills in `resources/boost/skills/{skill-name}/SKILL.md`. Laravel Boost v2.2+ automatically discovers skills from vendor packages when you run `boost:update`. No custom sync commands or Laravel service providers are required—Boost handles discovery directly from the vendor path.

When you install this package via Composer, `laravel/boost` is automatically installed as a dependency, so you do not need a separate Composer require for Boost.

| Source | Path |
|--------|------|
| This package (default skills) | `vendor/codebar-ag/coding-guidelines/resources/boost/skills/` |
| Project overrides | `.ai/skills/{skill-name}/SKILL.md` |

In day-to-day application work you typically only touch:

- `RULES.md` (as a reference)
- Your own `.ai/skills/{skill-name}/SKILL.md` overrides
- The skills in this package for **reading** (not editing in `vendor/`)

Package internals such as `.github/workflows/skills-validation.yml` and `composer.json` are maintained here and normally do not need changes in consumer applications.

---

## Advanced AI integrations

### Claude CI (GitHub Actions)

You can create a GitHub Actions workflow that uses Claude to challenge your changes against these rules and produce a copy‑pastable refactor plan.

- Typical usage in a project:
  - Add a workflow such as `.github/workflows/claude-guidelines-review.yml` in your application repository.
  - Configure it to run on pull requests to `main` and via a `workflow_dispatch` manual trigger.
- Expected output: a Markdown comment on the PR with:
  - A short assessment of guideline alignment.
  - A step‑by‑step refactor plan grouped by file/area.
  - Optional code snippets or pseudo‑patches you can copy‑paste.

To enable it, configure the Anthropic API secret as described in **Anthropic secret configuration** and follow the setup instructions of the chosen Claude GitHub Action.

#### Anthropic secret configuration

The Claude workflow expects an Anthropic API key to be available as a GitHub secret:

- **Secret name (default)**: `ANTHROPIC_API_KEY`
- **Recommended location**: organization-level `Settings → Secrets and variables → Actions` so all repositories can reuse it.
- **Usage**: the workflow passes this secret to the Claude Code Action for authenticated calls to the Claude API.

If your company uses a different secret name convention, update the workflow input to match (search for `secrets.ANTHROPIC_API_KEY` in your copied `claude-guidelines-review.yml` workflow and adjust accordingly).

### Claude Desktop & Claude Terminal

When using Claude Desktop or Claude Terminal (for example inside PhpStorm), you can:

- Tell Claude that the project uses `codebar-ag/coding-guidelines`.
- Ask it to load and follow:
  - `RULES.md`
  - All skills under `resources/boost/skills/**/SKILL.md`
- Reference specific skills by name in your prompts, for example:
  - “Apply the `phpstan` and `phpunit` skills when fixing these tests.”
  - “Follow the `models` and `actions` skills when refactoring this feature.”

This makes local AI usage consistent with what runs in CI and via Boost.

### MCP (optional)

If your editor supports MCP (Model Context Protocol), configure it to use the Boost MCP server for full context. See the [Laravel Boost documentation](https://laravel.com/docs/boost) for your editor's setup.

---

## Claude-based skills validation

This package validates its own skills using Pest and the [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk):

- **Run locally**: `vendor/bin/pest --group=skills`
- **Environment**: Set `ANTHROPIC_API_KEY` and optionally `ANTHROPIC_MODEL` (default in `phpunit.xml.dist`: `claude-haiku-4-5`).
- **Retries**: optionally set `SKILL_VALIDATION_MAX_ATTEMPTS` (default: `3`) to control strict fail-after-retry behavior for temporary provider overloads.
- **PHPUnit config**: `phpunit.xml.dist` is committed; copy to `phpunit.xml` (gitignored) for local overrides.

The `.github/workflows/skills-validation.yml` workflow runs these checks on pushes and pull requests. Configure `ANTHROPIC_API_KEY` and `ANTHROPIC_MODEL` as GitHub secrets.

Under the hood, skills validation is performed by a small Laravel AI agent and supports both direct test execution and queued batch execution:

- **Per-skill tests**: `vendor/bin/pest --group=skills` runs one live Anthropic validation test per `SKILL.md` file (currently 37 tests), so failures are isolated by file.
- **Batch command**: `skills:validate` discovers each `resources/boost/skills/**/SKILL.md` file and dispatches (or runs with `--sync`) one validation job per file.
- **Logging**: each validation appends JSON lines to `storage/logs/skills-validation.log`, including the input markdown, structured output, usage/token metadata, provider status, retry attempt metadata, and output-quality audit fields.
- **Strict overload handling**: both per-skill tests and queue jobs retry bounded times and then fail explicitly when Anthropic remains overloaded.
- **Queue safety**: queued batch validation keeps queue-level retries/backoff in `ValidateSkillJob` in addition to bounded in-job retries for transient overloads.

CI now validates skills directly through the per-skill test dataset, while the queue command remains available for manual or maintenance batch runs.
