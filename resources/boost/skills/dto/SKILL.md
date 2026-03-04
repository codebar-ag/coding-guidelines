---
name: dto
description: Readonly data containers with typed factory methods (`fromArray`, `fromModel`, `fromCollection`, `fromRequest`) used to pass structured data between application layers — especially for external API responses, Eloquent models, and service boundaries. Use this skill whenever creating, reviewing, or refactoring DTOs, Data Transfer Objects, value objects for inter-layer communication, or mapping payloads from APIs, models, or collections into typed PHP objects. Also trigger when the user mentions spatie/laravel-data alternatives, data mapping, or payload normalization in a Laravel context.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# DTO (Data Transfer Object)

## When to Apply

- Use when mapping external API payloads into typed objects before service logic runs.
- Use when passing structured data between Actions, Services, Jobs, and Resources.
- Use when hydrating typed objects from Eloquent models, collections, or paginators.
- Use when providers return multiple key formats for the same field (`ID`, `id`, `customer_id`, `customerId`).
- Use when a Form Request, Job, or Event needs a well-defined payload contract.

Do **not** use for single scalar wrappers, data used in only one private method, or when `spatie/laravel-data` is already installed and covers the use case (see *Alternatives* at the end).

## Preconditions

- The payload shape is known from API docs, Eloquent model attributes, fixtures, or tests.
- The owning module exists (example: `app/Services/Billing/DataObjects/`).
- Validation rules are defined in the caller (Form Request, Action, or Service), not the DTO.

## Process

### 1. Create the DTO Class

- Declare a `readonly class` with promoted, explicitly typed constructor properties.
- Place it in the owning feature/module `DataObjects/` directory.
- Import `Illuminate\Support\Arr` at the top of the file.

### 2. Implement Factory Methods

Add one or more `public static` factory methods depending on the data sources the DTO serves. Every factory method that reads from an associative array **must** use Laravel's `Arr::get()` helper (or the `data_get()` global) instead of raw `$data['key']` bracket access. This provides safe default handling and dot-notation support for nested payloads.

#### `fromArray(array $data): static`
The primary factory for raw associative arrays (API responses, decoded JSON, validated request data).

#### `fromModel(Model $model): static`
Accepts an Eloquent model and reads attributes via `$model->getAttribute()` or `$model->{property}`. Use when the DTO is frequently hydrated from a database record.

#### `fromRequest(FormRequest $request): static`
Reads from a validated Form Request. Prefer `$request->validated()` to ensure only validated fields are passed, then delegate to `fromArray()`.

#### `fromCollection(Collection $collection): Collection` *(collection of DTOs)*
Returns a `Collection` (or typed array) of DTO instances. Use `$collection->map(...)` internally.

Not every DTO needs all four factories — add only those that match real call-sites. At minimum, provide `fromArray()`.

### 3. Use Laravel Array Helpers Everywhere

Inside factory methods, **never** access array values with raw bracket syntax (`$data['key'] ?? null`). Instead:

```php
use Illuminate\Support\Arr;

// Good — safe access with default
Arr::get($data, 'customer.name', 'Unknown');

// Good — global helper, supports dot-notation and wildcards
data_get($data, 'customer.name', 'Unknown');

// Bad — raw bracket access, no dot-notation, verbose fallback chains
$data['customer']['name'] ?? $data['Customer']['Name'] ?? 'Unknown';
```

When normalizing multiple key variants for the same field, combine `Arr::get()` calls with a `??` chain on the results — not on raw brackets:

```php
Arr::get($data, 'ID') ?? Arr::get($data, 'id') ?? Arr::get($data, 'customer_id', '');
```

### 4. Keep Validation Outside the DTO

- Validate required/format rules **before** calling any factory method.
- The DTO's job is mapping and type-safe access — not enforcing business rules.

### 5. Write a Dedicated DTO Test

Every DTO **must** have its own test class. DTO tests are pure unit tests — no HTTP calls and no database. They can run without booting the framework, but may extend a lightweight bootstrapped base test case (e.g. `Tests\\TestCase` via Testbench) when DTO factories depend on Eloquent models. They verify that every factory method correctly maps, normalizes, and type-casts input data.

