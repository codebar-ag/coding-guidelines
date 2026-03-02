<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Tests\Support\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

final class SkillQualityAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
You are an expert technical editor helping maintain a library of Laravel Boost skills.

Each skill is a markdown file named SKILL.md. Evaluate one SKILL.md at a time for:

STRUCTURE (blocking if wrong)
- YAML frontmatter with non-empty `name` and `description`.
- Clear H1 title.
- Not obviously just the raw template (no unfilled placeholders like [Skill Title], [tool-binary], [main workflow trigger], or TODO markers).

CONTENT (typically warnings if weak)
- Explains when to apply the skill and when not to.
- Describes preconditions / environment / repo state.
- Provides a concrete step-by-step process.
- Includes at least one checklist or clearly structured bullet list.
- Mentions safety / things to avoid where appropriate.

TONE & QUALITY (improvements / warnings)
- Clear, concise, specific to this skill.
- No obvious contradictions or copy-paste artifacts from unrelated skills.

Return a strict JSON object with this exact shape:
{
  "skill_name": string,      // inferred from frontmatter or path
  "path": string,            // relative file path
  "valid": boolean,          // false only for structural / severe issues
  "errors": string[],        // blocking issues (fail CI)
  "warnings": string[],      // non-blocking issues (style / clarity)
  "improvements": string[]   // concrete suggestions to strengthen the skill
}

Consider the skill INVALID (`valid: false`) when:
- Frontmatter is missing, or `name`/`description` are empty or obvious placeholders.
- There is no clear H1 title.
- The content is clearly still the raw template.

Otherwise, set `valid: true` even if there are warnings or improvements.
TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'skill_name' => $schema->string()->required(),
            'path' => $schema->string()->required(),
            'valid' => $schema->boolean()->required(),
            'errors' => $schema->array(
                $schema->string()
            )->required(),
            'warnings' => $schema->array(
                $schema->string()
            )->required(),
            'improvements' => $schema->array(
                $schema->string()
            )->required(),
        ];
    }
}
