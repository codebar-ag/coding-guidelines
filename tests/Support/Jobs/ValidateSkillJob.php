<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Tests\Support\Jobs;

use CodebarAg\CodingGuidelines\Tests\Support\Agents\SkillQualityAgent;
use CodebarAg\CodingGuidelines\Tests\Support\Validation\SkillValidationQuality;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

final class ValidateSkillJob implements ShouldQueue
{
    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [5, 15, 30];

    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $relativePath,
        public string $root,
        public string $model,
    ) {}

    public function handle(): void
    {
        $absolutePath = $this->root.'/'.$this->relativePath;

        if (! is_file($absolutePath)) {
            return;
        }

        $markdown = (string) file_get_contents($absolutePath);

        $logDir = $this->root.'/storage/logs';
        $logFile = $logDir.'/skills-validation.log';

        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $agent = new SkillQualityAgent;
        $maxAttempts = max(1, (int) env('SKILL_VALIDATION_MAX_ATTEMPTS', 3));
        $retryBackoffMs = [1000, 2000, 4000];
        $response = null;
        $attemptUsed = 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $agent->prompt(
                    prompt: <<<PROMPT
Validate the following SKILL.md file.

Relative path: {$this->relativePath}

SKILL.md contents:
------------------
{$markdown}
------------------
PROMPT,
                    provider: Lab::Anthropic,
                    model: $this->model,
                );
                $attemptUsed = $attempt;

                break;
            } catch (ProviderOverloadedException $exception) {
                $status = $attempt < $maxAttempts ? 'overloaded_retrying' : 'overloaded_failed';
                $logEntry = [
                    'timestamp' => date(DATE_ATOM),
                    'path' => $this->relativePath,
                    'provider' => 'anthropic',
                    'model' => $this->model,
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
                    $logFile,
                    json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
                    FILE_APPEND
                );

                if ($attempt >= $maxAttempts) {
                    throw $exception;
                }

                $backoffMs = $retryBackoffMs[min($attempt - 1, count($retryBackoffMs) - 1)];
                usleep($backoffMs * 1000);
            }
        }

        if ($response === null) {
            return;
        }

        $responseData = [
            'skill_name' => data_get($response, 'skill_name'),
            'path' => data_get($response, 'path'),
            'valid' => (bool) data_get($response, 'valid', false),
            'errors' => data_get($response, 'errors', []),
            'warnings' => data_get($response, 'warnings', []),
            'improvements' => data_get($response, 'improvements', []),
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
            'path' => $this->relativePath,
            'provider' => 'anthropic',
            'model' => $this->model,
            'status' => 'success',
            'attempt' => $attemptUsed,
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
            $logFile,
            json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
    }
}
