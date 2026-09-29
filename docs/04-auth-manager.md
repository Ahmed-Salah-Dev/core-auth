# Core Auth — Authentication Manager

## 1. Phase

**Authentication Manager, Generalized Login, Guard Support, Typed Authenticated User Support, Authentication Exception Integration, Authentication Events Integration, and Remember Me Support**

---

# 2. Overview

يوفر `core-auth` طبقة Authentication abstraction فوق Laravel Authentication.

الهدف هو تقديم API واضحة وقابلة للتوسع للتعامل مع:

* Login
* Logout
* Authentication Check
* Authenticated User
* Named Guards
* Generalized Credentials
* Authentication Exceptions
* Laravel Authentication Events
* Remember Me

مع الحفاظ على مسؤولية Laravel عن تفاصيل Authentication infrastructure الداخلية.

التصميم الحالي لا يحاول إعادة بناء Laravel Authentication، وإنما يوفر طبقة package مستقلة يمكن تطويرها مستقبلًا دون ربط التطبيق مباشرة بالتفاصيل الداخلية.

---

# 3. Current Architecture

البنية الحالية:

```text
Application
     │
     ▼
AuthManagerInterface
     │
     ▼
AuthManager
     │
     ├── Default Authentication
     │
     └── Named Guards
              │
              ▼
         AuthGuard
              │
              ▼
     Laravel Stateful Guard
```

أما الاستثناءات:

```text
Laravel / Infrastructure Exception
              │
              ▼
   AuthenticationException
              │
              ▼
         Application
```

أما Authentication Events وRemember Me فتبقى مسؤوليتها الأساسية لدى Laravel Authentication infrastructure.

---

# 4. Design Goals

تم تصميم Authentication Manager لتحقيق الأهداف التالية:

* توفير API بسيطة للمصادقة.
* عدم فرض نوع محدد للـidentifier.
* دعم credentials عامة.
* دعم Named Guards.
* توفير abstraction مستقلة للـGuard.
* توفير User type واضح.
* توفير Authentication Exception boundary.
* الحفاظ على Previous Exception.
* دعم Remember Me من خلال Login API.
* الاعتماد على Laravel Authentication بدل إعادة تنفيذ بنيته الداخلية.
* دعم Laravel Authentication Events.
* توفير Unit Tests وIntegration Tests.
* الحفاظ على قابلية التوسع المستقبلية.
* الحفاظ على Backward Compatibility عند إضافة الخيارات الجديدة.

---

# 5. Current Environment

بيئة التطوير والاختبار الحالية:

```text
PHP 8.2.12
Laravel Framework 12.65.0
PHPUnit 11.5.56
Laravel Testbench 10
Mockery
```

---

# 6. Project Structure

البنية الحالية المتعلقة بـAuthentication:

```text
src/
├── Contracts/
│   ├── AuthManagerInterface.php
│   └── GuardInterface.php
│
├── Exceptions/
│   ├── CoreAuthException.php
│   └── AuthenticationException.php
│
├── Services/
│   ├── AuthManager.php
│   └── AuthGuard.php
│
└── CoreAuthServiceProvider.php
```

الاختبارات:

```text
tests/
├── Unit/
│   ├── AuthenticationExceptionTest.php
│   ├── AuthGuardTest.php
│   ├── AuthManagerContractTest.php
│   ├── AuthManagerTest.php
│   ├── CoreAuthExceptionTest.php
│   └── CoreAuthServiceProviderTest.php
│
└── Integration/
    └── AuthenticationEventsTest.php
```

---

# 7. AuthManagerInterface

المسار:

```text
src/Contracts/AuthManagerInterface.php
```

يمثل الـpublic contract الأساسي لـAuthentication Manager.

التوقيع الحالي:

```php
public function login(
    array $credentials,
    bool $remember = false
): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;

public function guard(string $name): GuardInterface;
```

---

# 8. Login API

عملية Login تقبل credentials عامة:

```php
public function login(
    array $credentials,
    bool $remember = false
): bool;
```

المعاملات:

```text
$credentials
$remember
```

حيث:

* `$credentials` تحتوي بيانات المصادقة.
* `$remember` تحدد ما إذا كان Remember Me مطلوبًا.

---

# 9. Generalized Credentials

لا تفرض الحزمة identifier محددًا.

يمكن أن تكون credentials مثل:

```text
email
username
phone
employee_id
national_id
custom identifier
```

مثال:

```php
[
    'email' => 'user@example.com',
    'password' => 'password',
]
```

أو:

