#!/bin/bash
# Validates AI response against skill-specific rules.
# Usage: validate-skill-response.sh <skill-name> <response>
# Exits 0 if valid, 1 if invalid.

skill="$1"
response="$2"

if [ -z "$response" ]; then
  echo "::error::[$skill] Empty response"
  exit 1
fi

case "$skill" in
  general)
    echo "$response" | grep -q 'env(' && { echo "::error::[$skill] Should use config() not env()"; exit 1; }
    echo "$response" | grep -q 'config(' || { echo "::error::[$skill] Missing config()"; exit 1; }
    ;;
  helperfunctions)
    echo "$response" | grep -qE 'Arr::|Str::|collect\(' || { echo "::error::[$skill] Should use Laravel helpers"; exit 1; }
    ;;
  models)
    echo "$response" | grep -qE 'extends Model|BelongsTo|HasMany|HasOne' || { echo "::error::[$skill] Needs Model with relationship"; exit 1; }
    ;;
  controllers)
    echo "$response" | grep -qE '__invoke|JsonResponse' || { echo "::error::[$skill] Needs invokable or JsonResponse"; exit 1; }
    ;;
  migrations)
    echo "$response" | grep -qE 'foreignId|constrained|cascadeOnDelete' || { echo "::error::[$skill] Needs proper foreign key"; exit 1; }
    echo "$response" | grep -qE 'function (up|down)\s*\(\)' || { echo "::error::[$skill] Needs up() and down()"; exit 1; }
    ;;
  routing)
    echo "$response" | grep -qE 'Route::|Route::apiResource|middleware' || { echo "::error::[$skill] Needs Route definitions"; exit 1; }
    ;;
  formrequests|requests)
    echo "$response" | grep -qE 'authorize\(\)|rules\(\)' || { echo "::error::[$skill] Needs authorize and rules"; exit 1; }
    ;;
  actions)
    echo "$response" | grep -q 'execute(' || { echo "::error::[$skill] Action needs execute()"; exit 1; }
    echo "$response" | grep -qE 'InvoiceAction|UserHandler' && { echo "::error::[$skill] Avoid vague names"; exit 1; } || true
    ;;
  commands)
    echo "$response" | grep -qE 'self::SUCCESS|self::FAILURE' || { echo "::error::[$skill] Must return SUCCESS or FAILURE"; exit 1; }
    ;;
  dto)
    echo "$response" | grep -q 'fromArray' || { echo "::error::[$skill] DTO needs fromArray()"; exit 1; }
    echo "$response" | grep -q 'readonly' || { echo "::error::[$skill] DTO should be readonly"; exit 1; }
    ;;
  enums)
    echo "$response" | grep -qE 'label\(\)|color\(\)' || { echo "::error::[$skill] Enum needs label() and color()"; exit 1; }
    echo "$response" | grep -q 'match' || true
    ;;
  events)
    echo "$response" | grep -qE 'Dispatchable|SerializesModels' || true
    ;;
  exceptions)
    echo "$response" | grep -q 'Exception' || { echo "::error::[$skill] Needs Exception suffix"; exit 1; }
    ;;
  helpers)
    echo "$response" | grep -qE 'Helper|format' || true
    ;;
  interfaces)
    echo "$response" | grep -q 'interface' || { echo "::error::[$skill] Needs interface"; exit 1; }
    ;;
  jobs)
    echo "$response" | grep -qE 'ShouldQueue|Dispatchable|Queueable' || { echo "::error::[$skill] Job needs queue traits"; exit 1; }
    ;;
  middleware)
    echo "$response" | grep -q '\$next(\$request)' || { echo "::error::[$skill] Middleware must call next"; exit 1; }
    ;;
  observers)
    echo "$response" | grep -qE 'Observer|created|updated' || { echo "::error::[$skill] Needs Observer and lifecycle methods"; exit 1; }
    ;;
  policies)
    echo "$response" | grep -qE 'viewAny|view|create' || { echo "::error::[$skill] Policy needs ability methods"; exit 1; }
    ;;
  resources)
    echo "$response" | grep -qE 'whenLoaded|when\(|JsonResource' || { echo "::error::[$skill] Resource needs whenLoaded/when"; exit 1; }
    ;;
  services)
    echo "$response" | grep -qE 'DB::transaction|Service' || { echo "::error::[$skill] Service needs DB::transaction or Service class"; exit 1; }
    ;;
  traits)
    echo "$response" | grep -q 'trait' || { echo "::error::[$skill] Needs trait"; exit 1; }
    ;;
  blade)
    echo "$response" | grep -q '{{' || { echo "::error::[$skill] Blade needs {{ }} escaping"; exit 1; }
    ;;
  design)
    echo "$response" | grep -qE '\$attributes|@props|min-h-\[44px\]' || true
    ;;
  livewire)
    echo "$response" | grep -qE 'wire:model|wire:click|Computed' || { echo "::error::[$skill] Livewire needs wire: or Computed"; exit 1; }
    ;;
  tailwind)
    echo "$response" | grep -qE '@theme|@source|tailwind' || { echo "::error::[$skill] Needs @theme or Tailwind setup"; exit 1; }
    ;;
  translations)
    echo "$response" | grep -qE '__\(|trans\(' || { echo "::error::[$skill] Needs __() or trans()"; exit 1; }
    ;;
  phpunit)
    echo "$response" | grep -qE 'test_|assertStatus|assertDatabaseHas|RefreshDatabase' || { echo "::error::[$skill] Needs test structure"; exit 1; }
    ;;
  pesttesting)
    echo "$response" | grep -qE 'it\(|expect\(|assertCreated|Arrange|Act|Assert' || { echo "::error::[$skill] Needs Pest syntax"; exit 1; }
    ;;
  phpstan)
    echo "$response" | grep -qE 'array<string|@return|: \w+' || true
    ;;
  dusk)
    echo "$response" | grep -qE 'assertNoJavaScriptErrors|dusk=|DatabaseTruncation' || true
    ;;
  php)
    echo "$response" | grep -qE 'throw new|return;' || true
    ;;
  saloon)
    echo "$response" | grep -q 'Connector' || { echo "::error::[$skill] Needs Saloon Connector"; exit 1; }
    echo "$response" | grep -qE 'Http::(get|post)|file_get_contents' && { echo "::error::[$skill] No raw HTTP"; exit 1; } || true
    ;;
  docuware)
    echo "$response" | grep -q 'config(' || { echo "::error::[$skill] Use config() not env()"; exit 1; }
    ;;
  albatros)
    echo "$response" | grep -qE 'Connector|Saloon' || { echo "::error::[$skill] Needs Saloon Connector"; exit 1; }
    ;;
  *)
    # Unknown skill - just check non-empty
    [ -n "$response" ] || exit 1
    ;;
esac

echo "✓ [$skill] validation passed"
exit 0
