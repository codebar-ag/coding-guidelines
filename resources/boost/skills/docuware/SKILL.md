---
name: docuware
description: DocuWare document management integration patterns. Use when working with DocuWare connector, webhooks, document imports, or app/Services/DocuWare/.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# DocuWare

## When to Use

- When implementing DocuWare webhook handling and background document imports.
- When reading DocuWare metadata and mapping it into local business models.
- When working in `app/Services/DocuWare/` or related import jobs.

## When NOT to Use

- For generic third-party APIs that should follow Saloon-first conventions.
- For local file ingestion flows that do not involve DocuWare.

## Preconditions

- `codebar-ag/laravel-docuware` package is installed.
- Credentials are configured in `config/laravel-docuware.php`.
- Webhook HMAC secret and signature middleware are configured.
- `DocumentImport` model, migration, and queue job exist.
- Target model creation contract is defined (for example required `Invoice` fields).

## Rules

- Uses `codebar-ag/laravel-docuware` package (not raw Saloon)
- Connector: `CodebarAg\DocuWare\Connectors\DocuWareConnector` with `ConfigWithCredentials`
- Credentials via `config('laravel-docuware.credentials.*')` — never `env()` directly
- DocuWareService wraps the connector in `app/Services/DocuWare/DocuWareService.php`
- Key methods: `fetchDocument()`, `downloadAndStore()`, `mapToInvoiceData()`, `processImport()`
- Accept optional connector in constructor for testability
- Field mapping defined in `config/docuware-fields.php`
- Always check for DocuWare credentials before making API calls
- Handle download failures gracefully (log warning, continue without file)
- Use `DB::transaction()` for model creation + import status update
- Duplicate detection: skip if same `source_document_id` already exists
- Activity logging at each stage (webhook received, processing started/completed/failed)
- Validate payload schema before dispatching jobs (required keys and expected types)
- Validate mapped data before model creation (FormRequest-like rules or validator in service/job)

## Webhook Flow

1. `POST /api/docuware/webhook` receives payload
2. HMAC signature verification via `VerifyDocuWareSignature` middleware
3. Validate required payload fields (`source_document_id`, `document_type`, `event`, timestamps as needed)
4. Create `DocumentImport` record with status `pending`
5. Dispatch `ProcessDocuWareImport` queued job
6. Job: fetch metadata, download document, map+validate local payload, create model, update import status

## Import Lifecycle

- `DocumentImport` model tracks: `pending` → `processing` → `completed` / `failed`
- Use `DB::transaction()` when creating model and updating import status together

## Examples

```php
// DocuWareService — optional connector for testability
class DocuWareService
{
    public function __construct(?DocuWareConnector $connector = null)
    {
        $this->connector = $connector ?? new DocuWareConnector();
    }

    public function processImport(DocumentImport $import): void
    {
        if (! config('laravel-docuware.credentials.api_url')) {
            throw new DocuWareNotConfiguredException();
        }

        $import->update(['status' => 'processing']);

        try {
            DB::transaction(function () use ($import) {
                $document = $this->fetchDocument($import->source_document_id);
                $data = $this->mapToInvoiceData($document);
                validator($data, [
                    'customer_id' => ['required', 'integer', 'exists:customers,id'],
                    'amount' => ['required', 'numeric'],
                    'status' => ['required', 'string'],
                ])->validate();
                $model = Invoice::create($data);
                $import->update(['status' => 'completed', 'importable_id' => $model->id]);
                activity()->performedOn($import)->log('Document import completed.');
            });
        } catch (Throwable $e) {
            Log::warning('DocuWare import failed.', [
                'import_id' => $import->id,
                'source_document_id' => $import->source_document_id,
                'message' => $e->getMessage(),
            ]);
            $import->update(['status' => 'failed']);
            throw $e;
        }
    }
}
```

```php
// Duplicate detection before processing
if (DocumentImport::where('source_document_id', $payload['id'])->exists()) {
    return response()->json(['status' => 'skipped'], 200);
}
```

## Testing Guidance

- Mock or fake `DocuWareConnector` and inject it into `DocuWareService`.
- Test webhook payload validation separately from import processing.
- Test duplicate detection and each lifecycle transition (`pending` to `processing` to `completed`/`failed`).
- Assert logging context includes traceable identifiers (at minimum `import_id` and `source_document_id`).

## Anti-Patterns

- Using `env()` directly for DocuWare credentials — use `config()`
- Making API calls without checking credentials first
- Not using `DB::transaction()` when creating models and updating import status
- Swallowing failures by catching and returning without logging/updating status; instead log context, set status `failed`, and re-throw when needed
- Missing activity logging at import lifecycle stages
- Processing imports without duplicate detection on `source_document_id`

## References

- [codebar-ag/laravel-docuware](https://github.com/codebar-ag/laravel-docuware)
- Related: `Services/SKILL.md` — service class conventions
- Related: `Jobs/SKILL.md` — queued job for ProcessDocuWareImport
