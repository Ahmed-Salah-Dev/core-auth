# CoreAuth

## Unified Architecture & API Documentation

`core-auth` is a reusable Laravel authentication and authorization package designed to provide clear, contract-based abstractions over Laravel's existing security infrastructure.

The package does not attempt to replace Laravel authentication, authorization, password reset, or email verification mechanisms.

Instead, it provides:

```text
Laravel
   +
CoreAuth Abstractions
   +
Application
```

The main architectural goal is to keep application code dependent on stable Contracts while Laravel remains responsible for the underlying security mechanisms.

---

# 1. Package Overview

`core-auth` provides several independent security and user-management responsibilities:

```text
CoreAuth
│
├── Authentication
│   └── AuthManager
│
├── Password Reset
│   └── PasswordResetManager
│
├── Email Verification
│   └── EmailVerificationManager
│
├── Authorization
│   └── AuthorizationManager
│
└── User Management
    └── UserManager
```

Each responsibility is intentionally separated.

The package follows:

```text
Separation of Concerns
Contract-based Design
Dependency Injection
Laravel Delegation
Testability
Container Integration
Explicit Exception Boundaries
Future Extensibility
```

---

# 2. Architectural Philosophy

The package follows a simple principle:

```text
Application
      │
      ▼
CoreAuth Contract
      │
      ▼
CoreAuth Manager
      │
      ▼
Laravel Infrastructure
```

CoreAuth provides the abstraction.

Laravel provides the underlying implementation and infrastructure.

For example:

```text
Authentication
      │
      ▼
AuthManager
      │
      ▼
Laravel Auth
```

```text
Password Reset
      │
      ▼
PasswordResetManager
      │
      ▼
Laravel Password Broker
```

```text
Email Verification
      │
      ▼
EmailVerificationManager
      │
      ▼
Laravel Verification Infrastructure
```

```text
Authorization
      │
      ▼
AuthorizationManager
      │
      ▼
Laravel Gate
```

```text
User Management
      │
      ▼
UserManager
      │
      ▼
Eloquent Model
```

The package intentionally avoids duplicating Laravel's internal security logic.

---

# 3. Core Design Principles

## 3.1 Contract First

Application code should depend on interfaces:

```php
AuthManagerInterface
PasswordResetManagerInterface
EmailVerificationManagerInterface
AuthorizationManagerInterface
UserManagerInterface
```

rather than concrete implementations.

This provides:

```text
Loose Coupling
Dependency Inversion
Testability
Replaceability
API Stability
```

---

## 3.2 Dependency Injection

CoreAuth managers use constructor injection for their dependencies.

Examples include:

```text
Laravel Auth Factory
Laravel Gate
Laravel Password Broker
Laravel Hasher
Configuration
```

Static Facade dependencies are avoided where constructor injection provides a clearer boundary.

---

## 3.3 Laravel Delegation

CoreAuth does not recreate Laravel functionality.

Instead:

```text
CoreAuth API
      │
      ▼
Laravel Contract / Infrastructure
      │
      ▼
Laravel implementation
```

Laravel remains responsible for the actual framework-level behavior.

---

## 3.4 Small Abstractions

Each manager should have a focused responsibility.

The package does not attempt to create a large universal security manager.

Instead:

```text
Authentication → AuthManager
Password Reset → PasswordResetManager
Email Verification → EmailVerificationManager
Authorization → AuthorizationManager
User Management → UserManager
```

This keeps the architecture modular.

---

# 4. Authentication

## 4.1 Responsibility

Authentication is responsible for determining and managing the authenticated user's session.

The current authentication abstraction covers:

```text
Login
Logout
Authentication State
Authenticated User
Named Guards
Remember Me
Authentication Events
Authentication Exceptions
```

Authentication is exposed through:

```text
AuthManagerInterface
```

and implemented by:

```text
AuthManager
```

---

# 5. Authentication Architecture

```text
Application
      │
      ▼
AuthManagerInterface
      │
      ▼
AuthManager
      │
      ▼
Laravel Auth Factory
      │
      ▼
Laravel Guard
      │
      ▼
AuthGuard
```

The package keeps Laravel's authentication infrastructure underneath the CoreAuth API.

---

# 6. AuthManagerInterface

The authentication Contract provides:

```php
public function login(
    array $credentials,
    bool $remember = false
): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;

public function guard(
    string $name
): GuardInterface;
```

---

# 7. Login

Login accepts a flexible credentials array:

```php
$auth->login([
    'email' => $email,
    'password' => $password,
]);
```

The API is not restricted to email.

Applications may use identifiers such as:

```text
email
username
phone
employee_id
custom identifiers
```

depending on the configured Laravel authentication provider.

The manager delegates the authentication attempt to Laravel.

---

# 8. Remember Me

Login supports optional Remember Me behavior:

```php
$auth->login(
    $credentials,
    true
);
```

