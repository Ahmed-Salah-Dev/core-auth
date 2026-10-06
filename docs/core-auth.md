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
├── User Management
│   └── UserManager
│
├── API Token Management
│   └── ApiTokenManager
│
└── API Token Authentication
    └── ApiAuthenticationManager
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

```text
API Token Management
      │
      ▼
ApiTokenManager
      │
      ▼
Laravel Sanctum
```

```text
API Token Authentication
      │
      ▼
ApiAuthenticationManager
      │
      ▼
Laravel Sanctum Guard
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
ApiTokenManagerInterface
ApiAuthenticationManagerInterface
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
API Token Management → ApiTokenManager
API Token Authentication → ApiAuthenticationManager
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

```text
ApiTokenManagerInterface
        ↓
ApiTokenManager
```

```text
ApiAuthenticationManagerInterface
        ↓
ApiAuthenticationManager
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
├── UserException
└── ApiTokenException
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
API Token Management
API Token Authentication
API Token Abilities
Service Provider Integration
Contracts
Package Exceptions
```

The current verified project state is:

```text
119 tests
231 assertions
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

## API Token Management

```text
ApiTokenManagerInterface

├── createToken()
├── tokens()
├── revoke()
└── revokeAll()
```

## API Token Authentication

```text
ApiAuthenticationManagerInterface

├── check()
├── user()
├── tokenCan()
└── tokenCant()
```

---

# 62. Example Application Architecture

A typical application service can depend on CoreAuth Contracts:

```php
use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;

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
│   ├── UserManagerInterface.php
│   ├── ApiTokenManagerInterface.php
│   └── ApiAuthenticationManagerInterface.php
│
├── Data/
│   └── ApiTokenResult.php
│
├── Exceptions/
│   ├── CoreAuthException.php
│   ├── AuthenticationException.php
│   ├── EmailVerificationException.php
│   ├── AuthorizationException.php
│   ├── UserException.php
│   └── ApiTokenException.php
│
├── Services/
│   ├── AuthManager.php
│   ├── AuthGuard.php
│   ├── PasswordResetManager.php
│   ├── EmailVerificationManager.php
│   ├── AuthorizationManager.php
│   ├── UserManager.php
│   ├── ApiTokenManager.php
│   └── ApiAuthenticationManager.php
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
│   ├── AuthenticationEventsTest.php
│   └── ApiTokenManagerTest.php
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
    ├── ApiTokenManagerTest.php
    ├── ApiTokenExceptionTest.php
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

API Token Management            ✓
API Token Creation              ✓
API Token Retrieval             ✓
API Token Revocation            ✓
API Token Abilities             ✓
API Token Expiration            ✓
API Token Exception             ✓
Sanctum Integration             ✓

API Token Authentication        ✓
API Authentication Manager      ✓
Current API User                ✓
API Authentication Check        ✓
API Token Ability Checks        ✓

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
104 tests
196 assertions
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
├── User Management
│   ├── UserManagerInterface          ✓
│   ├── UserManager                   ✓
│   ├── UserException                 ✓
│   ├── User Configuration            ✓
│   ├── find()                        ✓
│   ├── findBy()                      ✓
│   └── create()                      ✓
│
├── API Token Management
│   ├── ApiTokenManagerInterface      ✓
│   ├── ApiTokenManager               ✓
│   ├── ApiTokenException             ✓
│   ├── ApiTokenResult                ✓
│   ├── createToken()                 ✓
│   ├── tokens()                      ✓
│   ├── revoke()                      ✓
│   ├── revokeAll()                   ✓
│   ├── Token Abilities               ✓
│   └── Token Expiration              ✓
│
└── API Token Authentication
    ├── ApiAuthenticationManagerInterface ✓
    ├── ApiAuthenticationManager          ✓
    ├── check()                           ✓
    ├── user()                            ✓
    ├── tokenCan()                        ✓
    └── tokenCant()                       ✓
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
       ┌───────────────────────────┼───────────────────────────┼───────────────────────────┐
       │                           │                           │                           │
       ▼                           ▼                           ▼                           ▼
 Authentication              Authorization               User Management          API Token Management
       │                           │                           │                           │
       ▼                           ▼                           ▼                           ▼
  AuthManager             AuthorizationManager             UserManager             ApiTokenManager
       │                           │                           │                           │
       ▼                           ▼                           ▼                           ▼
 Laravel Auth                Laravel Gate                 Eloquent                 Laravel Sanctum
                                                                                           │
                                                                                           ▼
                                                                            API Token Authentication
                                                                                           │
                                                                                           ▼
                                                                            ApiAuthenticationManager
                                                                                           │
                                                                                           ▼
                                                                                  Sanctum Guard
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
│  Sanctum Token Infrastructure                            │
  Framework Infrastructure                               │
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

# 91. API Token Management

API Token Management provides a focused abstraction for creating and managing personal API tokens for authenticatable users.

The feature is built on top of:

```text
Laravel Sanctum
```

CoreAuth does not implement its own token storage, hashing, token lookup, or token authentication mechanism.

Instead:

```text
Application
      │
      ▼
