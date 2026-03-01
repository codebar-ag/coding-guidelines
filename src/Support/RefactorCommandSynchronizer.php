<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Support;

final class RefactorCommandSynchronizer
{
    public static function sync(string $projectRoot, string $packageRoot, mixed $io = null): bool
    {
        $source = $packageRoot.'/refactor.md';
        $commandsDir = $projectRoot.'/.cursor/commands';
        $destination = $commandsDir.'/refactor.md';

        if (! is_file($source)) {
            self::write($io, '<warning>[coding-guidelines]</warning> refactor.md not found, skipping sync.');

            return false;
        }

        if (! is_dir($commandsDir) && ! mkdir($commandsDir, 0755, true) && ! is_dir($commandsDir)) {
            self::write($io, '<warning>[coding-guidelines]</warning> Could not create .cursor/commands directory.');

            return false;
        }

        $sourceHash = hash_file('sha256', $source);
        $destinationHash = is_file($destination) ? hash_file('sha256', $destination) : null;

        if ($sourceHash === $destinationHash) {
            self::write($io, '<info>[coding-guidelines]</info> /refactor command already up to date.');

            return true;
        }

        if (! copy($source, $destination)) {
            self::write($io, '<warning>[coding-guidelines]</warning> Failed to copy refactor.md.');

            return false;
        }

        self::write($io, '<info>[coding-guidelines]</info> Synced /refactor command to .cursor/commands/refactor.md');

        return true;
    }

    private static function write(mixed $io, string $message): void
    {
        if (is_object($io) && method_exists($io, 'write')) {
            $io->write($message);
        }
    }
}