The default remains:

```php
$remember = false;
```

Therefore existing calls remain compatible:

```php
$auth->login($credentials);
```

The actual remember-me lifecycle remains the responsibility of Laravel's authentication guard.

CoreAuth passes the requested flag to the underlying guard rather than implementing persistent authentication itself.

---

# 9. Authentication Guards

CoreAuth supports named guards through:

```php
$auth->guard('web');
```

The returned object implements:

```text
GuardInterface
```

and is backed by:

```text
AuthGuard
```

Architecture:

```text
AuthManager
      │
      ▼
Laravel Guard
      │
      ▼
AuthGuard
      │
      ▼
GuardInterface
```

---

# 10. GuardInterface

The guard abstraction exposes:

```php
public function login(
    array $credentials
): bool;

public function logout(): void;

public function check(): bool;

public function user(): mixed;
```

The guard acts as a controlled abstraction around Laravel's guard implementation.

---

# 11. Authentication User

Authenticated users are exposed through Laravel's:

```php
Illuminate\Contracts\Auth\Authenticatable
```

The authentication layer therefore does not depend on:

```text
App\Models\User
```

or another application-specific model.

This allows applications to use their own authenticatable implementation.

---

# 12. Authentication Events

Authentication integrates with Laravel's authentication events.

The underlying Laravel authentication lifecycle remains responsible for events such as:

```text
Attempting
Authenticated
Failed
Login
Logout
```

CoreAuth does not implement a parallel event system.

This keeps event behavior aligned with Laravel.

---

# 13. Authentication Exceptions

Authentication errors can be represented by:

```text
AuthenticationException
```

which extends:

```text
CoreAuthException
```

The exception boundary preserves:

```text
Message
Code
Previous Exception
```

This gives applications a package-level exception type while retaining the original cause.

---

# 14. Authentication Responsibilities

CoreAuth:

```text
Contract
Authentication API
Guard Abstraction
Exception Boundary
Dependency Injection
Container Integration
```

Laravel:

```text
Credential Validation
Providers
Guards
Sessions
Remember Me
Authentication Events
Authentication Infrastructure
```

---

# 15. Password Reset

Password Reset is intentionally separated from Authentication.

Authentication answers:

```text
Is the user authenticated?
```

Password Reset answers:

```text
How can an account recover access after a forgotten password?
```

The abstraction is:

```text
PasswordResetManagerInterface
```

implemented by:

```text
PasswordResetManager
```

---

# 16. Password Reset Architecture

```text
Application
      │
      ▼
PasswordResetManagerInterface
      │
      ▼
PasswordResetManager
      │
      ▼
Laravel PasswordBroker
      │
      ├── Token Generation
      ├── Token Storage
      ├── Token Validation
      ├── Expiration
      ├── User Resolution
      └── Reset Notification
```

CoreAuth delegates the password-reset lifecycle to Laravel.

---

# 17. Password Reset API

The current public API is:

```php
public function sendResetLink(
    array $credentials
): bool;

public function reset(
    array $credentials,
    string $token,
    string $password
): bool;
```

---

# 18. Sending Reset Links

Example:

```php
$passwordReset->sendResetLink([
    'email' => $email,
]);
```

Credentials remain flexible and are not hard-coded to one identifier.

Laravel's Password Broker remains responsible for:

```text
User Resolution
Token Generation
Token Storage
Expiration
Reset Notification
```

The manager converts the relevant broker status into the package's Boolean API.

---

# 19. Resetting the Password

Example:

```php
$passwordReset->reset(
    [
        'email' => $email,
    ],
    $token,
    $newPassword
);
```

The manager delegates the reset operation to Laravel.

Password hashing and persistence use Laravel's configured infrastructure.

CoreAuth does not create its own password hashing mechanism.

---

# 20. Password Reset Error Philosophy

There is a distinction between:

```text
Expected Password Reset Failure
```

and:

```text
Unexpected Infrastructure Failure
```

Expected reset failures may result in:

```text
false
```

while unexpected exceptions from underlying infrastructure are not unnecessarily hidden.

This keeps the abstraction simple while preserving important failures.

---

# 21. Email Verification

Email Verification is a separate responsibility from Authentication.

The abstraction is:

```text
EmailVerificationManagerInterface
```

implemented by:

```text
EmailVerificationManager
```

---

# 22. Email Verification Architecture

```text
Application
      │
      ▼
EmailVerificationManagerInterface
      │
      ▼
EmailVerificationManager
      │
      ▼
Laravel Authentication
      │
      ▼
Authenticated User
      │
      ▼
Laravel Email Verification Infrastructure
```

The manager works with the currently authenticated user.

---

# 23. Email Verification Requirements

The authenticated user must support:

```php
Illuminate\Contracts\Auth\MustVerifyEmail
```

The manager does not depend on:

```text
App\Models\User
```