```php
[
    'username' => 'ahmed',
    'password' => 'password',
]
```

أو:

```php
[
    'phone' => '123456789',
    'password' => 'password',
]
```

تحديد طريقة البحث عن المستخدم والتحقق من credentials مسؤولية Laravel Authentication وUser Provider.

---

# 10. Remember Me

تمت إضافة Remember Me كخيار اختياري إلى Login API.

الاستخدام:

```php
$authManager->login(
    $credentials,
    true
);
```

القيمة الافتراضية:

```php
false
```

وبالتالي فإن الاستخدام القديم:

```php
$authManager->login($credentials);
```

ما زال صالحًا ويعادل:

```php
$authManager->login(
    $credentials,
    false
);
```

وهذا يحافظ على Backward Compatibility عند استدعاء Login دون Remember Me.

---

# 11. Remember Me Flow

عند استخدام:

```php
$authManager->login(
    $credentials,
    true
);
```

يتم تمرير القيمة إلى Laravel:

```text
Application
     │
     ▼
AuthManagerInterface
     │
     ▼
AuthManager
     │
     ├── credentials
     │
     └── remember = true
              │
              ▼
     Laravel Stateful Guard
              │
              ▼
     attempt($credentials, true)
```

وعند:

```php
$remember = false;
```

يتم تنفيذ:

```php
attempt($credentials, false);
```

---

# 12. Remember Me Responsibility

`core-auth` لا يعيد تنفيذ Remember Me infrastructure.

الحزمة توفر فقط:

```php
login($credentials, $remember);
```

ثم تفوض التنفيذ إلى Laravel Authentication.

Laravel مسؤول عن التفاصيل الداخلية مثل:

```text
Remember Token generation
Remember Token persistence
Remember Cookie
Session persistence
User restoration
```

وبالتالي:

```text
CoreAuth
    │
    │ API abstraction
    ▼
Laravel Authentication
    │
    ├── Session
    ├── Remember Token
    └── Remember Cookie
```

هذا يقلل coupling ويحافظ على توافق الحزمة مع Laravel Authentication lifecycle.

---

# 13. Remember Me Scope

الدعم الحالي لـRemember Me يشمل:

```text
✓ Remember parameter
✓ Default false
✓ Parameter forwarding
✓ Stateful Guard support
✓ Unit coverage
✓ Integration coverage
```

أما الاستعادة الكاملة للمستخدم من Remember Me cookie فلم تتم إضافتها كاختبار Integration مستقل حتى الآن.

---

# 14. Login Behavior

التدفق الأساسي:

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Laravel Auth Factory
     │
     ▼
Laravel Stateful Guard
     │
     ▼
attempt($credentials, $remember)
```

إذا نجحت المصادقة:

```text
true
```

إذا فشلت credentials:

```text
false
```

أما unexpected exceptions فتدخل إلى Authentication Exception Boundary.

---

# 15. Authentication Failure vs Exception

هناك فرق بين:

```text
Authentication Failure
```

و:

```text
Unexpected Exception
```

فشل Authentication الطبيعي:

```text
credentials invalid
       │
       ▼
Laravel Guard
       │
       ▼
false
```

بينما exception غير متوقع:

```text
Laravel / Infrastructure
       │
       ▼
Throwable
       │
       ▼
AuthenticationException
```

ولا يتم تحويل Authentication Failure الطبيعي إلى Exception.

---

# 16. AuthManager

المسار:

```text
src/Services/AuthManager.php
```

المسؤوليات الحالية:

* Login باستخدام Default Guard.
* تمرير credentials.
* تمرير Remember Me option.
* Logout.
* Check.
* User.
* إنشاء AuthGuard للـNamed Guard.
* تحويل unexpected exceptions إلى `AuthenticationException`.

---

# 17. AuthManager Dependencies

يستخدم `AuthManager`:

```php
Illuminate\Contracts\Auth\Factory
```

ويتم حقنه باستخدام Constructor Injection:

```php
public function __construct(
    private readonly AuthFactory $auth
) {
}
```

هذا يمنع `AuthManager` من الاعتماد مباشرة على concrete Laravel authentication implementation.

---

# 18. AuthManager Login Implementation

المفهوم الحالي:

```php
try {
    return $this->auth
        ->guard()
        ->attempt(
            $credentials,
            $remember
        );
} catch (Throwable $exception) {
    throw new AuthenticationException(
        $exception->getMessage(),
        (int) $exception->getCode(),
        $exception
    );
}
```

وبذلك تكون المسؤوليات واضحة:

```text
AuthManager
    │
    │ credentials + remember
    ▼