Place test files alongside the DTO's module path: `tests/Unit/Services/Billing/DataObjects/CustomerDataTest.php`.

A DTO test class should cover:

- **Happy path** — standard payload maps to correct property values.
- **Key variant normalization** — each known alternate key (`ID` vs `id` vs `customer_id`) resolves correctly.
- **Nullable / optional fields** — missing keys result in `null` (not exceptions).
- **Type casting** — string IDs, float totals, bool flags are cast correctly.
- **Nested data** — dot-notation fields and nested DTOs hydrate properly.
- **fromModel** — model attributes (including casts and accessors) map correctly.
- **fromCollection** — returns a collection of the correct DTO type and count.
- **Edge cases** — empty arrays, missing keys, extra keys are handled gracefully.

### 6. Verify Call Sites

- Replace ad-hoc array indexing in services/jobs with DTO property access.
- Where a DTO is created from a model, ensure eager-loaded relationships are available before mapping.

## Examples

### Basic DTO with `fromArray`

```php
<?php

declare(strict_types=1);

namespace App\Services\Billing\DataObjects;

use Illuminate\Support\Arr;

readonly class CustomerData
{
    public function __construct(
        public string  $id,
        public ?string $name,
        public ?string $email,
    ) {}

    /**
     * Create from a raw associative array (API response, validated input, etc.).
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:    (string) (Arr::get($data, 'ID') ?? Arr::get($data, 'id', '')),
            name:  Arr::get($data, 'Name') ?? Arr::get($data, 'name') ?? Arr::get($data, 'customer_name'),
            email: Arr::get($data, 'Email') ?? Arr::get($data, 'email'),
        );
    }
}
```

### DTO with `fromModel` and `fromCollection`

```php
<?php

declare(strict_types=1);

namespace App\Services\Shipping\DataObjects;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

readonly class OrderData
{
    public function __construct(
        public int     $id,
        public string  $status,
        public float   $total,
        public ?string $trackingNumber,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id:             (int) Arr::get($data, 'id', 0),
            status:         (string) Arr::get($data, 'status', 'pending'),
            total:          (float) Arr::get($data, 'total', 0),
            trackingNumber: Arr::get($data, 'tracking_number') ?? Arr::get($data, 'trackingNumber'),
        );
    }

    public static function fromModel(Order $order): static
    {
        return new static(
            id:             $order->getKey(),
            status:         $order->getAttribute('status'),
            total:          (float) $order->getAttribute('total'),
            trackingNumber: $order->getAttribute('tracking_number'),
        );
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, static>
     */
    public static function fromCollection(Collection $orders): Collection
    {
        return $orders->map(fn (Order $order): static => static::fromModel($order));
    }
}
```

### DTO with `fromRequest`

```php
public static function fromRequest(StoreOrderRequest $request): static
{
    return static::fromArray($request->validated());
}
```

### Caller-side usage

```php
// Validation belongs to the caller; the DTO maps only.
$validated = $request->validated();
$customer  = CustomerData::fromArray($apiResponse);
echo $customer->name;

// From an Eloquent model
$orderData = OrderData::fromModel(Order::findOrFail($id));

// From a collection / paginator
$orders = OrderData::fromCollection(Order::where('status', 'shipped')->get());
```

### Nested DTO access with `data_get`

When the source payload has deeply nested data, prefer `data_get()` for dot-notation traversal:

```php
public static function fromArray(array $data): static
{
    return new static(
        city:    data_get($data, 'address.city', 'Unknown'),
        zipCode: data_get($data, 'address.zip_code') ?? data_get($data, 'address.postalCode'),
    );
}
```

### Dedicated DTO Test

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Billing\DataObjects;