This keeps the implementation model-independent.

---

# 24. Email Verification API

The public API is:

```php
public function hasVerifiedEmail(): bool;

public function sendVerificationNotification(): void;
```

Example:

```php
if (! $verification->hasVerifiedEmail()) {
    $verification->sendVerificationNotification();
}
```

---

# 25. Laravel Delegation

CoreAuth delegates the actual verification lifecycle to Laravel.

Laravel remains responsible for mechanisms such as:

```text
Verification URLs
Signed URLs
Verification Requests
Verification Middleware
Notifications
Verification Events
Verification Lifecycle
```

CoreAuth intentionally does not introduce:

```text
verify()
```

or a custom verification URL system.

---

# 26. Email Verification Exceptions

When the current authenticated user does not support email verification, CoreAuth exposes:

```text
EmailVerificationException
```

The package therefore provides a clear package-level error boundary for unsupported verification behavior.

---

# 27. Authorization

Authorization is intentionally independent from Authentication.

Authentication asks:

```text
Who is the user?
```

Authorization asks:

```text
Can this user perform this action?
```

The abstraction is:

```text
AuthorizationManagerInterface
```

implemented by:

```text
AuthorizationManager
```

---

# 28. Authorization Architecture

```text
Application
      │
      ▼
AuthorizationManagerInterface
      │
      ▼
AuthorizationManager
      │
      ▼
Laravel Gate
      │
      ├── Gates
      ├── Policies
      └── Authorization Rules
```

CoreAuth does not implement an independent authorization engine.

---

# 29. Authorization API

The current Authorization Contract exposes:

```text
allows()
denies()
authorize()
check()
any()
none()
inspect()
forUser()
```

---

# 30. allows()

```php
public function allows(
    mixed $ability,
    mixed $arguments = []
): bool;
```

Example:

```php
$authorization->allows(
    'update',
    $post
);
```

The result is:

```text
true
```

or:

```text
false
```

The operation is delegated to Laravel Gate.

---

# 31. denies()

```php
public function denies(
    mixed $ability,
    mixed $arguments = []
): bool;
```

Example:

```php
$authorization->denies(
    'delete',
    $post
);
```

The result reflects Laravel Gate's authorization decision.

---

# 32. authorize()

```php
public function authorize(
    mixed $ability,
    mixed $arguments = []
): void;
```

This method is used when authorization must be enforced.

Example:

```php
$authorization->authorize(
    'update',
    $post
);
```

If authorization succeeds, execution continues.

If the underlying authorization operation throws, CoreAuth converts the failure into:

```text
AuthorizationException
```

while preserving the original exception as the previous exception.

---

# 33. AuthorizationException

The exception hierarchy is:

```text
CoreAuthException
       │
       └── AuthorizationException
```

The exception provides a package-specific boundary around authorization failures.

---

# 34. Advanced Authorization Checks

The Authorization API also provides:

```php
check()
any()
none()
inspect()
```

These methods delegate to Laravel Gate.

### check()

```php
$authorization->check(
    'update',
    $post
);
```

### any()

```php
$authorization->any(
    [
        'update',
        'delete',
    ],
    $post
);
```

### none()

```php
$authorization->none(
    [
        'update',
        'delete',
    ],
    $post
);
```

### inspect()

```php
$response = $authorization->inspect(
    'update',
    $post
);
```

`inspect()` preserves Laravel's:

```php
Illuminate\Auth\Access\Response
```

instead of reducing the result immediately to Boolean.

---

# 35. Authorization User Context

Authorization can be evaluated for a specific user through:

```php
forUser()
```

Signature:

```php
public function forUser(
    mixed $user
): static;
```

Example:

```php
$userAuthorization = $authorization->forUser(
    $user
);

$userAuthorization->allows(
    'update',
    $post
);
```

The operation delegates user context selection to:

```php
$this->gate->forUser($user);
```

---

# 36. Authorization and Arguments

Abilities are not restricted to one identifier type.

For example:

```text
update
delete
publish
archive
```

Arguments may be:

```php
$post
```

or:

```php
[$post, $category]
```

or another structure supported by the application's Gate or Policy.

CoreAuth passes the arguments to Laravel without implementing its own argument interpretation.

---

# 37. Authorization Responsibilities

CoreAuth:

```text
Contract
Manager API
Gate Delegation
Exception Boundary
Dependency Injection
Container Binding
```

Laravel:

```text
Gates
Policies
Ability Resolution
Authorization Rules
User Context
Authorization Response
```

CoreAuth does not create:

```text
Roles
Permission Tables
Permission Models
ACL Engine
Custom Policy Engine
```

unless a future real requirement justifies such an abstraction.

---

# 38. User Management

User Management provides a focused abstraction around user retrieval and creation.

The Contract is:

```text
UserManagerInterface
```

implemented by:

```text
UserManager
```

The current responsibility includes:

