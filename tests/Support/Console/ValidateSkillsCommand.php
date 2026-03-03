<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Tests\Support\Console;

use CodebarAg\CodingGuidelines\Tests\Support\Jobs\ValidateSkillJob;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

final class ValidateSkillsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'skills:validate {--sync : Run validation jobs synchronously} {--model= : Override the Anthropic model to use}';

    /**
     * @var string
     */
    protected $description = 'Dispatch validation for each SKILL.md using Laravel AI.';

    public function handle(): int
    {
        $apiKey = env('ANTHROPIC_API_KEY');

        if (! $apiKey) {
            $this->warn('ANTHROPIC_API_KEY is not set; skipping AI-based skill validation.');

            return self::SUCCESS;
        }

        $root = dirname(__DIR__, 3);
        $skillsRoot = $root.'/resources/boost/skills';

        if (! is_dir($skillsRoot)) {
            $this->warn('resources/boost/skills directory not found; skipping skill validation.');

            return self::SUCCESS;
        }

        $logDir = $root.'/storage/logs';
        $logFile = $logDir.'/skills-validation.log';

        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($logFile, '');

        $finder = new Finder;
        $finder->files()->in($skillsRoot)->name('SKILL.md');

        $model = (string) ($this->option('model') ?: env('ANTHROPIC_MODEL', 'claude-3-5-sonnet@20240620'));
        $runSync = (bool) $this->option('sync');

        foreach ($finder as $file) {
            $relativePath = ltrim(str_replace($root.'/', '', $file->getPathname()), '/');

            if ($runSync) {
                (new ValidateSkillJob(
                    relativePath: $relativePath,
                    root: $root,
                    model: $model,
                ))->handle();
            } else {
                ValidateSkillJob::dispatch(
                    relativePath: $relativePath,
                    root: $root,
                    model: $model,
                );
            }
        }

        return self::SUCCESS;
    }
}
