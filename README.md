# Coding Guidelines

Shared Cursor AI rules for codebar-ag Laravel projects.

## What This Is

A collection of `.mdc` rule files that Cursor reads automatically to enforce coding conventions. Rules are organized by category and synced to each project via Composer. Unused rules stay dormant -- syncing everything everywhere is safe.

## Structure

See [RULES.md](RULES.md) for the full folder layout, category descriptions, and how to add new rules.

```
rules/
  testing/       phpunit.mdc, phpstan.mdc
  frontend/      blade.mdc, livewire.mdc, tailwind.mdc
  backend/       php.mdc, enums.mdc, dto.mdc, jobs.mdc
  laravel/       general.mdc, models.mdc, controllers.mdc, migrations.mdc, routing.mdc
  services/      saloon.mdc, docuware.mdc, albatros.mdc
```

## How It Works

- Rules with `globs` patterns only activate when matching files are open in Cursor
- Rules with `alwaysApply: true` apply to every conversation
- Service-specific rules (e.g. `docuware.mdc`) only activate in projects that have those files
- Syncing all rules to all projects is safe -- unused rules simply don't activate

## Use on Another Project

Follow these steps to add the shared guidelines to any Laravel project.

### Step 1 -- Create the sync script

Add `scripts/sync-guidelines.php` to your project root:

```php
<?php

/**
 * Sync shared coding guidelines from the codebar-ag/coding-guidelines repo.
 *
 * Clones (first run) or pulls (subsequent runs) the repo directly into
 * the `guidelines/` directory at the project root.
 *
 * Fails gracefully if the repo is unreachable (e.g. no SSH key configured).
 */
$repo = 'git@github.com:codebar-ag/coding-guidelines.git';
$targetDir = 'guidelines';

if (is_dir($targetDir.'/.git')) {
    exec("git -C {$targetDir} pull --quiet 2>&1", $output, $exitCode);
} else {
    exec("git clone --depth 1 --quiet {$repo} {$targetDir} 2>&1", $output, $exitCode);
}

if ($exitCode !== 0) {
    echo 'Warning: Could not sync guidelines (repo not accessible).'.PHP_EOL;
    exit(0); // Don't break composer install
}

echo "Guidelines synced to {$targetDir}/.".PHP_EOL;
```

### Step 2 -- Register the Composer scripts

Add (or merge) these entries into your `composer.json` `scripts` section:

```json
"scripts": {
    "sync-guidelines": [
        "@php scripts/sync-guidelines.php"
    ],
    "post-install-cmd": [
        "@sync-guidelines"
    ],
    "post-update-cmd": [
        "@sync-guidelines"
    ]
}
```

Guidelines will now sync automatically on every `composer install` and `composer update`.

### Step 3 -- Create the Cursor workspace rule

Create `.cursor/rules/guidelines.mdc` so Cursor always knows about the guidelines:

```markdown
---
description: References shared coding guidelines in the guidelines/ directory
alwaysApply: true
---

# Shared Coding Guidelines

This project has shared coding guidelines in the `guidelines/` directory
(synced from the codebar-ag/coding-guidelines repo).

Before making any code change, read and follow the relevant rule files:

- `guidelines/rules/testing/` -- PHPUnit, PHPStan conventions
- `guidelines/rules/frontend/` -- Blade, Livewire, Tailwind conventions
- `guidelines/rules/backend/` -- PHP typing, enums, DTOs, jobs conventions
- `guidelines/rules/laravel/` -- Models, controllers, migrations, routing conventions
- `guidelines/rules/services/` -- Saloon, DocuWare, Albatros API conventions

See `guidelines/RULES.md` for the full structure and index.
```

### Step 4 -- Update `.gitignore`

The `guidelines/` directory is a cloned repo and should not be committed to your project:

```
/guidelines
```

### Step 5 -- Run the sync

```bash
composer sync-guidelines
```

You should see `Guidelines synced to guidelines/.` on success.

### Quick checklist