```text
Find User
Find User by Attributes
Create User
```

---

# 39. UserManagerInterface

The current Contract is:

```php
public function find(
    int|string $id
): ?Authenticatable;

public function findBy(
    array $attributes
): ?Authenticatable;

public function create(
    array $attributes
): Authenticatable;
```

The returned user type is:

```php
Illuminate\Contracts\Auth\Authenticatable
```

---

# 40. User Configuration

The package provides configuration through:

```text
config/core-auth.php
```

The relevant configuration is:

```php
return [
    'user' => [
        'model' => null,
    ],
];
```

Applications configure their authenticatable Eloquent model through:

```text
core-auth.user.model
```

---

# 41. User Model Requirements

The configured model must satisfy both:

```text
Eloquent Model
```

and:

```text
Authenticatable
```

Conceptually:

```text
Configured User Model
       │
       ├── Eloquent Model
       │
       └── Authenticatable
```

This ensures that UserManager can work with an application-specific user model while retaining the authentication contract expected by Laravel.

---

# 42. UserManager Validation

`UserManager` validates the configured model during construction.

Invalid configuration results in:

```text
UserException
```

The package therefore fails early when the required user model configuration is missing or invalid.

The package-specific exception hierarchy is:

```text
CoreAuthException
       │
       └── UserException
```

---

# 43. find()

Example:

```php
$userManager->find($id);
```

The operation delegates to the configured Eloquent model.

If the user exists:

```text
Authenticatable
```

is returned.

If no user exists:

```text
null
```

is returned.

---

# 44. findBy()

Example:

```php
$userManager->findBy([
    'email' => $email,
]);
```

The attributes are passed to the configured model query.

This allows applications to search using different attributes without hard-coding a specific identifier.

---

# 45. create()

Example:

```php
$userManager->create([
    'name' => $name,
    'email' => $email,
    'password' => $password,
]);
```

The attributes are passed to the configured Eloquent model for creation.

Password hashing and other application-specific preparation should be handled according to the application's Laravel configuration and model behavior.

CoreAuth does not introduce a separate persistence engine.

---

# 46. User Management Responsibilities

CoreAuth:

```text
User Contract
User Manager API
Configured Model Validation
User Retrieval Abstraction
User Creation Abstraction
Container Integration
```

The application's Eloquent model remains responsible for:

```text
Model Definition
Attributes
Casts
Relationships
Model-specific Behavior
Persistence Rules
```

---

# 47. Service Container Integration

CoreAuth registers its managers through:

```text
src/CoreAuthServiceProvider.php
```

The package exposes Contracts through Laravel's Service Container.

Conceptually:

```text
Contract
   │
   ▼
Implementation
```

The current managers are registered as singletons.

---

# 48. Container Bindings

The main bindings are:

```text
AuthManagerInterface
        ↓
AuthManager
```

```text
PasswordResetManagerInterface
        ↓
PasswordResetManager
```

```text
EmailVerificationManagerInterface
        ↓
EmailVerificationManager
```

```text
AuthorizationManagerInterface
        ↓
AuthorizationManager
```

```text
UserManagerInterface
        ↓
UserManager
```

For managers that require scalar or configuration-based dependencies, the Service Provider supplies those dependencies explicitly.

---

# 49. Singleton Behavior

The managers are registered as:

```text
Singleton
```

Therefore the Laravel Container returns the same manager instance during the same application container lifecycle.

This has been explicitly tested for the relevant service-provider bindings.

---

# 50. Dependency Boundaries

The current architecture can be summarized as:

```text
Application
      │
      ├──────────────────────────┐
      │                          │
      ▼                          ▼
CoreAuth Contracts          CoreAuth Managers
      │                          │
      └──────────────┬───────────┘
                     ▼
              Laravel Services
```

The package avoids direct coupling between application code and Laravel implementation details where a stable Contract is appropriate.

---

# 51. Exception Architecture

CoreAuth uses a common exception hierarchy:

```text
CoreAuthException
│
├── AuthenticationException
├── EmailVerificationException
├── AuthorizationException
└── UserException
```

The exception types correspond to package responsibilities.

Password Reset currently does not require a dedicated package-specific exception for its normal Boolean failure API.

---

# 52. Exception Philosophy

The purpose of package exceptions is not to hide every Laravel exception.

Instead, exception boundaries should be introduced where they provide a meaningful package-level abstraction.

For example:

```text
Authentication
      ↓
AuthenticationException
```

```text
Authorization
      ↓
AuthorizationException
```

```text
Email Verification
      ↓
EmailVerificationException
```

```text
User Management
      ↓
UserException
```

The original exception can be preserved where wrapping is part of the manager's boundary.

---

# 53. Testing Philosophy

Testing follows the same modular architecture as the package.

```text
Contract
   ↓
Unit Tests
   ↓
Service Provider Tests
   ↓
Container Integration
   ↓
Full Test Suite
```

