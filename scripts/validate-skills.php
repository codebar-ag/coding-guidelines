#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Validates all SKILL.md files in resources/boost/skills/.
 *
 * Each skill must have:
 * - YAML frontmatter with "name" and "description"
 * - Non-empty values for both fields
 *
 * Exits with code 1 if any skill is invalid.
 */
$skillsDir = __DIR__.'/../resources/boost/skills';
if (! is_dir($skillsDir)) {
    fwrite(STDERR, "Skills directory not found: {$skillsDir}\n");
    exit(1);
}

$skillDirs = glob($skillsDir.'/*', GLOB_ONLYDIR);
$errors = [];
$expectedCount = 36;

foreach ($skillDirs as $dir) {
    $skillFile = $dir.'/SKILL.md';
    $skillName = basename($dir);

    if (! file_exists($skillFile)) {
        $errors[] = "{$skillName}: Missing SKILL.md";

        continue;
    }

    $content = file_get_contents($skillFile);

    if (! preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
        $errors[] = "{$skillName}: Missing or invalid YAML frontmatter (must start with --- and end with ---)";

        continue;
    }

    $frontmatter = $matches[1];
    $parsed = [];
    foreach (explode("\n", $frontmatter) as $line) {
        if (preg_match('/^([a-z]+):\s*(.*)$/', trim($line), $m)) {
            $parsed[$m[1]] = trim($m[2], " \t\n\r\0\x0B\"'");
        }
    }

    if (empty($parsed['name'])) {
        $errors[] = "{$skillName}: Frontmatter must include 'name'";
    }

    if (empty($parsed['description'])) {
        $errors[] = "{$skillName}: Frontmatter must include 'description'";
    }
}

if (! empty($errors)) {
    foreach ($errors as $err) {
        fwrite(STDERR, "ERROR: {$err}\n");
    }
    exit(1);
}

$count = count($skillDirs);
if ($count !== $expectedCount) {
    fwrite(STDERR, "ERROR: Expected {$expectedCount} skills, found {$count}. Update README or add missing skills.\n");
    exit(1);
}

echo "✓ All {$count} skills validated successfully.\n";
exit(0);