- [ ] `scripts/sync-guidelines.php` exists
- [ ] `composer.json` has `sync-guidelines`, `post-install-cmd`, and `post-update-cmd` scripts
- [ ] `.cursor/rules/guidelines.mdc` exists with `alwaysApply: true`
- [ ] `/guidelines` is in `.gitignore`
- [ ] `composer sync-guidelines` runs without errors

## Repository Audit Prompt

Copy-paste the prompt below into **Cursor Agent** to analyze an entire repository against the coding guidelines and get a refactoring plan.

> **Tip:** Run this in a fresh Cursor Agent conversation for the cleanest results. Use **Plan mode** first to review findings before switching to Agent mode for refactoring.

---

### Audit only (report findings)

```
Read all rule files in guidelines/rules/ (testing, frontend, backend, laravel, services).

Then analyze the full repository against every rule. For each category, check:

- testing/   -- Scan tests/**/*.php against phpunit.mdc and phpstan.mdc
- frontend/  -- Scan resources/views/**/*.blade.php against blade.mdc,
                app/Livewire/**/*.php against livewire.mdc,
                resources/css/** against tailwind.mdc
- backend/   -- Scan app/**/*.php against php.mdc,
                app/Enums/**/*.php against enums.mdc,
                **/DataObjects/**/*.php against dto.mdc,
                app/Jobs/**/*.php against jobs.mdc
- laravel/   -- Scan app/Models/**/*.php against models.mdc,
                app/Http/Controllers/**/*.php against controllers.mdc,
                database/migrations/**/*.php against migrations.mdc,
                routes/**/*.php against routing.mdc,
                and check general.mdc for config/logging/formatting
- services/  -- Scan app/Services/**/*.php against saloon.mdc,
                app/Services/DocuWare/**/*.php against docuware.mdc,
                app/Services/Albatros/**/*.php against albatros.mdc

Produce a categorized report with:
1. Category name and rule file checked
2. Files that violate the rule (with line numbers)
3. What the violation is and what the rule expects
4. Severity: high (breaks conventions badly), medium (should fix), low (nice to have)

Sort findings by severity (high first). At the end, summarize total
violation counts per category and suggest a prioritized refactoring order.
```

---

### Audit and refactor

```
Read all rule files in guidelines/rules/ (testing, frontend, backend, laravel, services).

Analyze the full repository against every rule. For each category, check:

- testing/   -- Scan tests/**/*.php against phpunit.mdc and phpstan.mdc
- frontend/  -- Scan resources/views/**/*.blade.php against blade.mdc,
                app/Livewire/**/*.php against livewire.mdc,
                resources/css/** against tailwind.mdc
- backend/   -- Scan app/**/*.php against php.mdc,
                app/Enums/**/*.php against enums.mdc,
                **/DataObjects/**/*.php against dto.mdc,
                app/Jobs/**/*.php against jobs.mdc
- laravel/   -- Scan app/Models/**/*.php against models.mdc,
                app/Http/Controllers/**/*.php against controllers.mdc,
                database/migrations/**/*.php against migrations.mdc,
                routes/**/*.php against routing.mdc,
                and check general.mdc for config/logging/formatting
- services/  -- Scan app/Services/**/*.php against saloon.mdc,
                app/Services/DocuWare/**/*.php against docuware.mdc,
                app/Services/Albatros/**/*.php against albatros.mdc

For every violation found:
1. Report the file, line, violation, and what the rule expects
2. Fix the violation in place following the rule's conventions
3. After each category, confirm what was changed before moving on

Work through categories in this order: backend, laravel, testing, frontend, services.
Start with high-severity violations first within each category.
Do NOT modify database migrations that have already been run (check if a newer migration exists).
Run PHPStan after all changes to verify no new errors were introduced.
```

---

### Single-category audit

To audit just one category, use:

```
Read all rule files in guidelines/rules/backend/.
Scan app/**/*.php against each rule. Report every violation with file,
line number, what the rule expects, and fix it in place.
```

Replace `backend` and the scan path with any category from the structure above.
