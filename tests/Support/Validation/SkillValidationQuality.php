<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Tests\Support\Validation;

final class SkillValidationQuality
{
    /**
     * @param  array{warnings: array<int, string>, improvements: array<int, string>}  $result
     * @return array{issues: array<int, string>, score: int}
     */
    public static function evaluate(array $result): array
    {
        $issues = [];
        $warnings = array_values(array_filter($result['warnings'] ?? [], 'is_string'));
        $improvements = array_values(array_filter($result['improvements'] ?? [], 'is_string'));

        if (count($warnings) > 6) {
            $issues[] = 'warnings_exceed_limit';
        }

        if (count($improvements) > 6) {
            $issues[] = 'improvements_exceed_limit';
        }

        $warningsDuplicates = self::findDuplicates($warnings);
        if ($warningsDuplicates !== []) {
            $issues[] = 'duplicate_warnings: '.implode(', ', $warningsDuplicates);
        }

        $improvementDuplicates = self::findDuplicates($improvements);
        if ($improvementDuplicates !== []) {
            $issues[] = 'duplicate_improvements: '.implode(', ', $improvementDuplicates);
        }

        $crossSectionDuplicates = self::findCrossSectionDuplicates($warnings, $improvements);
        if ($crossSectionDuplicates !== []) {
            $issues[] = 'warning_improvement_overlap: '.implode(', ', $crossSectionDuplicates);
        }

        if (self::containsLowSignalItems($warnings, $improvements)) {
            $issues[] = 'low_signal_items_detected';
        }

        // Starts at 100 and loses 20 per issue to keep score readable.
        $score = max(0, 100 - (count($issues) * 20));

        return [
            'issues' => $issues,
            'score' => $score,
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<int, string>
     */
    private static function findDuplicates(array $items): array
    {
        $counts = [];

        foreach ($items as $item) {
            $normalized = self::normalize($item);
            if ($normalized === '') {
                continue;
            }

            $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
        }

        return array_keys(array_filter($counts, static fn (int $count): bool => $count > 1));
    }

    /**
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $improvements
     * @return array<int, string>
     */
    private static function findCrossSectionDuplicates(array $warnings, array $improvements): array
    {
        $duplicates = [];

        foreach ($warnings as $warning) {
            $normalizedWarning = self::normalize($warning);
            if ($normalizedWarning === '') {
                continue;
            }

            foreach ($improvements as $improvement) {
                $normalizedImprovement = self::normalize($improvement);
                if ($normalizedImprovement === '') {
                    continue;
                }

                if ($normalizedWarning === $normalizedImprovement) {
                    $duplicates[] = $normalizedWarning;

                    continue;
                }

                if (str_contains($normalizedWarning, $normalizedImprovement) || str_contains($normalizedImprovement, $normalizedWarning)) {
                    // Ignore tiny fragments to reduce false positives.
                    if (mb_strlen($normalizedWarning) >= 40 && mb_strlen($normalizedImprovement) >= 40) {
                        $duplicates[] = $normalizedWarning;
                    }
                }
            }
        }

        return array_values(array_unique($duplicates));
    }

    /**
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $improvements
     */
    private static function containsLowSignalItems(array $warnings, array $improvements): bool
    {
        $items = array_merge($warnings, $improvements);

        foreach ($items as $item) {
            $normalized = self::normalize($item);

            if ($normalized === '') {
                return true;
            }

            if (mb_strlen($normalized) < 20) {
                return true;
            }

            // Avoid brittle style checks that over-penalize valid, actionable suggestions.
            // Keep only deterministic low-signal checks (empty / too short).
        }

        return false;
    }

    private static function normalize(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized, " \t\n\r\0\x0B.,;:!?");
    }
}
