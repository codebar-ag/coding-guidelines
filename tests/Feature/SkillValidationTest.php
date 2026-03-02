<?php

declare(strict_types=1);

use CodebarAg\CodingGuidelines\Tests\Support\Agents\SkillQualityAgent;
use Laravel\Ai\Enums\Lab;
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

    foreach ($finder as $file) {
        $relativePath = ltrim(str_replace($root.'/', '', $file->getPathname()), '/');
        $absolutePath = $file->getPathname();
        $markdown = (string) file_get_contents($absolutePath);

        $model = env('ANTHROPIC_MODEL', 'claude-3-5-sonnet@20240620');

        $agent = new SkillQualityAgent;

        $response = $agent->prompt(
            prompt: <<<PROMPT
Validate the following SKILL.md file.

Relative path: {$relativePath}

SKILL.md contents:
------------------
{$markdown}
------------------
PROMPT,
            provider: Lab::Anthropic,
            model: $model,
        );

        /** @var array{
         *  skill_name: string,
         *  path: string,
         *  valid: bool,
         *  errors: array<int, string>,
         *  warnings: array<int, string>,
         *  improvements: array<int, string>
         * } $result
         */
        $result = [
            'skill_name' => $response['skill_name'],
            'path' => $response['path'],
            'valid' => (bool) $response['valid'],
            'errors' => $response['errors'],
            'warnings' => $response['warnings'],
            'improvements' => $response['improvements'],
        ];

        if ($result['errors'] !== []) {
            fwrite(
                STDERR,
                PHP_EOL.'Skill validation errors for '.$relativePath.':'.PHP_EOL.' - '.implode(PHP_EOL.' - ', $result['errors']).PHP_EOL
            );
        }

        if ($result['warnings'] !== [] || $result['improvements'] !== []) {
            fwrite(
                STDOUT,
                PHP_EOL.'Skill validation hints for '.$relativePath.':'.PHP_EOL.
                'Warnings:'.PHP_EOL.' - '.implode(PHP_EOL.' - ', $result['warnings'] ?: ['(none)']).PHP_EOL.
                'Improvements:'.PHP_EOL.' - '.implode(PHP_EOL.' - ', $result['improvements'] ?: ['(none)']).PHP_EOL
            );
        }

        expect($result['valid'])->toBeTrue()
            ->and($result['errors'])->toBe([]);
    }
})->group('skills');
