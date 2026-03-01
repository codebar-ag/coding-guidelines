<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Console;

use CodebarAg\CodingGuidelines\Support\RefactorCommandSynchronizer;
use Illuminate\Console\Command;

final class SyncRefactorCommand extends Command
{
    protected $signature = 'guidelines:sync-refactor-command';

    protected $description = 'Sync .cursor/commands/refactor.md from the coding-guidelines package';

    public function handle(): int
    {
        $projectRoot = getcwd() ?: '.';
        $packageRoot = dirname(__DIR__, 2);

        $synced = RefactorCommandSynchronizer::sync($projectRoot, $packageRoot);

        if ($synced) {
            $this->info('Refactor command is ready at .cursor/commands/refactor.md');

            return 0;
        }

        $this->warn('Could not sync refactor command.');

        return 1;
    }
}