Laravel Stateful Guard
    │
    ▼
attempt()
```

---

# 19. AuthManager Logout

التوقيع:

```php
public function logout(): void;
```

التنفيذ يعتمد على Laravel Authentication:

```text
AuthManager
     │
     ▼
Laravel Guard
     │
     ▼
logout()
```

---

# 20. AuthManager Check

التوقيع:

```php
public function check(): bool;
```

القيمة:

```text
true
```

تعني أن هناك authenticated user.

والقيمة:

```text
false
```

تعني عدم وجود authenticated user.

---

# 21. AuthManager User

التوقيع:

```php
public function user(): ?Authenticatable;
```

القيمة المرجعة:

```text
Authenticatable
```

أو:

```text
null
```

عند عدم وجود authenticated user.

---

# 22. Named Guards

يوفر `AuthManager` إمكانية استخدام Named Guards:

```php
$authManager->guard('api');
```

القيمة المرجعة:

```php
GuardInterface
```

وليس Laravel Guard مباشرة.

التدفق:

```text
Application
     │
     ▼
AuthManager
     │
     ▼
guard('api')
     │
     ▼
AuthGuard
     │
     ▼
Laravel Stateful Guard
```

---

# 23. GuardInterface

المسار:

```text
src/Contracts/GuardInterface.php
```

يمثل abstraction مستقلة للـGuard.

التوقيع الحالي:

```php
public function login(
    array $credentials,
    bool $remember = false
): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;
```

---

# 24. AuthGuard

المسار:

```text
src/Services/AuthGuard.php
```

`AuthGuard` هو adapter بين:

```text
GuardInterface
```

و:

```text
Laravel StatefulGuard
```

البنية:

```text
Application
     │
     ▼
GuardInterface
     │
     ▼
AuthGuard
     │
     ▼
StatefulGuard
```

---

# 25. Why StatefulGuard?

يعتمد `AuthGuard` على:

```php
Illuminate\Contracts\Auth\StatefulGuard
```

لأن Login الحالي يستخدم:

```php
attempt(
    $credentials,
    $remember
);
```

وRemember Me جزء من Stateful Authentication flow.

لذلك الاعتماد على `StatefulGuard` يعكس المتطلبات الفعلية للـabstraction الحالية.

---

# 26. AuthGuard Login

التوقيع:

```php
public function login(
    array $credentials,
    bool $remember = false
): bool;
```

ويتم تمرير القيم مباشرة إلى Laravel:

```php
return $this->guard->attempt(
    $credentials,
    $remember
);
```

---

# 27. AuthGuard Exception Boundary

إذا حدث unexpected exception:

```text
StatefulGuard
     │
     ▼
Throwable
     │
     ▼
AuthenticationException
```

ويتم الحفاظ على الـoriginal exception:

```php
$exception
```

كـprevious exception.

---

# 28. Typed Authenticated User

تعتمد الحزمة على:

```php
Illuminate\Contracts\Auth\Authenticatable
```

بدل:

```php
mixed
```

لأن نوع authenticated user معروف ضمن Laravel Authentication contract.

التوقيع:

```php
public function user(): ?Authenticatable;
```

---

# 29. User Model Independence

لا تعتمد الحزمة على Model محدد مثل:

```text
App\Models\User
```

بل تعتمد على:

```php
Authenticatable
```

وهذا يسمح للتطبيق باستخدام User Model خاص به طالما يطبق Laravel Authentication contract المطلوب.

---

# 30. Authentication Events

تعتمد الحزمة على Laravel Authentication Events الموجودة أصلًا.

تم اختبار:

```text
Attempting
Authenticated
Failed
Login
Logout
```

التدفق المفاهيمي:

```text
AuthManager
     │
     ▼
Stateful Guard
     │
     ▼
Laravel Authentication
     │
     ├── Attempting
     ├── Authenticated
     ├── Login
     └── Logout
```

وفي حالة فشل المصادقة:

```text
Attempting
    │
    ▼
