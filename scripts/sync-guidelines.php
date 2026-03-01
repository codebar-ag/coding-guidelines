<?php

/**
 * Sync shared coding guidelines from the codebar-ag/coding-guidelines repo.
 *
 * Clones (first run) or pulls (subsequent runs) the repo directly into
 * the `guidelines/` directory at the project root.
 *
 * Fails gracefully if the repo is unreachable (e.g. network offline).
 */
$repo = 'https://github.com/codebar-ag/coding-guidelines.git';
$targetDir = 'guidelines';

if (is_dir($targetDir.'/.git')) {
    exec("git -C {$targetDir} remote set-url origin {$repo} 2>&1", $remoteOutput, $remoteExit);
    exec("git -C {$targetDir} fetch origin main --quiet 2>&1", $fetchOutput, $fetchExit);
    if ($fetchExit === 0) {
        exec("git -C {$targetDir} reset --hard FETCH_HEAD 2>&1", $resetOutput, $exitCode);
    } else {
        $exitCode = $fetchExit;
    }
} else {
    exec("git clone --depth 1 --quiet {$repo} {$targetDir} 2>&1", $output, $exitCode);
}

if ($exitCode !== 0) {
    echo 'Warning: Could not sync guidelines (repo not accessible).'.PHP_EOL;
    exit(0); // Don't break composer install
}

echo "Guidelines synced to {$targetDir}/.".PHP_EOL;

// Copy refactor.md to .cursor/commands/ for Cursor slash commands
$refactorSource = __DIR__.'/../refactor.md';
$commandsDir = '.cursor/commands';
$refactorDest = $commandsDir.'/refactor.md';

if (file_exists($refactorSource)) {
    if (! is_dir($commandsDir)) {
        mkdir($commandsDir, 0755, true);
    }
    if (copy($refactorSource, $refactorDest)) {
        echo "Refactor command copied to {$refactorDest}.".PHP_EOL;
    }
}