use App\Services\Billing\DataObjects\CustomerData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CustomerDataTest extends TestCase
{
    #[Test]
    public function it_creates_from_standard_keys(): void
    {
        $dto = CustomerData::fromArray([
            'id'    => '123',
            'name'  => 'Acme Corp',
            'email' => 'billing@acme.test',
        ]);

        $this->assertSame('123', $dto->id);
        $this->assertSame('Acme Corp', $dto->name);
        $this->assertSame('billing@acme.test', $dto->email);
    }

    #[Test]
    #[DataProvider('alternateKeyProvider')]
    public function it_normalizes_alternate_key_formats(array $payload, string $expectedId, ?string $expectedName): void
    {
        $dto = CustomerData::fromArray($payload);

        $this->assertSame($expectedId, $dto->id);
        $this->assertSame($expectedName, $dto->name);
    }

    public static function alternateKeyProvider(): array
    {
        return [
            'uppercase keys' => [
                ['ID' => '456', 'Name' => 'Globex'],
                '456',
                'Globex',
            ],
            'customer_name variant' => [
                ['id' => '789', 'customer_name' => 'Initech'],
                '789',
                'Initech',
            ],
            'ID takes precedence over id' => [
                ['ID' => '100', 'id' => '200', 'name' => 'Test'],
                '100',
                'Test',
            ],
        ];
    }

    #[Test]
    public function it_handles_missing_optional_fields(): void
    {
        $dto = CustomerData::fromArray(['id' => '1']);

        $this->assertSame('1', $dto->id);
        $this->assertNull($dto->name);
        $this->assertNull($dto->email);
    }

    #[Test]
    public function it_casts_numeric_id_to_string(): void
    {
        $dto = CustomerData::fromArray(['id' => 42]);

        $this->assertSame('42', $dto->id);
    }

    #[Test]
    public function it_defaults_id_to_empty_string_when_missing(): void
    {
        $dto = CustomerData::fromArray([]);

        $this->assertSame('', $dto->id);
    }

    #[Test]
    public function it_ignores_extra_keys(): void
    {
        $dto = CustomerData::fromArray([
            'id'         => '1',
            'name'       => 'Test',
            'email'      => 'test@example.test',
            'phone'      => '555-0100',
            'created_at' => '2025-01-01',
        ]);

        $this->assertSame('1', $dto->id);
        $this->assertSame('Test', $dto->name);
        $this->assertSame('test@example.test', $dto->email);
    }
}
```

### DTO Test for `fromModel` and `fromCollection`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Shipping\DataObjects;

use App\Models\Order;
use App\Services\Shipping\DataObjects\OrderData;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderDataTest extends TestCase
{
    #[Test]
    public function it_creates_from_model(): void
    {
        $order = new Order();
        $order->forceFill([
            'id'              => 7,
            'status'          => 'shipped',
            'total'           => 99.95,
            'tracking_number' => 'TRK-001',
        ]);

        $dto = OrderData::fromModel($order);

        $this->assertSame(7, $dto->id);
        $this->assertSame('shipped', $dto->status);
        $this->assertSame(99.95, $dto->total);
        $this->assertSame('TRK-001', $dto->trackingNumber);
    }

    #[Test]
    public function it_handles_null_tracking_number_from_model(): void
    {
        $order = new Order();
        $order->forceFill([
            'id'     => 8,
            'status' => 'pending',
            'total'  => 0,
        ]);

        $dto = OrderData::fromModel($order);

        $this->assertNull($dto->trackingNumber);
    }

    #[Test]
    public function it_creates_collection_from_models(): void
    {
        $orders = new Collection([
            tap(new Order(), fn ($o) => $o->forceFill(['id' => 1, 'status' => 'pending',  'total' => 10])),
            tap(new Order(), fn ($o) => $o->forceFill(['id' => 2, 'status' => 'shipped',  'total' => 20])),
            tap(new Order(), fn ($o) => $o->forceFill(['id' => 3, 'status' => 'delivered', 'total' => 30])),
        ]);

        $dtos = OrderData::fromCollection($orders);

        $this->assertCount(3, $dtos);
        $this->assertContainsOnlyInstancesOf(OrderData::class, $dtos);
        $this->assertSame('shipped', $dtos->get(1)->status);
    }

    #[Test]
    public function it_returns_empty_collection_from_empty_input(): void
    {
        $dtos = OrderData::fromCollection(new Collection());

        $this->assertCount(0, $dtos);
    }

    #[Test]
    public function from_array_normalizes_tracking_number_variants(): void
    {
        $snakeCase = OrderData::fromArray(['id' => 1, 'status' => 'new', 'total' => 5, 'tracking_number' => 'A']);
        $camelCase = OrderData::fromArray(['id' => 2, 'status' => 'new', 'total' => 5, 'trackingNumber'  => 'B']);

        $this->assertSame('A', $snakeCase->trackingNumber);
        $this->assertSame('B', $camelCase->trackingNumber);
    }
}
```