ApiTokenManagerInterface
      │
      ▼
ApiTokenManager
      │
      ▼
Laravel Sanctum
      │
      ▼
Personal Access Tokens
```

The current responsibility includes:

```text
Create API Token
Retrieve User Tokens
Revoke Specific Token
Revoke All User Tokens
Token Abilities
Token Expiration
```

---

## 91.1 ApiTokenManagerInterface

The public Contract is:

```php
public function createToken(
    Authenticatable $user,
    string $name,
    array $abilities = [],
    ?DateTimeInterface $expiresAt = null
): ApiTokenResult;

public function tokens(
    Authenticatable $user
): Collection;

public function revoke(
    Authenticatable $user,
    int|string $tokenId
): void;

public function revokeAll(
    Authenticatable $user
): void;
```

Application code should depend on:

```text
ApiTokenManagerInterface
```

rather than the concrete:

```text
ApiTokenManager
```

---

## 91.2 Sanctum Requirement

The given user must support Laravel Sanctum API tokens through:

```php
Laravel\\Sanctum\\HasApiTokens
```

The application user model therefore needs to use the Sanctum trait.

Conceptually:

```text
User
 │
 └── HasApiTokens
       │
       ▼
Sanctum Token Support
```

If the supplied user does not use the required trait, CoreAuth throws:

```text
ApiTokenException
```

This provides an explicit package-level boundary instead of allowing an unsupported user object to fail later with an unrelated method error.

---

## 91.3 createToken()

A new API token can be created with:

```php
$result = $apiTokens->createToken(
    $user,
    'mobile-app'
);
```

The method accepts:

```text
User
Token Name
Abilities
Optional Expiration
```

The returned value is:

```text
ApiTokenResult
```

The manager delegates token creation to Laravel Sanctum.

CoreAuth does not generate or hash tokens independently.

---

## 91.4 Token Name

Each token has an application-defined name:

```php
$result = $apiTokens->createToken(
    $user,
    'mobile-app'
);
```

The name can identify the client or purpose of the token, for example:

```text
mobile-app
web-client
admin-dashboard
integration
```

The name is stored with the token and is returned through the CoreAuth result object.

---

## 91.5 Token Abilities

Tokens may be created with a set of abilities:

```php
$result = $apiTokens->createToken(
    $user,
    'mobile-app',
    [
        'posts:read',
        'posts:write',
    ]
);
```

The Contract uses:

```php
array<int, string>
```

for the abilities list.

CoreAuth passes these abilities to Laravel Sanctum rather than implementing its own token-ability system.

The interpretation and enforcement of abilities remain part of the application's API authorization design and Sanctum integration.

---

## 91.6 Token Expiration

A token may optionally receive an expiration date:

```php
$result = $apiTokens->createToken(
    $user,
    'mobile-app',
    [],
    $expiresAt
);
```

The expiration parameter is:

```php
?DateTimeInterface
```

When no expiration is supplied:

```php
$expiresAt = null;
```

The manager passes the value to Laravel Sanctum.

CoreAuth therefore does not implement a second expiration mechanism.

---

## 91.7 ApiTokenResult

Token creation returns:

```text
ApiTokenResult
```

The DTO contains:

```php
public int|string $id;

public string $name;

public string $token;

public ?DateTimeInterface $expiresAt;
```

Example:

```php
$result = $apiTokens->createToken(
    $user,
    'mobile-app'
);

$result->id;
$result->name;
$result->token;
$result->expiresAt;
```

The plain-text token is returned as part of the creation result because it is needed by the client to authenticate subsequent API requests.

Applications should handle the returned token as sensitive authentication material.

The token value is not reconstructed by CoreAuth after creation.

---

## 91.8 Retrieving User Tokens

All tokens belonging to a user can be retrieved through:

```php
$tokens = $apiTokens->tokens($user);
```

The return type is:

```php
Illuminate\\Support\\Collection
```

The manager delegates retrieval to the user's Sanctum token relationship.

```text
User
  │
  ▼