The goal is to verify CoreAuth behavior without unnecessarily re-testing Laravel itself.

---

# 54. Unit Testing

Unit tests focus on manager behavior.

For integrations such as Laravel Gate, mocks are used when the goal is to isolate CoreAuth.

For example:

```text
AuthorizationManager
       │
       ▼
Mock Gate
```

This allows tests to verify:

```text
Correct method delegation
Correct arguments
Returned values
Exception handling
```

---

# 55. Contract Testing

Contract tests ensure that implementations satisfy their declared interfaces.

For example:

```php
$this->assertInstanceOf(
    AuthorizationManagerInterface::class,
    $manager
);
```

The same principle applies to the other managers.

Contract testing protects the public API from accidental divergence between interface and implementation.

---

# 56. Service Provider Testing

Service Provider tests verify:

```text
Contract Resolution
Correct Implementation
Singleton Behavior
Configuration-based Resolution
```

This is important because a manager can be correct in isolation while still being incorrectly registered in the application container.

---

# 57. Laravel Testbench

The package uses:

```text
Orchestra Testbench
```

to test Laravel integration without requiring a complete Laravel application project.

Testbench is used for scenarios involving:

```text
Service Provider
Container
Configuration
Eloquent
Authentication
Laravel Infrastructure
```

---

# 58. Database Testing

User Management tests use a real SQLite in-memory database for Eloquent behavior.

This allows tests to verify:

```text
find()
findBy()
create()
```

against actual Eloquent queries rather than replacing Eloquent itself with mocks.

This follows the principle:

```text
Mock external infrastructure where appropriate.
Use real framework behavior where integration is the behavior being tested.
```

---

# 59. Current Test Coverage Structure

The test suite contains tests covering:

```text
Authentication
Authentication Guards
Authentication Exceptions
Authentication Events
Remember Me
Password Reset
Email Verification
Authorization
User Management
Service Provider Integration
Contracts
Package Exceptions
```

The current verified project state is:

```text
88 tests
164 assertions
```

and the suite is passing.

---

# 60. Testing Boundaries

CoreAuth tests should focus on package behavior.

They should not duplicate tests for:

```text
Laravel Gate Internals
Laravel Password Broker Internals
Laravel Authentication Internals
Laravel Policy Resolution
Laravel Framework Internals
```

Instead, CoreAuth verifies that its managers integrate with those mechanisms correctly.

---

# 61. Public API

The current package API can be summarized as follows.

## Authentication

```text
AuthManagerInterface

├── login()
├── logout()
├── check()
├── user()
└── guard()
```

## Guards

```text
GuardInterface

├── login()
├── logout()
├── check()
└── user()
```

## Password Reset

```text
PasswordResetManagerInterface

├── sendResetLink()
└── reset()
```

## Email Verification

```text
EmailVerificationManagerInterface

├── hasVerifiedEmail()
└── sendVerificationNotification()
```

## Authorization

```text
AuthorizationManagerInterface

├── allows()
├── denies()
├── authorize()
├── check()
├── any()
├── none()
├── inspect()
└── forUser()
```

## User Management

```text
UserManagerInterface

├── find()
├── findBy()
└── create()
```

---

# 62. Example Application Architecture

A typical application service can depend on CoreAuth Contracts:

```php
use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;

final class PostService
{
    public function __construct(
        private readonly AuthManagerInterface $auth,
        private readonly AuthorizationManagerInterface $authorization,
    ) {
    }

    public function update($post): void
    {
        $this->authorization->authorize(
            'update',
            $post
        );

        // Continue update operation...
    }
}
```

The application does not need to instantiate:

```php
AuthManager
```

or:

```php
AuthorizationManager
```

directly.

Laravel's container resolves the Contracts.

---

# 63. Example Authentication Flow

```text
Application
      │
      ▼
AuthManagerInterface
      │
      ▼
AuthManager
      │
      ▼
Laravel Auth Factory
      │
      ▼
Guard
      │
      ▼
Authentication Result
```

---

# 64. Example Authorization Flow

```text
Application
      │
      ▼
AuthorizationManagerInterface
      │
      ▼
AuthorizationManager
      │
      ▼
Laravel Gate
      │
      ├── Gate
      └── Policy
      │
      ▼
Authorization Result
```

---

# 65. Example Password Reset Flow

```text
Application
      │
      ▼
PasswordResetManagerInterface
      │
      ▼
PasswordResetManager
      │
      ▼
PasswordBroker
      │
      ├── Resolve User
      ├── Generate Token
      ├── Validate Token
      ├── Handle Expiration
      └── Send Notification
```

---

# 66. Example Email Verification Flow

```text
Application
      │
      ▼
EmailVerificationManagerInterface
      │
      ▼
EmailVerificationManager
      │
      ▼
Authenticated User
      │
      ▼
Laravel Verification Infrastructure
```

