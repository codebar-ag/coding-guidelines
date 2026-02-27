# Rules Structure

This file defines the folder structure and conventions for skills in this package.

## Package Layout

Skills live in `resources/boost/skills/{skill-name}/SKILL.md` — the path Laravel Boost discovers for vendor packages.

```
resources/boost/skills/
  general/           # Laravel conventions
  models/            # Eloquent models
  actions/           # Action pattern
  blade/             # Blade templates
  livewire/          # Livewire components
  phpunit/           # PHPUnit tests
  saloon/            # Saloon API integrations
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