## Checklists

- [ ] DTO is `readonly` and all properties are explicitly typed.
- [ ] Array access inside factory methods uses `Arr::get()` or `data_get()` — no raw bracket access.
- [ ] `fromArray()` handles all known key variants for the source payload.
- [ ] Additional factory methods (`fromModel`, `fromCollection`, `fromRequest`) exist where needed.
- [ ] Validation and business rules live outside the DTO (Form Request, Action, or Service).
- [ ] File is stored in the owning module's `DataObjects/` directory.
- [ ] Nested DTOs are hydrated via their own factory methods, not inline array parsing.
- [ ] A dedicated test class exists covering happy path, key variants, nullables, type casting, and edge cases.
- [ ] `fromModel` tests use `forceFill()` on unsaved model instances — no database required.
- [ ] `fromCollection` tests assert correct count, instance type, and empty-collection handling.

## Anti-Patterns

- Adding setters or mutable state to a DTO.
- Putting business logic, DB queries, or HTTP calls inside a DTO.
- Using DTOs as Eloquent model replacements (use models for persistence, DTOs for transport).
- Accessing arrays with raw bracket syntax (`$data['key']`) instead of `Arr::get()` / `data_get()`.
- Assuming a single naming convention (e.g. only `customer_id`, ignoring `customerId` or `CustomerID`).
- Leaving properties untyped or using `mixed` without narrowing.
- Creating a single "god DTO" for create, update, and read — split into separate DTOs per context when validation rules diverge significantly.
- Duplicating model attribute logic inside the DTO — call `$model->getAttribute()` and let Eloquent handle casts and accessors.
- Skipping DTO tests because "it's just a data class" — factory methods contain mapping logic that breaks silently when API payloads change.

## Alternatives

### spatie/laravel-data

If the project already uses (or is open to) `spatie/laravel-data`, consider extending `Spatie\LaravelData\Data` or `Spatie\LaravelData\Dto` instead of writing a plain readonly class. The package provides:

- Automatic creation from arrays, models, requests, and collections via `::from()` and `::collect()`.
- Built-in validation, casts, transformers, and TypeScript generation.
- Property name mapping with `#[MapInputName]` for snake_case ↔ camelCase.
- Eloquent casting support for storing data objects in JSON columns.

Use a manual readonly DTO when you need zero dependencies, full control over mapping logic, or when working with external API payloads that require heavy key normalization.

## References

- [Laravel Arr & data_get Helpers](https://laravel.com/docs/12.x/helpers) — `Arr::get()`, `data_get()`, `data_fill()`, `data_set()`
- [PHP Readonly Classes](https://www.php.net/manual/en/language.oop5.readonly-properties.php)
- [spatie/laravel-data — Creating a Data Object](https://spatie.be/docs/laravel-data/v4/as-a-data-transfer-object/creating-a-data-object)
- [spatie/laravel-data — From a Model](https://spatie.be/docs/laravel-data/v4/as-a-data-transfer-object/model-to-data-object)
- [spatie/laravel-data — Collections](https://spatie.be/docs/laravel-data/v4/as-a-data-transfer-object/collections)
- `resources/boost/skills/services/SKILL.md` (DTO consumers)
- `resources/boost/skills/saloon/SKILL.md` (external API mapping)