---

# 67. Example User Management Flow

```text
Application
      │
      ▼
UserManagerInterface
      │
      ▼
UserManager
      │
      ▼
Configured Eloquent Model
      │
      ▼
Database
```

---

# 68. Authentication vs Authorization

These responsibilities must remain separate.

## Authentication

```text
Who is the user?
Is the user authenticated?
Login
Logout
Session
Remember Me
Authenticated User
```

Handled by:

```text
AuthManager
```

## Authorization

```text
Can the user perform this action?
```

Handled by:

```text
AuthorizationManager
```

This separation is fundamental to the package architecture.

---

# 69. Authentication vs User Management

User Management and Authentication are also separate.

Authentication manages the authenticated state:

```text
Login
Logout
Check
Current User
Guards
```

User Management provides access to user records:

```text
Find
Find By
Create
```

Therefore:

```text
Authentication
      ↓
AuthManager

User Management
      ↓
UserManager
```

This prevents the authentication manager from becoming a general-purpose user repository.

---

# 70. Authorization vs User Management

Authorization determines:

```text
Can this user perform an action?
```

User Management determines:

```text
How can the application retrieve or create a user?
```

Therefore they remain independent:

```text
UserManager
      │
      ▼
User Data

AuthorizationManager
      │
      ▼
Authorization Rules
```

---

# 71. Security Philosophy

CoreAuth does not claim to make application authorization rules secure automatically.

Security rules remain application responsibilities.

For authorization:

```text
Security Rules
      ↓
Laravel Gates / Policies
      ↓
AuthorizationManager
      ↓
Application
```

For authentication:

```text
Credentials
      ↓
Laravel Authentication
      ↓
AuthManager
      ↓
Application
```

CoreAuth organizes access to these systems but does not replace their underlying security mechanisms.

---

# 72. No Custom Permission System

The current Authorization implementation intentionally does not provide:

```text
Roles
Permissions Tables
Permission Models
Role Models
ACL Engine
Custom Policy Engine
```

This is deliberate.

Such abstractions should only be introduced when real requirements justify their design.

The current package uses Laravel's authorization infrastructure instead.

---

# 73. No Premature Abstraction

CoreAuth follows:

```text
Real Requirement
      ↓
Architecture Decision
      ↓
Contract
      ↓
Implementation
      ↓
Tests
      ↓
Documentation
```

rather than:

```text
Possible Future Requirement
      ↓
Premature Abstraction
```

This keeps the package maintainable and avoids unnecessary APIs.

---

# 74. Extensibility

The current architecture leaves room for future extensions without requiring them prematurely.

Potential areas can be evaluated independently, such as:

```text
Advanced Authorization APIs
Role / Permission Abstractions
Additional Policy Integrations
Authorization Helpers
Additional User Services
Application-level Security Services
```

These are not part of the current public API unless implemented, tested, and documented.

---

# 75. Current Project Structure

The main source architecture is:

```text
src/
├── Contracts/
│   ├── AuthManagerInterface.php
│   ├── GuardInterface.php
│   ├── PasswordResetManagerInterface.php
│   ├── EmailVerificationManagerInterface.php
│   ├── AuthorizationManagerInterface.php
│   └── UserManagerInterface.php
│
├── Exceptions/
│   ├── CoreAuthException.php
│   ├── AuthenticationException.php
│   ├── EmailVerificationException.php
│   ├── AuthorizationException.php
│   └── UserException.php
│
├── Services/
│   ├── AuthManager.php
│   ├── AuthGuard.php
│   ├── PasswordResetManager.php
│   ├── EmailVerificationManager.php
│   ├── AuthorizationManager.php
│   └── UserManager.php
│
└── CoreAuthServiceProvider.php
```

Tests are organized by responsibility:

```text
tests/
├── Fixtures/
│   └── User.php
│
├── Integration/
│   └── AuthenticationEventsTest.php
│
└── Unit/
    ├── AuthenticationExceptionTest.php
    ├── AuthGuardTest.php
    ├── AuthManagerContractTest.php
    ├── AuthManagerTest.php
    ├── CoreAuthExceptionTest.php
    ├── CoreAuthServiceProviderTest.php
    ├── PasswordResetManagerTest.php
    ├── EmailVerificationManagerTest.php
    ├── AuthorizationManagerTest.php
    ├── UserManagerTest.php
    └── ...
```

---

# 76. Service Provider

The main package integration point is:

```text
src/CoreAuthServiceProvider.php
```

Its responsibilities include:

```text
Configuration Registration
Manager Registration
Contract Bindings
Container Integration
```

The Provider intentionally keeps package registration centralized.

---

# 77. Configuration

CoreAuth configuration is located at:

```text
config/core-auth.php
```

The current user configuration is:

```php
'user' => [
    'model' => null,
],
```

The Service Provider merges the package configuration into Laravel's application configuration.