Failed
```

---

# 31. Events Responsibility Boundary

الحزمة لا تعيد إنشاء Authentication Events.

Laravel مسؤول عن:

```text
Event creation
Event dispatching
Authentication lifecycle
```

بينما `core-auth` يوفر abstraction فوق Authentication API.

---

# 32. Authentication Exception

المسار:

```text
src/Exceptions/AuthenticationException.php
```

الغرض:

```text
Authentication Exception Boundary
```

أي أن unexpected exceptions القادمة من Authentication infrastructure يتم تحويلها إلى exception مخصصة للحزمة.

---

# 33. Previous Exception Preservation

عند إنشاء:

```php
AuthenticationException
```

يتم الاحتفاظ بالـoriginal exception:

```php
new AuthenticationException(
    $message,
    $code,
    $exception
);
```

وبالتالي يمكن الوصول إلى:

```php
$exception->getPrevious();
```

وهذا يحافظ على معلومات debugging الأصلية.

---

# 34. Exception Responsibility

الحزمة تميز بين:

```text
Normal Authentication Failure
```

و:

```text
Unexpected Authentication Exception
```

النموذج:

```text
Invalid Credentials
       │
       ▼
     false
```

بينما:

```text
Unexpected Throwable
       │
       ▼
AuthenticationException
```

---

# 35. Integration Test Environment

يستخدم المشروع:

```text
Orchestra Testbench
```

لتوفير Laravel application environment مناسب لاختبار الحزمة.

يتم اختبار:

```text
Service Provider
Container
Authentication
Events
Remember Me
```

ضمن بيئة Laravel حقيقية نسبيًا.

---

# 36. Test Authentication Environment

تم إنشاء Test Authentication environment يحتوي على:

```text
TestUser
TestUserProvider
Laravel Authentication
Event Dispatcher
```

وتمت إضافة Remember Token state إلى TestUser:

```text
rememberToken
```

مع:

```php
getRememberToken()
setRememberToken()
```

---

# 37. Test User Provider

يطبق:

```php
Illuminate\Contracts\Auth\UserProvider
```

ويستخدم لاختبار Laravel Authentication بدون الاعتماد على Database حقيقية.

يوفر provider المستخدم الاختباري:

```text
user@example.com
```

ويتعامل مع credentials غير الصحيحة كـAuthentication failure.

كما يدعم:

```php
updateRememberToken()
```

حتى يستطيع Laravel تحديث Remember Token أثناء Remember Me flow.

---

# 38. Remember Me Integration Test

تمت إضافة Integration Test:

```text
test_remember_me_stores_remember_token
```

ويقوم بـ:

```php
$auth->login([
    'email' => 'user@example.com',
    'password' => 'password',
], true);
```

ثم يتحقق من:

```text
Authentication succeeds
Test user exists
Remember token exists
```

التدفق:

```text
AuthManager
     │
     ▼
login($credentials, true)
     │
     ▼
StatefulGuard
     │
     ▼
attempt($credentials, true)
     │
     ▼
Laravel Authentication
     │
     ▼
updateRememberToken()
     │
     ▼
TestUser rememberToken
```

هذا الاختبار يثبت تكامل Remember Me parameter مع Laravel Authentication.

ولا يثبت دورة استعادة المستخدم الكاملة من Remember Me cookie.

---

# 39. Service Container Binding

المسار:

```text
src/CoreAuthServiceProvider.php
```

يتم تسجيل:

```php
AuthManagerInterface::class
```

مع:

```php
AuthManager::class
```

بحيث يستطيع التطبيق الحصول على Authentication Manager من Laravel Container.

الاستخدام:

```php
$authManager = $app->make(
    AuthManagerInterface::class
);
```

---

# 40. Singleton Binding

يتم تسجيل Authentication Manager كـSingleton.

المبدأ:

```text
AuthManagerInterface
        │
        ▼
AuthManager
        │
        ▼
