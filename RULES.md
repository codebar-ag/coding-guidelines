# Rules Structure

This file defines the folder structure and conventions for skills in this package.

It serves as the index of coding-guideline skills for Laravel Boost and other AI assistants. Detailed rules live in the individual `SKILL.md` files, while `README.md` explains installation, high-level usage, and the full skill list.

## Package Layout

Skills live in `resources/boost/skills/{skill-name}/SKILL.md` — the path Laravel Boost discovers for vendor packages.

```
resources/boost/skills/
  general/           # Laravel conventions
  models/            # Eloquent models
  actions/           # Action pattern
  blade/             # Blade templates
  design/            # Component-first design system
  livewire/          # Livewire components
  tailwind/          # Tailwind CSS v4 styling
  translations/      # Translation and localization
  phpunit/           # PHPUnit tests
  saloon/            # Saloon API integrations
  docuware/          # DocuWare document management
  albatros/          # Albatros accounting API
  ...                # See README for full list
```

## How to Add a New Skill

1. Create `resources/boost/skills/{skill-name}/SKILL.md`
2. Add YAML frontmatter with `name` and `description` (required by [Agent Skills format](https://agentskills.io/what-are-skills)):

```markdown
---
name: my-skill
description: Brief description of when to use this skill
---

# Skill Title

## Rules
- Rule 1
- Rule 2

## Examples
...

## Anti-Patterns
...
```

3. Use lowercase kebab-case for folder names: `my-skill`
4. Keep skills concise with clear Rules, Examples, and Anti-Patterns

## Overriding Skills

Projects can override any skill by creating `.ai/skills/{skill-name}/SKILL.md` in their Laravel project. The local file takes precedence over the package default.

Use this package’s `resources/boost/skills/{skill-name}/SKILL.md` files as **defaults to read**, not files to edit in `vendor/`. For project-specific conventions:

- Prefer `.ai/skills/{skill-name}/SKILL.md` in your application.
- Adjust the `compatible_agents` frontmatter there to map skills to the agents from `AGENTS.md` (ArchitectAgent, ImplementationAgent, RefactorAgent, TestAgent, ReviewAgent).
