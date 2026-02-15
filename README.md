# Coding Guidelines

Shared Cursor AI rules for codebar-ag Laravel projects. See [RULES.md](RULES.md) for the full rule index.

## Install

From your Laravel project root:

1. `git clone --depth 1 git@github.com:codebar-ag/coding-guidelines.git guidelines`
2. Add `/guidelines` to your `.gitignore`
3. `mkdir -p .cursor/rules && cp guidelines/guidelines.mdc .cursor/rules/guidelines.mdc`

## Update

`git -C guidelines pull`

## Auto-update on Composer

Add this to your `composer.json` scripts to pull the latest guidelines on every `composer update`:

```json
"scripts": {
    "post-update-cmd": [
        "git -C guidelines pull --quiet 2>&1 || true"
    ]
}
```

## Audit & Refactor

Paste this into Cursor Agent to analyze and fix the entire repo against all rules:

```
Read all rule files in guidelines/rules/. Analyze the full repository against every rule.
For each violation: report the file, line, what the rule expects, then fix it in place.
Work category by category: backend, laravel, testing, frontend, services.
Do NOT modify migrations that have already been run. Run PHPStan after all changes.
```