Singleton
```

وبالتالي فإن Container يعيد نفس instance أثناء دورة حياة الـapplication container.

---

# 41. Service Provider Responsibility

المسؤولية الحالية للـService Provider محدودة بـ:

```text
Container Registration
Authentication Manager Binding
```

ولا يحتوي على Authentication business logic.

---

# 42. Testing Strategy

تم تقسيم الاختبارات إلى:

```text
Unit Tests
Integration Tests
```

### Unit Tests

تركز على class behavior مع عزل dependencies.

### Integration Tests

تركز على التكامل مع Laravel Authentication environment.

---

# 43. AuthManager Tests

يتم اختبار:

```text
✓ Successful Login
✓ Failed Login
✓ Generalized Credentials
✓ Credentials Forwarding
✓ Remember Me Forwarding
✓ Default Remember = false
✓ Logout
✓ Check
✓ User
✓ Named Guard
✓ AuthenticationException
✓ Previous Exception
```

---

# 44. AuthGuard Tests

يتم اختبار:

```text
✓ GuardInterface implementation
✓ Successful Login
✓ Failed Login
✓ Credentials Forwarding
✓ Remember Me Forwarding
✓ Default Remember = false
✓ Logout
✓ Check
✓ User
✓ Null User
✓ AuthenticationException
✓ Previous Exception
```

---

# 45. Contract Tests

يتم التحقق من أن:

```text
AuthManager
```

يطبق:

```text
AuthManagerInterface
```

بشكل صحيح.

ويتم التحقق كذلك من:

```php
login(
    array $credentials,
    bool $remember = false
): bool;
```

---

# 46. Service Provider Tests

يتم اختبار:

```text
✓ Binding
✓ Container Resolution
✓ Singleton Behavior
```

---

# 47. Authentication Exception Tests

يتم اختبار:

```text
✓ Message
✓ Code
✓ Previous Exception
✓ Exception Inheritance
```

---

# 48. Integration Tests

تستخدم Integration Tests:

```text
Laravel Testbench
Test Application
Test User
Test User Provider
Laravel Authentication
Authentication Events
Remember Token support
```

---

# 49. Authentication Events Coverage

تغطي Integration Tests:

```text
Attempting
Failed
Authenticated
Login
Logout
```

وتختبر الحزمة داخل Laravel Authentication environment بدل Mocking كامل للـframework.

---

# 50. Remember Me Coverage

تتم تغطية Remember Me على مستويين.

## Unit Level

يتم التحقق من:

```text
remember = true
```

وأنه يصل إلى:

```php
attempt($credentials, true);
```

في:

```text
AuthManagerTest
AuthGuardTest
```

## Integration Level

يتم التحقق من أن:

```php
login($credentials, true)
```

يؤدي إلى وجود Remember Token على Test User من خلال Laravel Authentication flow.

---

# 51. PHPUnit Configuration

ملف:

```text
phpunit.xml
```

يحتوي على:

```text
Unit Test Suite
Integration Test Suite
```

Integration Tests:

```text
tests/Integration
```

ويتم تشغيل كامل الاختبارات باستخدام:

```bash
vendor/bin/phpunit
```

---

# 52. Current Test Structure

البنية:

```text
tests/
├── Unit/
│   ├── AuthenticationExceptionTest.php
│   ├── AuthGuardTest.php
│   ├── AuthManagerContractTest.php
│   ├── AuthManagerTest.php
│   ├── CoreAuthExceptionTest.php
│   └── CoreAuthServiceProviderTest.php
│
└── Integration/
    └── AuthenticationEventsTest.php
```

---

# 53. Mocking Strategy

تستخدم Unit Tests:

```text
Mockery
```

لعزل Laravel Authentication dependencies.

مثال:

```php
$guard
    ->shouldReceive('attempt')
    ->once()
    ->with(
        [
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ],
        false
    )
    ->andReturn(true);
```

وهذا يثبت أن:

```text
credentials
+
remember
```

يتم تمريرهما إلى Laravel Guard.

---

# 54. Remember Me Mocking

يمكن اختبار Remember Me بشكل معزول:

```php
$guard
    ->shouldReceive('attempt')
    ->once()
    ->with(
        [
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ],
        true
    )
    ->andReturn(true);
```

ثم:

```php
$this->assertTrue(
    $this->authManager->login(
        [
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ],
        true
    )
);
```

وهذا يثبت انتقال Remember Me option من package API إلى Laravel Guard.

---

# 55. Exception Mocking

يمكن محاكاة unexpected exception:

```php
$guard
    ->shouldReceive('attempt')
    ->once()
    ->with(
        $credentials,
        false
    )
    ->andThrow(
        new RuntimeException(
            'Unexpected authentication failure.'
        )
    );
```

ثم يتم التحقق من:

```text
AuthenticationException
```

ومن:

```php
$exception->getPrevious();
```

---

# 56. Laravel Testbench

يتم استخدام Laravel Testbench لاختبار:

```text
Service Provider Testing
Container Testing
Authentication Integration
Authentication Events
Remember Me Integration
```

وهذا يوفر environment قريبًا من Laravel application الحقيقي.

---

# 57. Code Documentation

تمت إضافة PHPDoc إلى المكونات الأساسية.

يتم توثيق:

```text
Parameters
Return Types
Exceptions
Authentication Behavior
```

ومن ذلك:

```php
@param array<string, mixed> $credentials
@param bool $remember
@return bool
@throws AuthenticationException
```

---

# 58. Important Design Decisions

## 58.1 Laravel Contracts

يعتمد التصميم على Laravel Contracts المناسبة:

```php
Illuminate\Contracts\Auth\Factory
Illuminate\Contracts\Auth\StatefulGuard
Illuminate\Contracts\Auth\Authenticatable
Illuminate\Contracts\Auth\UserProvider
```

---

## 58.2 Contract / Implementation Separation

يوجد فصل بين:

```text
Application Contract
       │
       ▼