tokens()
  │
  ▼
Personal Access Tokens
```

The operation is scoped to the supplied user.

---

## 91.9 Revoking a Specific Token

A specific token belonging to a user can be revoked through:

```php
$apiTokens->revoke(
    $user,
    $tokenId
);
```

The token identifier accepts:

```php
int|string
```

The operation is explicitly scoped through the supplied user:

```text
User
  │
  ▼
tokens()
  │
  ▼
where token id
  │
  ▼
delete
```

This prevents a user from revoking another user's token through this manager API.

---

## 91.10 Revoking All Tokens

All API tokens belonging to a user can be revoked through:

```php
$apiTokens->revokeAll($user);
```

The operation delegates to:

```php
$user->tokens()->delete();
```

This is useful when an application needs to invalidate all personal API tokens associated with a user.

For example:

```text
Password Security Event
        │
        ▼
Revoke All API Tokens
```

The decision about when to revoke all tokens remains an application-level security policy.

---

## 91.11 API Token Exception

API token-specific failures use:

```text
ApiTokenException
```

The hierarchy is:

```text
CoreAuthException
       │
       └── ApiTokenException
```

The current manager uses this exception when the supplied user does not support Sanctum API tokens.

The package does not catch and hide every underlying Laravel or database exception.

Unexpected infrastructure failures remain visible unless a meaningful CoreAuth exception boundary exists.

---

## 91.12 Container Integration

The API token manager is registered through:

```text
src/CoreAuthServiceProvider.php
```

The binding is:

```text
ApiTokenManagerInterface
        ↓
ApiTokenManager
```

The manager is registered as a singleton.

Therefore:

```php
app(ApiTokenManagerInterface::class);
```

resolves the configured CoreAuth implementation.

Applications should depend on the Contract:

```php
use AhmedSalahDev\\CoreAuth\\Contracts\\ApiTokenManagerInterface;

final class TokenService
{
    public function __construct(
        private readonly ApiTokenManagerInterface $apiTokens,
    ) {
    }
}
```

The application does not need to instantiate:

```text
ApiTokenManager
```

directly.

---

## 91.13 API Token Flow

The intended high-level flow is:

```text
Application
      │
      ▼
ApiTokenManagerInterface
      │
      ▼
ApiTokenManager
      │
      ▼
Laravel Sanctum
      │
      ├── Create Token
      ├── Store Token
      ├── Hash Token
      └── Manage Token Relationship
```

For an API request, the broader application flow is:

```text
API Request
     │
     ▼
Laravel / Sanctum Token Authentication
     │
     ▼
Authenticated User
     │
     ▼
AuthorizationManager
     │
     ▼
Gate / Policy
     │
     ▼
Application Resource
```

This keeps token management separate from authorization.

---

## 91.14 API Token Responsibilities

CoreAuth:

```text
Contract
Token Manager API
Token Result DTO
Token Creation Delegation
Token Retrieval Delegation
Token Revocation Delegation
Sanctum Capability Validation
Exception Boundary
Container Integration
```

Laravel Sanctum:

```text
Token Storage
Token Hashing
Token Authentication
Personal Access Token Infrastructure
Token Ability Infrastructure
Token Relationship
```

Application:

```text
Token Naming Policy
Ability Design
Token Distribution
Token Storage on the Client
Token Revocation Policy
Security Policy
API Authorization Rules
```

This separation prevents `ApiTokenManager` from becoming a replacement for Sanctum.

---

## 91.15 API Token Testing

API Token Management is tested at both unit and integration levels.

Unit tests verify:

```text
Contract Implementation
Public API
Unsupported User Handling
Exception Behavior
```

Integration tests verify:

```text
Token Creation
Token Abilities
Token Expiration
Token Retrieval
Specific Token Revocation
All Token Revocation
User Isolation
```

The integration tests use Laravel's database infrastructure and Sanctum so that token behavior is verified through the real integration boundary.

---

## 91.16 API Token Architectural Boundary

The API token feature follows the same architectural rule as the rest of CoreAuth:

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
Laravel Sanctum
```

CoreAuth adds:

```text
Stable Contract
Focused Manager
DTO
Exception Boundary
Container Integration
Tests
```

Sanctum remains responsible for the underlying token infrastructure.


---

## 91.17 API Token Authentication

