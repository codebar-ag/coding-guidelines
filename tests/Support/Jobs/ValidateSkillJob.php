<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Tests\Support\Jobs;

use CodebarAg\CodingGuidelines\Tests\Support\Agents\SkillQualityAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

final class ValidateSkillJob implements ShouldQueue
{
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
        } catch (ProviderOverloadedException) {
            $logEntry = [
                'timestamp' => date(DATE_ATOM),
                'path' => $this->relativePath,
                'model' => $this->model,
                'valid' => false,
                'errors' => ['AI provider is overloaded; validation deferred.'],
                'warnings' => [],
                'improvements' => [],
                'status' => 'overloaded',
                'markdown' => $markdown,
            ];

            file_put_contents(
                $logFile,
                json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );

            return;
        }

        $responseData = $response->toArray();

        $result = [
            'skill_name' => $responseData['skill_name'],
            'path' => $responseData['path'],
            'valid' => (bool) $responseData['valid'],
            'errors' => $responseData['errors'],
            'warnings' => $responseData['warnings'],
            'improvements' => $responseData['improvements'],
        ];

        $logEntry = [
            'timestamp' => date(DATE_ATOM),
            'path' => $this->relativePath,
            'model' => $this->model,
            'valid' => $result['valid'],
            'errors' => $result['errors'],
            'warnings' => $result['warnings'],
            'improvements' => $result['improvements'],
            'markdown' => $markdown,
            'response' => $responseData,
        ];

        file_put_contents(
            $logFile,
            json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
    }
}