AuthManagerInterface
       │
       ▼
AuthManager
```

وكذلك:

```text
GuardInterface
       │
       ▼
AuthGuard
```

---

## 58.3 Generalized Credentials

لا يتم فرض:

```text
email only
```

بل:

```php
array<string, mixed>
```

بحسب Authentication configuration الخاص بالتطبيق.

---

## 58.4 Guard Abstraction

لا يتم إرجاع Laravel Guard مباشرة إلى التطبيق.

بدلًا من ذلك:

```text
Laravel StatefulGuard
       │
       ▼
AuthGuard
       │
       ▼
GuardInterface
```

وهذا يحافظ على package abstraction.

---

## 58.5 Typed User

يتم استخدام:

```php
?Authenticatable
```

بدل:

```php
mixed
```

عندما يكون النوع معروفًا.

---

## 58.6 User Model Independence

لا تعتمد الحزمة على:

```text
App\Models\User
```

ولا على Model محدد.

---

## 58.7 Exception Boundary

يتم تحويل unexpected authentication exceptions إلى:

```text
AuthenticationException
```

مع الحفاظ على original exception.

---

## 58.8 Normal Failure vs Exceptional Failure

السلوك:

```text
Invalid Credentials
       │
       ▼
false
```

بينما:

```text
Unexpected Throwable
       │
       ▼
AuthenticationException
```

---

## 58.9 Laravel Events

تعتمد الحزمة على Laravel Authentication Events الموجودة بدل إعادة تنفيذ Event system.

---

## 58.10 Remember Me Delegation

توفر الحزمة:

```php
login($credentials, $remember);
```

لكنها لا تعيد تنفيذ Remember Me infrastructure.

المسؤولية:

```text
CoreAuth
    │
    │ API
    ▼
Laravel Authentication
    │
    ├── Remember Token
    ├── Remember Cookie
    └── User Restoration
```

---

# 59. Current Limitations

الميزات التالية لم يتم تنفيذها ضمن النطاق الحالي:

```text
Authentication Logging Abstraction
Rate Limiting
Authentication Throttling
Password Reset
Email Verification
Multi-Factor Authentication
Authentication Error Categories
```

أما Remember Me الأساسي فقد تم تنفيذه من خلال:

```php
login($credentials, $remember)
```

مع تفويض infrastructure إلى Laravel.

والـfull Remember Me restoration lifecycle ليس مغطى حاليًا باختبار Integration مستقل.

---

# 60. Completed Features

## Authentication Manager

```text
✓ AuthManagerInterface
✓ AuthManager implementation
✓ Login
✓ Logout
✓ Check
✓ User
```

## Generalized Authentication

```text
✓ Generalized credentials
✓ Email credentials
✓ Username credentials
✓ Custom identifiers
```

## Guard Support

```text
✓ GuardInterface
✓ AuthGuard
✓ Named Guards
✓ StatefulGuard integration
```

## Typed User

```text
✓ Authenticatable return type
✓ Nullable authenticated user
✓ User Model independence
```

## Exceptions

```text
✓ CoreAuthException
✓ AuthenticationException
✓ Exception boundary
✓ Previous Exception preservation
```

## Authentication Events

```text
✓ Attempting
✓ Authenticated
✓ Failed
✓ Login
✓ Logout
✓ Integration coverage
```

## Remember Me

```text
✓ Remember parameter
✓ Default remember = false
✓ AuthManager support
✓ AuthGuard support
✓ StatefulGuard support
✓ Remember option forwarding
✓ AuthManager unit coverage
✓ AuthGuard unit coverage
✓ Remember Token integration coverage
```

## Testing

```text
✓ PHPUnit
✓ Mockery
✓ Laravel Testbench
✓ Unit Tests
✓ Integration Tests
✓ Authentication Integration
```

## Documentation

```text
✓ API documentation
✓ Architecture documentation
✓ Testing documentation
✓ Exception documentation
✓ Authentication Events documentation
✓ Remember Me documentation
```

---

# 61. Current Status

الحالة الحالية لـAuthentication Manager:

```text
Authentication Manager          ✓
Generalized Login               ✓
Named Guards                    ✓
Typed User                      ✓
Authentication Exceptions       ✓
Authentication Events           ✓
Remember Me                     ✓
Remember Token Integration      ✓
Unit Tests                      ✓
Integration Tests               ✓
Laravel Testbench               ✓
PHPDoc                          ✓
Documentation                   ✓
Service Container               ✓
```

---

# 62. Current Test Result

آخر تشغيل كامل لـPHPUnit بعد إضافة Remember Me:

```bash
vendor/bin/phpunit
```

النتيجة:

```text
37 tests
55 assertions
OK
```

أي أن كامل الاختبارات الحالية تمر بنجاح.

---

# 63. Verification Workflow

قبل Commit أو Pull Request:

```bash
git status
git diff
git diff --check
vendor/bin/phpunit
```

ولمراجعة الملفات المعدلة:

```bash
git diff --stat
git diff
```

ولمراجعة التعديلات بعد staging:

```bash
git diff --cached
git diff --cached --check
```

بعد Commit:

```bash
git status
```

ويجب أن تكون الحالة:

```text
working tree clean
```

---

# 64. Git Development Workflow

يتم تطوير الميزات باستخدام Feature Branches مستقلة.

النمط:

```text
develop
   │
   ├── feature/generalize-auth-login
   │
   ├── feature/auth-manager-guard-support
   │
   ├── feature/auth-manager-user
   │
   ├── feature/authentication-exception-integration
   │
   ├── feature/authentication-events
   │
   └── feature/authentication-remember-me