API Token Authentication provides a focused abstraction for identifying the currently authenticated API user and checking the abilities of the current Sanctum token.

It is intentionally separate from API Token Management.

```text
API Token Management
      │
      ▼
Create / Retrieve / Revoke Tokens

API Token Authentication
      │
      ▼
Authenticate Current Request
      │
      ▼
Inspect Current Token Abilities
```

The abstraction is:

```text
ApiAuthenticationManagerInterface
```

implemented by:

```text
ApiAuthenticationManager
```

The manager uses Laravel's:

```text
sanctum
```

guard rather than implementing token parsing or authentication itself.

---

## 91.18 ApiAuthenticationManagerInterface

The public Contract is:

```php
public function check(): bool;

public function user(): ?Authenticatable;

public function tokenCan(
    string $ability
): bool;

public function tokenCant(
    string $ability
): bool;
```

The application should depend on:

```text
ApiAuthenticationManagerInterface
```

rather than:

```text
ApiAuthenticationManager
```

This keeps the API authentication layer consistent with the rest of CoreAuth.

---

## 91.19 API Authentication Guard

`ApiAuthenticationManager` resolves the Laravel Sanctum guard through Laravel's authentication factory:

```text
ApiAuthenticationManager
        │
        ▼
Laravel Auth Factory
        │
        ▼
sanctum Guard
```

The manager does not create or validate bearer tokens itself.

Laravel Sanctum remains responsible for:

```text
Token Parsing
Token Lookup
Token Authentication
Authenticated User Resolution
Current Access Token
```

CoreAuth exposes a stable package-level API over that infrastructure.

---

## 91.20 check()

The `check()` method determines whether the current API request is authenticated.

Example:

```php
if ($apiAuth->check()) {
    // The current API request is authenticated.
}
```

The operation delegates to the Sanctum guard:

```php
$this->auth->guard('sanctum')->check();
```

The result is:

```text
true
```

when the current API request is authenticated, otherwise:

```text
false
```

CoreAuth does not implement a second authentication-state mechanism.

---

## 91.21 user()

The `user()` method retrieves the currently authenticated API user.

Example:

```php
$user = $apiAuth->user();
```

The return type is:

```php
?Illuminate\Contracts\Auth\Authenticatable
```

If no authenticated API user exists:

```text
null
```

is returned.

The manager delegates user resolution to the Sanctum guard.

---

## 91.22 tokenCan()

The `tokenCan()` method checks whether the current API token has a given ability.

Example:

```php
if ($apiAuth->tokenCan('posts:write')) {
    // The current token has the required ability.
}
```

The method delegates the ability check to the authenticated user's Sanctum token support.

Conceptually:

```text
Current API Request
        │
        ▼
Sanctum Guard
        │
        ▼
Authenticated User
        │
        ▼
HasApiTokens
        │
        ▼
Current Access Token
        │
        ▼
Ability Check
```

CoreAuth does not duplicate Sanctum's token ability storage or matching logic.

The current implementation safely returns:

```text
false
```

when there is no authenticated user or the user does not provide Sanctum API-token support.

---

## 91.23 tokenCant()

The `tokenCant()` method provides the inverse of `tokenCan()`.

Example:

```php
if ($apiAuth->tokenCant('posts:write')) {
    abort(403);
}
```

Its behavior is equivalent to:

```php
return ! $this->tokenCan($ability);
```

Therefore:

```text
tokenCan()  → ability exists
tokenCant() → ability does not exist
```

This keeps the public API small while providing both positive and negative checks.

---

## 91.24 API Token Authentication Example

A service or controller can depend on the Contract:

```php
use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;

final class ApiPostService
{
    public function __construct(
        private readonly ApiAuthenticationManagerInterface $apiAuth,
    ) {
    }

    public function update(): void
    {
        if ($this->apiAuth->tokenCant('posts:write')) {
            abort(403);
        }

        // Continue the operation...
    }
}
```

The application does not need to instantiate:

```text
ApiAuthenticationManager
```

directly.

Laravel's container resolves:

```php
app(ApiAuthenticationManagerInterface::class);
```

through the CoreAuth Service Provider.

---

## 91.25 API Token Management vs API Token Authentication

These are related but separate responsibilities.

### API Token Management

Responsible for:

```text
Create Token
Retrieve Tokens
Revoke Token
Revoke All Tokens
Assign Abilities
Assign Expiration
```

Implemented by:

```text
ApiTokenManager
```

### API Token Authentication

Responsible for:

