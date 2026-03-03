---
name: interfaces
description: Contracts defining the shape a class must conform to. Used for decoupling dependent classes from concrete implementations, enabling multiple implementations and easier testing.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Interfaces & Contracts

## When to Use

- You have (or realistically expect) multiple implementations.
- Consumers should depend on capabilities, not implementation details.
- You need easy test substitution via container binding.

## When Not to Use

- A class has one stable implementation with no likely alternatives.
- An interface adds indirection but no flexibility.
- A team is creating interfaces by default for every class.

## Preconditions

- Laravel project uses container bindings and constructor injection.
- `app/Contracts/` is the canonical location for application contracts.
- A service provider (typically `AppServiceProvider`) is available for bindings.

## Process

1. Identify a capability that needs multiple implementations.
2. Extract a capability-based interface in `app/Contracts/`.
3. Implement the contract in one or more concrete classes.
4. Bind the interface to a default implementation in a service provider.
5. Type-hint the interface in consumers and swap bindings in tests.

## Rules

- Interface classes live in `app/Contracts/`
- Interfaces define a **contract** — the shape a class must conform to, without dictating the implementation
- Use a clear noun or adjective that describes the capability: `PaymentGateway`, `Notifiable`, `ReportGenerator`
- Avoid an `Interface` suffix — the namespace and context make it clear
- Only create an interface when there are (or you anticipate) **multiple implementations**
- Always type-hint the interface, never the concrete class, in consuming classes
- Bind the interface to a concrete implementation in a service provider
- Define only public method signatures — no implementation, no properties

## Examples

```php
// Interface definition
namespace App\Contracts;

use App\Models\Order;
use App\Data\PaymentResult;

interface PaymentGateway
{
    public function charge(Order $order, int $amountInCents): PaymentResult;

    public function refund(string $transactionId, int $amountInCents): PaymentResult;
}
```

```php
// Implementation
namespace App\Services\Payment;

use App\Contracts\PaymentGateway;

class StripeGateway implements PaymentGateway
{
    public function charge(Order $order, int $amountInCents): PaymentResult
    {
        // Stripe-specific implementation
    }

    public function refund(string $transactionId, int $amountInCents): PaymentResult
    {
        // Stripe-specific implementation
    }
}
```

```php
// Service container binding — AppServiceProvider
public function register(): void
{
    $this->app->bind(PaymentGateway::class, StripeGateway::class);
}
```

```php
// Consuming — type-hint the interface
class ChargePaymentMethod
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}
}
```

```php
// Testing — swap the binding
$this->app->bind(PaymentGateway::class, FakePaymentGateway::class);
```

## Checklist

- [ ] Interface name reflects capability (`PaymentGateway`, not `PaymentGatewayInterface`).
- [ ] Contract contains signatures only (no implementation/state).
- [ ] Consumer classes type-hint the interface.
- [ ] Concrete classes implement every method from the contract.
- [ ] Service provider binding exists for runtime resolution.
- [ ] Tests can swap the contract binding to fake/stub implementations.

## Anti-Patterns

- Creating an interface for every class by default — adds indirection and cognitive overhead without value.
- Creating an interface with one implementation and no realistic alternative — increases maintenance cost with no gain.
- Adding default implementations to an interface — violates contract-only intent; use an abstract class if needed.
- Adding constants specific to one implementation — leaks implementation details into the contract.
- Type-hinting concrete classes in consumers — prevents easy swapping and weakens dependency inversion.

## References

- [Laravel Service Container](https://laravel.com/docs/container)
- [PHP Interfaces](https://www.php.net/manual/en/language.oop5.interfaces.php)
- Related: `Services/SKILL.md` — services often implement interfaces
