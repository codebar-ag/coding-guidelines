<?php

declare(strict_types=1);

use CodebarAg\CodingGuidelines\Tests\Support\Console\ValidateSkillsCommand;
use CodebarAg\CodingGuidelines\Tests\Support\Jobs\ValidateSkillJob;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\Finder\Finder;

it('validates each SKILL.md with Laravel AI', function (): void {
    $apiKey = env('ANTHROPIC_API_KEY');

    if (! $apiKey) {
        $this->markTestSkipped('ANTHROPIC_API_KEY is not set; skipping AI-based skill validation.');
    }

    $root = dirname(__DIR__, 2);
    $skillsRoot = $root.'/resources/boost/skills';

    if (! is_dir($skillsRoot)) {
        $this->markTestSkipped('resources/boost/skills directory not found; skipping skill validation.');
    }

    $finder = new Finder;
    $finder->files()->in($skillsRoot)->name('SKILL.md');

    Bus::fake();

    $command = new ValidateSkillsCommand;
    $command->setLaravel(app());
    $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput);

    $model = env('ANTHROPIC_MODEL', 'claude-3-5-sonnet@20240620');
    $expectedJobs = iterator_to_array($finder);

    Bus::assertDispatched(ValidateSkillJob::class, count($expectedJobs));

    foreach ($expectedJobs as $file) {
        $relativePath = ltrim(str_replace($root.'/', '', $file->getPathname()), '/');

        Bus::assertDispatched(ValidateSkillJob::class, function (ValidateSkillJob $job) use ($relativePath, $root, $model) {
            return $job->relativePath === $relativePath
                && $job->root === $root
                && $job->model === $model;
        });
    }
})->group('skills');