```text
Check Current API Authentication
Retrieve Current API User
Check Current Token Ability
Check Missing Current Token Ability
```

Implemented by:

```text
ApiAuthenticationManager
```

The boundary is:

```text
ApiTokenManager
      │
      ▼
Manage Token Lifecycle

ApiAuthenticationManager
      │
      ▼
Use Current Token for the API Request
```

No functionality is duplicated between the two managers.

---

## 91.26 API Token Abilities vs Application Authorization

Token abilities and application authorization are intentionally different concepts.

Token abilities answer:

```text
What capability does this API token have?
```

Application authorization answers:

```text
Is this user allowed to perform this operation on this resource?
```

Therefore:

```text
API Token Ability
        │
        ▼
Can this token perform this category of operation?
        │
        ▼
Application Authorization
        │
        ▼
Can this authenticated user perform this action?
```

The application may use both layers.

For example:

```text
Request
   │
   ▼
Sanctum Authentication
   │
   ▼
ApiAuthenticationManager::check()
   │
   ▼
ApiAuthenticationManager::tokenCan('posts:write')
   │
   ▼
AuthorizationManager / Policy
   │
   ▼
Resource Operation
```

A token ability should not automatically replace a Laravel Gate or Policy decision when resource-level authorization is required.

---

## 91.27 API Token Authentication Responsibilities

CoreAuth:

```text
Contract
API Authentication Manager API
Sanctum Guard Delegation
Current User Access
Token Ability Checks
Container Integration
Unit Tests
Integration Tests
```

Laravel Sanctum:

```text
Bearer Token Authentication
Token Resolution
Current Access Token
Token Ability Infrastructure
HasApiTokens Integration
```

Application:

```text
Ability Naming
Ability Assignment
Route / Endpoint Policy
Resource Authorization
Business Rules
HTTP Enforcement
Security Policy
```

This boundary keeps API authentication focused and prevents CoreAuth from becoming a second Sanctum implementation.

---

## 91.28 API Token Authentication Testing

The API authentication layer is tested at both unit and integration levels.

Unit coverage verifies:

```text
Contract Implementation
Public Methods
Sanctum Guard Delegation
Authenticated User Retrieval
tokenCan() Positive Result
tokenCan() Negative Result
tokenCant() Positive Result
tokenCant() Negative Result
```

The unit suite for this feature contains:

```text
8 tests
27 assertions
```

Integration coverage verifies behavior against the real Laravel Sanctum integration, including:

```text
Authenticated API User
Authenticated State
Token Ability Present
Token Ability Missing
tokenCant() Behavior
Unauthenticated State
Unauthenticated User
```

The integration suite for this feature contains:

```text
7 tests
8 assertions
```

The complete verified project suite is:

```text
119 tests
231 assertions
```

and passes successfully.

---

## 91.29 API Token Authentication Architectural Flow

The complete API authentication flow is:

```text
API Request
      │
      ▼
Laravel Authentication Factory
      │
      ▼
Sanctum Guard
      │
      ▼
ApiAuthenticationManager
      │
      ├── check()
      │
      ├── user()
      │
      ├── tokenCan()
      │
      └── tokenCant()
      │
      ▼
Application API Logic
```

When resource authorization is required:

```text
API Request
      │
      ▼
Sanctum Authentication
      │
      ▼
ApiAuthenticationManager
      │
      ▼
Token Ability Check
      │
      ▼
AuthorizationManager
      │
      ▼
Laravel Gate / Policy
      │
      ▼
Application Resource
```

Each layer has a single purpose.


---

# 92. Final Summary

`core-auth` is currently structured as a modular Laravel package with independent responsibilities:

```text
Authentication
Password Reset
Email Verification
Authorization
User Management
API Token Management
API Token Authentication
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
ApiTokenManager
ApiAuthenticationManager
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
119 tests
231 assertions
```

with the test suite passing.

---

# 93. Final Architectural Principle

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
             ┌────────────────┼────────────────┬────────────────────────┐
             │                │                │                        │
             ▼                ▼                ▼                        ▼
      Authentication    Authorization     User Management      API Token Services
             │                │                │                        │
             ▼                ▼                ▼              ┌─────────┴─────────┐
       Laravel Auth      Laravel Gate      Eloquent            │                   │
                                                              ▼                   ▼
                                                       ApiTokenManager   ApiAuthenticationManager
                                                              │                   │
                                                              └─────────┬─────────┘
                                                                        ▼
                                                                  Laravel Sanctum
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
