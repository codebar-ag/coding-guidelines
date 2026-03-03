---
name: dto
description: Readonly data containers with a `fromArray` factory method used to pass structured data between application layers - especially for external API responses and service boundaries.
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
- Use when providers return multiple key formats for the same field (`ID`, `id`, `customer_id`).
- Do not use for single scalar wrappers or data that is used in only one private method.

## Preconditions

- The payload shape is known from API docs, fixtures, or tests.
- The owning module exists (example: `app/Services/Billing/DataObjects/`).
- Validation rules are defined in the caller (Form Request, Action, or Service).

## Process

### 1. Create the DTO Class

- Add a `readonly class` with promoted, explicitly typed properties.
- Place it in the owning feature/module `DataObjects/` directory.

### 2. Implement `fromArray()` Mapping

- Add `public static function fromArray(array $data): static`.
- Normalize known field variants in one place.
- Keep required fields non-nullable and optional fields nullable.

### 3. Keep Validation Outside the DTO

- Validate required/format rules before calling `fromArray()`.
- Keep DTO code limited to mapping and data access.

### 4. Verify Call Sites

- Replace ad-hoc array indexing with DTO usage in services/jobs.
- Add a fixture case with at least one alternate key style.

## Examples

```php
readonly class CustomerData
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $email,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: (string) ($data['ID'] ?? $data['id'] ?? ''),
            name: $data['Name'] ?? $data['name'] ?? $data['customer_name'] ?? null,
            email: $data['Email'] ?? $data['email'] ?? null,
        );
    }
}
```

```php
// Validation belongs to caller; DTO maps only.
$validated = validator($payload, ['id' => ['required']])->validate();

$customer = CustomerData::fromArray($apiResponse);
echo $customer->name;
```

## Checklists

- [ ] DTO is `readonly` and all properties are typed.
- [ ] `fromArray()` handles all known key variants.
- [ ] Validation/business rules are outside the DTO.
- [ ] File is stored in the owning module `DataObjects/` path.

## Anti-Patterns

- Adding setters or mutable state to a DTO.
- Putting business logic, DB calls, or HTTP calls in a DTO.
- Using DTOs as Eloquent model replacements.
- Assuming one naming convention (for example only `customer_id` and ignoring `customerId`).
- Leaving properties untyped or using `mixed` without narrowing.

## References

- [PHP Readonly Classes](https://www.php.net/manual/en/language.oop5.readonly-properties.php)
- `resources/boost/skills/services/SKILL.md` (DTO consumers)
- `resources/boost/skills/saloon/SKILL.md` (external API mapping)
