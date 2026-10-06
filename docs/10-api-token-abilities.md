# API Token Abilities

## 1. Overview

**API Token Abilities** is the latest extension of the CoreAuth API authentication layer.

Its purpose is to provide a clean, reusable interface for determining whether the currently authenticated API token has a specific ability.

The feature is intentionally built on top of Laravel Sanctum and does not reimplement token ability logic.

---

## 2. Feature Goals

The feature provides two operations:

- `tokenCan()` — determine whether the current API token has a given ability.
- `tokenCant()` — determine whether the current API token does not have a given ability.

The implementation remains inside the existing `ApiAuthenticationManager` and does not introduce a new manager.

---

## 3. Architectural Position

API Token Abilities belongs to the **API Token Authentication** layer.

The responsibility boundaries are:

```text
API Token Management
        │
        │ creates / lists / revokes tokens
        ▼
API Token Authentication
        │
        ├── check()
        ├── user()
        ├── tokenCan()
        └── tokenCant()
                │
                ▼
        Laravel Sanctum
                │
                ▼
        HasApiTokens
```

The feature therefore extends API authentication instead of creating a separate authorization system.

---

## 4. Contract

The public contract is:

`src/Contracts/ApiAuthenticationManagerInterface.php`

```php
<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ApiAuthenticationManagerInterface
{
    public function check(): bool;

    public function user(): ?Authenticatable;

    public function tokenCan(string $ability): bool;

    public function tokenCant(string $ability): bool;
}
```

### Public methods

| Method | Responsibility |
|---|---|
| `check()` | Determine whether the current API request is authenticated |
| `user()` | Retrieve the authenticated API user |
| `tokenCan()` | Determine whether the current token has an ability |
| `tokenCant()` | Determine whether the current token does not have an ability |

---

## 5. Implementation

The implementation is located at:

`src/Services/ApiAuthenticationManager.php`

The manager uses Laravel's authentication factory to access the `sanctum` guard.

```php
private function guard(): Guard
{
    return $this->auth->guard('sanctum');
}
```

The ability checks are delegated to the authenticated user's Sanctum token.

### `tokenCan()`

```php
public function tokenCan(string $ability): bool
{
    $user = $this->user();

    if (
        ! $user ||
        ! in_array(HasApiTokens::class, class_uses_recursive($user), true)
    ) {
        return false;
    }

    return $user->tokenCan($ability);
}
```

The method safely returns `false` when:

- there is no authenticated API user;
- the user does not use Sanctum's `HasApiTokens` trait.

Otherwise, the decision is delegated to Sanctum.

### `tokenCant()`

```php
public function tokenCant(string $ability): bool
{
    return ! $this->tokenCan($ability);
}
```

This keeps the negative check consistent with the positive check and avoids duplicating token ability logic.

---

## 6. Laravel Sanctum Integration

CoreAuth does not implement its own token ability storage or checking mechanism.

Instead, it delegates the final decision to:

```php
$user->tokenCan($ability);
```

This preserves Laravel Sanctum as the source of truth for API token abilities.

The underlying Sanctum API already provides:

```php
tokenCan(string $ability)
tokenCant(string $ability)
```

CoreAuth exposes these operations through its own API authentication contract.

---

## 7. Token Creation and Abilities

API token abilities are assigned when a token is created through the existing API Token Management layer.

The existing API token manager already accepts an abilities array:

```php
createToken(
    Authenticatable $user,
    string $name,
    array $abilities = [],
    ?DateTimeInterface $expiresAt = null
): ApiTokenResult
```

Example:

```php
$result = $apiTokenManager->createToken(
    $user,
    'mobile-app',
    ['orders:read', 'orders:create']
);
```

The token can subsequently be checked through the API authentication manager:

```php
$apiAuthenticationManager->tokenCan('orders:read');
```

---

## 8. Example Usage

```php
if ($apiAuthenticationManager->check()) {
    if ($apiAuthenticationManager->tokenCan('orders:read')) {
        // Allow reading orders.
    }
}
```

Negative check:

```php
if ($apiAuthenticationManager->tokenCant('orders:delete')) {
    // Token does not have the required ability.
}
```

---

## 9. No New Manager

A separate `ApiTokenAbilityManager` was intentionally **not** introduced.

The reason is architectural:

- token creation and lifecycle belong to `ApiTokenManager`;
- current API authentication belongs to `ApiAuthenticationManager`;
- token ability checks are part of the authenticated token context;
- Sanctum already owns the underlying ability implementation.