Application-specific configuration overrides the package defaults.

---

# 78. Package Container Philosophy

The container is used to provide:

```text
Stable Contract Resolution
Dependency Injection
Singleton Managers
Configuration-aware Construction
```

Applications should generally depend on:

```php
AuthorizationManagerInterface
```

rather than:

```php
AuthorizationManager
```

The same principle applies to the other managers.

---

# 79. Verification Workflow

Before committing a feature:

```bash
git status
git diff
git diff --check
vendor/bin/phpunit
```

Review the change size:

```bash
git diff --stat
git diff
```

After staging:

```bash
git diff --cached
git diff --cached --check
```

After committing:

```bash
git status
```

The expected final state is:

```text
working tree clean
```

---

# 80. Git Development Workflow

Features are developed using independent feature branches.

The standard flow is:

```text
develop
   │
   ▼
Feature Branch
   │
   ▼
Contract
   │
   ▼
Implementation
   │
   ▼
Tests
   │
   ▼
Container Integration
   │
   ▼
Documentation
   │
   ▼
Commit
   │
   ▼
Push
   │
   ▼
Pull Request
   │
   ▼
develop
```

After a feature is merged and `develop` is verified, the completed feature branch is removed locally and from GitHub.

---

# 81. Development Methodology

The project follows:

```text
Requirement
      ↓
Architecture Decision
      ↓
Contract
      ↓
Implementation
      ↓
Unit / Integration Tests
      ↓
Container Integration
      ↓
Full Test Suite
      ↓
Documentation
      ↓
Pull Request
```

Each feature should remain independently reviewable.

---

# 82. Current Completed Responsibilities

The current CoreAuth architecture contains:

```text
Authentication                 ✓
Generalized Credentials        ✓
Named Guards                   ✓
Typed Authenticated User       ✓
Authentication Exceptions      ✓
Authentication Events          ✓
Remember Me                    ✓

Password Reset                 ✓
Email Verification             ✓

Authorization                  ✓
Ability Checks                 ✓
Advanced Authorization Checks  ✓
User Context                   ✓
Authorization Exceptions       ✓

User Management                ✓
User Retrieval                 ✓
User Lookup by Attributes      ✓
User Creation                  ✓
User Configuration             ✓
User Exception                 ✓

Container Integration          ✓
Singleton Bindings             ✓
Contract Testing               ✓
Unit Testing                   ✓
Integration Testing            ✓
Laravel Testbench              ✓
Documentation                  ✓
```

---

# 83. Current Test Status

The current verified test suite is:

```text
88 tests
164 assertions
```

Expected status:

```text
OK
```

This represents the current state of the implemented package features rather than the historical test counts that appeared in earlier feature-specific documentation.

---

# 84. Current Architecture Status

```text
CoreAuth
│
├── Authentication
│   ├── AuthManagerInterface       ✓
│   ├── AuthManager                ✓
│   ├── GuardInterface             ✓
│   ├── AuthGuard                  ✓
│   ├── Remember Me                ✓
│   ├── Authentication Events      ✓
│   └── AuthenticationException   ✓
│
├── Password Reset
│   ├── PasswordResetManagerInterface ✓
│   └── PasswordResetManager          ✓
│
├── Email Verification
│   ├── EmailVerificationManagerInterface ✓
│   └── EmailVerificationManager          ✓
│
├── Authorization
│   ├── AuthorizationManagerInterface ✓
│   ├── AuthorizationManager          ✓
│   ├── AuthorizationException        ✓
│   ├── allows()                      ✓
│   ├── denies()                      ✓
│   ├── authorize()                   ✓
│   ├── check()                       ✓
│   ├── any()                         ✓
│   ├── none()                        ✓
│   ├── inspect()                     ✓
│   └── forUser()                     ✓
│
└── User Management
    ├── UserManagerInterface          ✓
    ├── UserManager                   ✓
    ├── UserException                 ✓
    ├── User Configuration            ✓
    ├── find()                        ✓
    ├── findBy()                      ✓
    └── create()                      ✓
```

---

# 85. Final Architecture

The current CoreAuth architecture is:

```text
                              Application
                                   │
          ┌────────────────────────┼────────────────────────┐
          │                        │                        │
          ▼                        ▼                        ▼
  AuthManagerInterface    PasswordResetManagerInterface    ...
          │                        │
          ▼                        ▼
     AuthManager          PasswordResetManager
          │                        │
          ▼                        ▼
   Laravel Auth            Password Broker
```

And the complete responsibility map is:

```text
                              Application
                                   │
       ┌───────────────────────────┼───────────────────────────┐
       │                           │                           │
       ▼                           ▼                           ▼
 Authentication              Authorization               User Management
       │                           │                           │
       ▼                           ▼                           ▼
  AuthManager             AuthorizationManager             UserManager
       │                           │                           │
       ▼                           ▼                           ▼
 Laravel Auth                Laravel Gate                 Eloquent
```

