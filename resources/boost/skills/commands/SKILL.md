---
name: commands
description: Artisan console command classes that serve as the CLI entry point for operations. Commands validate input and delegate all business logic to Actions or Services.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Commands

## When to Use

- CLI-triggered business operations, maintenance tasks, imports, and admin workflows.
- Developer/operator entrypoints that should be explicit and scriptable.
- Cases where command execution delegates domain behavior to Actions/Services.

## When Not to Use

- Pure background workflows that should be queued jobs directly.
- Time-based automation that belongs in the scheduler (`app/Console/Kernel.php`) calling existing commands/jobs.
- HTTP-initiated behavior where a controller/action is the correct entrypoint.

## Preconditions

- Command class is placed under `app/Console/Commands/`.
- Required Action/Service dependencies already exist (or are planned first).
- Signature, argument descriptions, and options are fully defined before implementation.
- Input validation rules are known for all arguments/options.

## Process Checklist

- [ ] Define a clear `namespace:action` signature and command description.
- [ ] Add descriptions for every argument/option.
- [ ] Validate raw input with `validator()` or `Validator`.
- [ ] Delegate business logic to an Action/Service.
- [ ] Return `self::SUCCESS` / `self::FAILURE` consistently.
- [ ] Add tests for success and failure paths.

## Rules

- Keep commands as orchestration entrypoints, not business-logic containers.
- Validate all input and fail explicitly on invalid arguments/options.
- Use `PromptsForMissingInput` for better operator UX.
- Log meaningful errors while still returning deterministic exit codes.

## Examples

```php
class SendInvoiceReminders extends Command implements PromptsForMissingInput
{
    protected $signature = 'invoices:send-reminders
                            {email : The email address to send the reminder to}
                            {--dry-run : Simulate the command without persisting changes}';

    protected $description = 'Send invoice payment reminders to a specific user.';

    // Laravel resolves this dependency automatically via container injection.
    public function __construct(private readonly SendInvoiceReminderAction $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $validated = validator(
            [
                'email'   => $this->argument('email'),
                'dry_run' => $this->option('dry-run'),
            ],
            [
                'email'   => ['required', 'email'],
                'dry_run' => ['boolean'],
            ]
        )->validate(); // Equivalent explicit style: Validator::make(...)->validate()

        try {
            $this->action->execute($validated['email']);
            $this->info("Reminder sent to {$validated['email']}.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error("Failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'email' => ['What email should receive the reminder?'],
        ];
    }
}
```

## Testing Guidance

- Add command tests for argument validation and exit codes.
- Verify delegated Action/Service is called with validated values.
- Assert console output for both success and error paths.

```php
it('delegates validated input to the action', function () {
    $action = Mockery::mock(SendInvoiceReminderAction::class);
    $action->shouldReceive('execute')
        ->once()
        ->with('billing@example.com');

    $this->app->instance(SendInvoiceReminderAction::class, $action);

    $this->artisan('invoices:send-reminders billing@example.com')
        ->expectsOutput('Reminder sent to billing@example.com.')
        ->assertExitCode(Command::SUCCESS);
});
```

## Anti-Patterns

- Putting business logic directly inside `handle()` instead of delegating to an Action or Service
- Not returning `self::SUCCESS` or `self::FAILURE` (e.g., returning `void` or `null`)
- Trusting raw `$this->argument()` / `$this->option()` values without validation
- Omitting descriptions from arguments and options in the signature
- Not implementing `PromptsForMissingInput`, causing silent failures for missing arguments

## References

- [Laravel Artisan Console](https://laravel.com/docs/artisan)
- Related: `Actions/SKILL.md` — for the business logic commands delegate to