Creating another manager would split a small responsibility unnecessarily and increase the public API surface of CoreAuth.

---

## 10. API Token Management vs API Token Abilities

These responsibilities are related but distinct.

### API Token Management

Responsible for the token lifecycle:

- create token;
- list tokens;
- revoke one token;
- revoke all tokens.

Implemented by:

`ApiTokenManager`

### API Token Authentication

Responsible for the current request:

- determine authenticated state;
- retrieve authenticated user;
- inspect the current token's abilities.

Implemented by:

`ApiAuthenticationManager`

Therefore:

```text
ApiTokenManager
    → Token lifecycle

ApiAuthenticationManager
    → Current API request
    → Current user
    → Current token abilities
```

---

## 11. Token Abilities vs Application Authorization

Token abilities should not be confused with application-level authorization.

### Token abilities

Answer:

> Is this API token allowed to perform this type of API operation?

Example:

```php
$apiAuthenticationManager->tokenCan('orders:read');
```

### Application authorization

Answers:

> Is this authenticated user authorized to perform this action on this resource?

This is handled by the existing authorization layer through Laravel's Gate/Policies.

Therefore, both mechanisms can work together:

```text
API Request
    │
    ├── API Authentication
    │       └── tokenCan('orders:read')
    │
    └── Application Authorization
            └── Gate / Policy
```

This separation prevents CoreAuth from mixing token capabilities with application authorization rules.

---

## 12. Service Container Binding

The existing service provider binds the interface to the implementation:

```php
$this->app->singleton(
    ApiAuthenticationManagerInterface::class,
    ApiAuthenticationManager::class
);
```

Consumers therefore depend on the contract rather than the concrete service.

---

## 13. Testing

The feature was covered by both unit and integration tests.

### Unit Tests

The API authentication manager tests cover:

- contract implementation;
- public `tokenCan()` method;
- public `tokenCant()` method;
- Sanctum guard usage;
- successful `tokenCan()` checks;
- failed `tokenCan()` checks;
- successful `tokenCant()` checks;
- failed `tokenCant()` checks.

Result:

**8 tests / 27 assertions**

### Integration Tests

The integration suite verifies behavior against the Sanctum authentication flow, including:

- authenticated API user;
- authenticated state;
- token ability present;
- token ability absent;
- negative ability checks;
- unauthenticated requests.

Result:

**7 tests / 8 assertions**

### Full Test Suite

After completing the feature:

**119 tests / 231 assertions — all passing**

Additional validation:

```text
git diff --check
```

Result:

**clean**

---

## 14. Design Principles

This feature follows the CoreAuth architectural principles:

1. **Laravel remains the foundation.**
2. **Sanctum remains responsible for token ability logic.**
3. **CoreAuth provides a stable package-level contract.**
4. **No duplicated Sanctum behavior is introduced.**
5. **No unnecessary manager is created.**
6. **Unauthenticated or unsupported users fail safely with `false`.**
7. **API token abilities remain separate from application authorization.**
8. **The public API is kept small and focused.**

---

## 15. Final Architecture

After this feature, the API authentication architecture is:

```text
CoreAuth
│
├── API Token Management
│   └── ApiTokenManager
│       ├── createToken()
│       ├── tokens()
│       ├── revoke()
│       └── revokeAll()
│
└── API Token Authentication
    └── ApiAuthenticationManager
        ├── check()
        ├── user()
        ├── tokenCan()
        └── tokenCant()
                │
                ▼
        Laravel Sanctum
        └── HasApiTokens
```

The authorization layer remains independent:

```text
AuthorizationManager
        │
        ▼
Laravel Gate / Policies
```

---

## 16. Feature Status

**Status:** Completed and merged

**Feature branch:** `feature/api-token-abilities`

**Commit:** `58ada46 feat: add API token abilities`

**Pull Request:** #23

**Final verification:**

- 119 tests passed
- 231 assertions passed
- `git diff --check` passed

---

## 17. Conclusion

API Token Abilities completes the current API token authentication functionality by exposing Sanctum token abilities through the CoreAuth abstraction.

The implementation remains intentionally lightweight:

```text
CoreAuth Contract
       ↓
ApiAuthenticationManager
       ↓
Sanctum
       ↓
Current API Token
       ↓
Ability Check
```

This keeps CoreAuth reusable and extensible while preserving Laravel Sanctum as the underlying authentication and token-ability engine.
