# Cursor Laravel Rules

Shared Cursor AI rules for all codebar-ag Laravel projects.

## What This Is

A collection of `.mdc` rule files that Cursor reads automatically to follow our coding conventions. Rules are organized by category (testing, frontend, backend, laravel, services) and synced to each project via Composer.

## Setup

Add the following to your project's `composer.json`:

```json
"scripts": {
    "sync-cursor-rules": [
        "@php artisan cursor:sync-rules 2>/dev/null || php sync-cursor-rules.php 2>/dev/null || echo 'Run: composer sync-cursor-rules manually'"
    ],
    "post-install-cmd": [
        "@sync-cursor-rules"
    ],
    "post-update-cmd": [
        "@sync-cursor-rules"
    ]
}
```

Or use the one-liner sync script (add to `scripts` in `composer.json`):

```json
"sync-cursor-rules": [
    "@php -r \"$t=sys_get_temp_dir().'/codebar-cursor-rules'; @mkdir('.cursor/rules',0755,true); $c=is_dir($t)?exec('git -C '.$t.' pull -q 2>&1',$o,$x):exec('git clone --depth 1 -q git@github.com:codebar-ag/cursor-laravel-rules.git '.$t.' 2>&1',$o,$x); if($x===0){$i=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($t.'/rules',FilesystemIterator::SKIP_DOTS));foreach($i as $f){if($f->getExtension()==='mdc'){$rel=substr($f->getPathname(),strlen($t.'/rules/'));@mkdir('.cursor/rules/'.dirname($rel),0755,true);copy($f->getPathname(),'.cursor/rules/'.$rel);}}echo 'Cursor rules synced.'.PHP_EOL;}else{echo 'Warning: Could not sync Cursor rules.'.PHP_EOL;}\""
]
```

Then add to `.gitignore`:

```
/.cursor/rules
```

Run once to sync:

```bash
composer sync-cursor-rules
```

## Structure

See [RULES.md](RULES.md) for the full folder layout and how to add new rules.

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