```

بعد اكتمال Feature:

```text
Feature Branch
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

بعد الدمج والتأكد من استقرار `develop` يتم حذف Feature Branch المنتهي.

---

# 65. Latest Completed Feature

آخر Feature تم تنفيذها:

```text
feature/authentication-remember-me
```

Commit:

```text
73ee724
```

Commit message:

```text
feat: add remember me authentication support
```

التعديلات الرئيسية:

```text
AuthManagerInterface
GuardInterface
AuthManager
AuthGuard
AuthManagerTest
AuthGuardTest
AuthenticationEventsTest
```

وتشمل:

```text
Remember Me Login Support
Remember Me Unit Coverage
Remember Me Integration Coverage
Remember Token Test Support
```

تم تنفيذ دورة العمل:

```text
✓ Commit
✓ Push
✓ Pull Request
✓ Merge إلى develop
✓ حذف Feature Branch محليًا
✓ حذف Feature Branch من GitHub
✓ git fetch --prune
```

حالة `develop` الحالية:

```text
Up to date with origin/develop
Working tree clean
```

---

# 66. Development Methodology

سيتم تطوير الميزات القادمة وفق منهجية:

```text
Requirement
    │
    ▼
Design
    │
    ▼
Contract
    │
    ▼
Implementation
    │
    ▼
Unit Tests
    │
    ▼
Integration Tests
    │
    ▼
Documentation
    │
    ▼
Commit
    │
    ▼
Pull Request
    │
    ▼
develop
```

---

# 67. Development Principles

## 67.1 Contract First

تعريف Contract قبل Implementation عندما يكون ذلك مناسبًا.

## 67.2 Test First / Test Driven Where Practical

تحديد behavior واختباره قبل أو بالتزامن مع implementation.

## 67.3 Loose Coupling

تقليل الارتباط المباشر بين Application وLaravel implementations.

## 67.4 Dependency Injection

استخدام Constructor Injection وLaravel Container.

## 67.5 Type Safety

استخدام الأنواع الواضحة:

```text
bool
array<string, mixed>
?Authenticatable
GuardInterface
```

## 67.6 Small Features

تقسيم التطوير إلى Features صغيرة قابلة للاختبار والمراجعة والدمج.

## 67.7 Backward Compatibility

عدم كسر API الحالي إلا بقرار معماري واضح.

مثال:

```php
login($credentials);
```

ظل صالحًا بعد إضافة:

```php
login($credentials, $remember);
```

لأن:

```php
$remember = false
```

هو default behavior.

## 67.8 Responsibility Separation

كل class أو abstraction يجب أن يمتلك مسؤولية محددة.

## 67.9 Testability

تصميم المكونات بحيث يمكن اختبارها مع أقل اعتماد ممكن على environment حقيقي.

## 67.10 Documentation

يجب أن يعكس التوثيق behavior الفعلي للكود.

## 67.11 Stable Develop

يجب أن يبقى:

```text
develop
```

قابلًا للاختبار بعد دمج الميزات.

## 67.12 Exception Boundary

يجب الحفاظ على boundary واضحة بين:

```text
Laravel / Infrastructure
        │
        ▼
CoreAuth Exception Layer
        │
        ▼
Application
```

## 67.13 Integration Testing