Alongside:

```text
                         Password Reset
                               │
                               ▼
                    PasswordResetManager
                               │
                               ▼
                       Laravel PasswordBroker
```

and:

```text
                       Email Verification
                               │
                               ▼
                  EmailVerificationManager
                               │
                               ▼
                Laravel Verification Infrastructure
```

---

# 86. Responsibility Boundaries

The final architecture can be summarized as:

```text
┌─────────────────────────────────────────────────────────┐
│                       CoreAuth                          │
│                                                         │
│  Contracts                                              │
│  Manager APIs                                           │
│  Exception Boundaries                                   │
│  Dependency Injection                                   │
│  Container Integration                                  │
│  Package-level Abstractions                             │
│                                                         │
└──────────────────────────┬──────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                        Laravel                          │
│                                                         │
│  Authentication                                         │
│  Guards                                                 │
│  Sessions                                               │
│  Password Broker                                        │
│  Email Verification                                     │
│  Gates                                                  │
│  Policies                                               │
│  Eloquent                                               │
│  Hashing                                                │
│  Framework Infrastructure                               │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

This boundary is one of the most important architectural decisions in CoreAuth.

---

# 87. What CoreAuth Is Not

CoreAuth is not intended to become:

```text
A replacement for Laravel Auth
A replacement for Laravel Gate
A replacement for Laravel Policies
A replacement for Laravel Password Broker
A replacement for Laravel Eloquent
A custom ACL framework
A custom permission engine
A second Laravel framework
```

Instead, it is:

```text
A reusable abstraction layer
over Laravel security infrastructure.
```

---

# 88. Design Quality Principles

The package architecture prioritizes:

```text
Single Responsibility
Separation of Concerns
Dependency Inversion
Contract-based APIs
Explicit Dependencies
Minimal Abstraction
Laravel Delegation
Testability
Configuration-driven Integration
Clear Exception Boundaries
```

These principles should continue to guide future features.

---

# 89. Future Feature Policy

Before adding a new feature, evaluate:

```text
1. Is there a real requirement?
2. Does the responsibility belong inside CoreAuth?
3. Does it need a new Contract?
4. Does it need a dedicated Manager?
5. Does Laravel already provide the underlying mechanism?
6. What is the correct dependency boundary?
7. What behavior must be tested?
8. What exception boundary is appropriate?
9. What container registration is required?
10. How should the feature be documented?
```

Only after these questions are answered should implementation begin.

---

# 90. Future Extension Strategy

Future development should follow:

```text
Real Requirement
      │
      ▼
Architecture Review
      │
      ▼
Contract
      │
      ▼
Implementation
      │
      ▼
Tests
      │
      ▼
Container Integration
      │
      ▼
Documentation
```

Potential future areas may include:

```text
Advanced User Services
Role / Permission Abstractions
Additional Authorization Helpers
Additional Authentication Services
Application-level Security Services
```

These remain future considerations and are not part of the current API unless implemented.

---

# 91. Final Summary

`core-auth` is currently structured as a modular Laravel package with independent responsibilities:

```text
Authentication
Password Reset
Email Verification
Authorization
User Management
```

Each responsibility exposes a Contract and an implementation.

The package uses Laravel's infrastructure instead of recreating it.

The central architecture is:

```text
Application
      │
      ▼
CoreAuth Contract
      │
      ▼
CoreAuth Manager
      │
      ▼
Laravel Infrastructure
```

The current public managers are:

```text
AuthManager
PasswordResetManager
EmailVerificationManager
AuthorizationManager
UserManager
```

The package provides:

```text
Contract-based APIs
Dependency Injection
Container Integration
Singleton Managers
Exception Boundaries
Laravel Delegation
Unit Testing
Integration Testing
Testbench Integration
Configuration Support
```

The current verified project state is:

```text
88 tests
164 assertions
```

with the test suite passing.

---

# 92. Final Architectural Principle

The most important principle of `core-auth` is:

```text
Do not rebuild Laravel.
Organize and abstract Laravel.
```

Therefore the intended architecture remains:

```text
                         Application
                              │
                              ▼
                        CoreAuth APIs
                              │
             ┌────────────────┼────────────────┐
             │                │                │
             ▼                ▼                ▼
      Authentication    Authorization     User Management
             │                │                │
             ▼                ▼                ▼
       Laravel Auth      Laravel Gate      Eloquent
             │
             ▼
       Laravel Infrastructure
```

CoreAuth should continue to add value through:

```text
Clear Contracts
Focused Managers
Stable APIs
Explicit Dependencies
Strong Tests
Clean Container Integration
Useful Exception Boundaries
Modular Architecture
```

while leaving Laravel responsible for the framework-level implementation.

This provides a foundation that can evolve without prematurely introducing unnecessary abstractions.
