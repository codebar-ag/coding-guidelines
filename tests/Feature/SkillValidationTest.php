<?php

declare(strict_types=1);

use CodebarAg\CodingGuidelines\Tests\Support\Agents\SkillQualityAgent;
use CodebarAg\CodingGuidelines\Tests\Support\Validation\SkillValidationQuality;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Symfony\Component\Finder\Finder;

dataset('skills', function (): array {
    $root = dirname(__DIR__, 2);
    $skillsRoot = $root.'/resources/boost/skills';

    if (! is_dir($skillsRoot)) {
        return [];
    }

    $finder = new Finder;
    $finder->files()->in($skillsRoot)->name('SKILL.md');

    $dataset = [];

    foreach ($finder as $file) {
        $relativePath = ltrim(str_replace($root.'/', '', $file->getPathname()), '/');
        $dataset[$relativePath] = [$relativePath];
    }

    return $dataset;
});

beforeAll(function (): void {
    $root = dirname(__DIR__, 2);
    $logDir = $root.'/storage/logs';
    $logFile = $logDir.'/skills-validation.log';

    if (! is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    file_put_contents($logFile, '');
});

it('validates SKILL.md via Laravel AI: :relativePath', function (string $relativePath): void {
    $apiKey = env('ANTHROPIC_API_KEY');

    if (! $apiKey) {
        $this->markTestSkipped('ANTHROPIC_API_KEY is not set; skipping AI-based skill validation.');
    }

    $root = dirname(__DIR__, 2);

    $absolutePath = $root.'/'.$relativePath;

    if (! is_file($absolutePath)) {
        $this->markTestSkipped('Skill file not found: '.$relativePath);
    }

    $markdown = (string) file_get_contents($absolutePath);
    $model = env('ANTHROPIC_MODEL', 'claude-haiku-4-5');
    $agent = new SkillQualityAgent;
    $maxAttempts = max(1, (int) env('SKILL_VALIDATION_MAX_ATTEMPTS', 3));
    $retryBackoffMs = [1000, 2000, 4000];
    $response = null;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
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

            break;
        } catch (ProviderOverloadedException $exception) {
            $status = $attempt < $maxAttempts ? 'overloaded_retrying' : 'overloaded_failed';
            $overloadLogEntry = [
                'timestamp' => date(DATE_ATOM),
                'path' => $relativePath,
                'provider' => 'anthropic',
                'model' => $model,
                'status' => $status,
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'valid' => false,
                'errors' => [$exception->getMessage()],
                'warnings' => [],
                'improvements' => [],
                'markdown' => $markdown,
                'quality' => null,
            ];

            file_put_contents(
                $root.'/storage/logs/skills-validation.log',
                json_encode($overloadLogEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );

            if ($attempt >= $maxAttempts) {
                $this->fail(
                    sprintf(
                        'AI provider overload after %d attempts for %s (model: %s).',
                        $maxAttempts,
                        $relativePath,
                        $model
                    )
                );
            }

            $backoffMs = $retryBackoffMs[min($attempt - 1, count($retryBackoffMs) - 1)];
            usleep($backoffMs * 1000);
        }
    }

    if ($response === null) {
        $this->fail('Skill validation response was null after retries.');
    }

    $normalizeStringList = static function (mixed $value): array {
        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $normalized[] = $item;
        }

        return array_values($normalized);
    };

    $responseData = [
        'skill_name' => data_get($response, 'skill_name'),
        'path' => data_get($response, 'path'),
        'valid' => (bool) data_get($response, 'valid', false),
        'errors' => $normalizeStringList(data_get($response, 'errors', [])),
        'warnings' => $normalizeStringList(data_get($response, 'warnings', [])),
        'improvements' => $normalizeStringList(data_get($response, 'improvements', [])),
        'usage' => data_get($response, 'usage'),
    ];
    $result = [
        'skill_name' => $responseData['skill_name'],
        'path' => $responseData['path'],
        'valid' => (bool) $responseData['valid'],
        'errors' => $responseData['errors'],
        'warnings' => $responseData['warnings'],
        'improvements' => $responseData['improvements'],
    ];
    $quality = SkillValidationQuality::evaluate($result);

    $logEntry = [
        'timestamp' => date(DATE_ATOM),
        'path' => $relativePath,
        'provider' => 'anthropic',
        'model' => $model,
        'status' => 'success',
        'attempt' => $attempt ?? 1,
        'max_attempts' => $maxAttempts,
        'valid' => $result['valid'],
        'errors' => $result['errors'],
        'warnings' => $result['warnings'],
        'improvements' => $result['improvements'],
        'markdown' => $markdown,
        'response' => $responseData,
        'usage' => $responseData['usage'] ?? null,
        'quality' => $quality,
    ];

    file_put_contents(
        $root.'/storage/logs/skills-validation.log',
        json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
        FILE_APPEND
    );

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
        ->and($result['errors'])->toBe([])
        ->and($quality['issues'])->toBe([]);
})->with('skills')->group('skills');