يجب استخدام Integration Tests عندما يكون behavior متعلقًا بتكامل عدة مكونات Laravel وليس class واحدًا فقط.

## 67.14 Framework Delegation

عندما توفر Laravel Authentication behavior مناسبًا، يجب أن تعتمد الحزمة عليه بدل إعادة تنفيذ نفس infrastructure داخل package.

ينطبق ذلك حاليًا على:

```text
Remember Me
Authentication Events
Session Authentication
User Provider interaction
```

---

# 68. Authentication Flow Summary

## 68.1 Login Without Remember Me

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
AuthFactory
     │
     ▼
Laravel Stateful Guard
     │
     ▼
attempt($credentials, false)
     │
     ├── Attempting
     ├── Authenticated
     └── Login
     │
     ▼
true
```

---

## 68.2 Login With Remember Me

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
AuthFactory
     │
     ▼
Laravel Stateful Guard
     │
     ▼
attempt($credentials, true)
     │
     ├── Attempting
     ├── Authenticated
     └── Login
     │
     ▼
Remember Token handling
     │
     ▼
true
```

---

## 68.3 Failed Login

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Stateful Guard
     │
     ▼
attempt($credentials, false)
     │
     ├── Attempting
     └── Failed
     │
     ▼
false
```

---

## 68.4 Unexpected Exception

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Laravel Authentication
     │
     ▼
Throwable
     │
     ▼
AuthenticationException
     │
     ▼
Application
```

---

# 69. Final Architecture

البنية النهائية الحالية:

```text
                         Application
                              │
                              ▼
                    AuthManagerInterface
                              │
                              ▼
                         AuthManager
                              │
               ┌──────────────┴──────────────┐
               │                             │
               ▼                             ▼
        Default Guard                  Named Guard
               │                             │
               ▼                             ▼
     Laravel StatefulGuard              AuthGuard
               │                             │
               └──────────────┬──────────────┘
                              │
                              ▼
                   Laravel Authentication
                              │
             ┌────────────────┼────────────────┐
             │                │                │
             ▼                ▼                ▼
          Session          Events        Remember Me
                                             │
                                             ▼
                                      Remember Token
```

Exception boundary:

```text
Laravel Authentication
          │
          ▼
       Throwable
          │
          ▼
AuthenticationException
          │
          ▼
      Application
```

---

# 70. Current Public API

الاستخدام الأساسي:

```php
$authManager->login($credentials);
```

أو مع Remember Me:

```php
$authManager->login(
    $credentials,
    true
);
```

Logout:

```php
$authManager->logout();
```

Check:

```php
$authManager->check();
```

User:

```php
$user = $authManager->user();
```

Named Guard:

```php
$authManager->guard('api');
```

---

# 71. Future Extension Strategy

عند إضافة Feature جديدة، يجب الحفاظ على نفس المبادئ:

```text
Feature
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
Integration
   │
   ▼
Documentation
```

ويجب تجنب إضافة abstraction لمجرد التوسع النظري.

يتم إنشاء abstraction عندما يكون هناك:

```text
Real Requirement
Clear Responsibility
Stable Boundary
Testable Behavior
```

---

# 72. Summary

تم بناء Authentication Manager كطبقة abstraction قابلة للتوسع فوق Laravel Authentication.

الدعم الحالي يشمل:

```text
✓ Login
✓ Generalized Credentials
✓ Logout
✓ Check
✓ Typed User
✓ Named Guards
✓ Stateful Guard Adapter
✓ AuthenticationException
✓ Previous Exception Preservation
✓ Authentication Events
✓ Remember Me
✓ Remember Token Integration Coverage
✓ Unit Testing
✓ Integration Testing
✓ Laravel Testbench
✓ Service Container Binding
```

أصبح بإمكان التطبيق استخدام:

```php
$authManager->login($credentials);
```

أو:

```php
$authManager->login(
    $credentials,
    true
);
```

لتفعيل Remember Me من خلال Laravel Authentication.

وتبقى مسؤوليات Laravel Authentication infrastructure مثل:

```text
Authentication Verification
User Provider
Session
Remember Token
Remember Cookie
User Restoration
Authentication Events
```

خارج مسؤولية `core-auth` المباشرة.

الحالة الحالية للاختبارات:

```text
37 tests
55 assertions
OK
```

وبذلك يمثل `core-auth` حاليًا أساسًا منظمًا وقابلًا للتوسع لطبقة Authentication، مع الحفاظ على الفصل بين:

```text
Package Abstraction
        │
        ▼
Laravel Authentication
        │
        ▼
Application
```
