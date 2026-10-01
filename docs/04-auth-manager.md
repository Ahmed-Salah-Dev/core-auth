# Core Auth — Authentication Manager

## 1. Phase

**Authentication Manager, Generalized Login, Guard Support, Typed Authenticated User Support, Authentication Exception Integration, Authentication Events Integration, Remember Me Support, and Authentication Contract/Public API Refinement**

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

التصميم الحالي لا يحاول إعادة بناء Laravel Authentication، وإنما يوفر طبقة package مستقلة يمكن تطويرها مستقبلًا دون ربط التطبيق مباشرة بالتفاصيل الداخلية لـLaravel.

تم كذلك إجراء **Authentication Contract/Public API Refinement** بهدف توحيد طريقة تعامل `AuthManager` مع الـguards، وتقليل تكرار المسؤوليات، وجعل `AuthGuard` هو abstraction boundary الموحدة بين package وLaravel Authentication.

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
     ├── defaultGuard()
     │        │
     │        ▼
     │     AuthGuard
     │
     └── guard($name)
              │
              ▼
           AuthGuard
              │
              ▼
     Laravel StatefulGuard
```

بالتالي فإن العمليات التالية:

```text
Login
Logout
Check
User
```

تمر جميعها عبر `AuthGuard`.

أما الـNamed Guards فيتم الوصول إليها من خلال:

```php
$authManager->guard($name);
```

ويتم إرجاع:

```php
GuardInterface
```

بدل Laravel Guard مباشرة.

أما Authentication Exception boundary:

```text
Laravel StatefulGuard
          │
          ▼
       Throwable
          │
          ▼
      AuthGuard
          │
          ▼
AuthenticationException
          │
          ▼
      Application
```

ويكون `AuthGuard` هو المسؤول عن تحويل unexpected authentication exceptions إلى `AuthenticationException`.

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
* توحيد تعامل `AuthManager` مع الـdefault والـnamed guards.
* إبقاء exception handling في abstraction المسؤولة عنه.
* تقليل تكرار Laravel Guard handling داخل `AuthManager`.

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

الاختبارات المتعلقة بـAuthentication:

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

ميزات Password Reset وEmail Verification موجودة في ملفاتها ووثائقها المستقلة، ولا يتم تكرار تفاصيلها هنا لأن هذا الملف مخصص لـAuthentication Manager.

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

الـinterface يمثل API التي يتعامل معها التطبيق دون الحاجة إلى معرفة implementation الداخلي.

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

القيمة الافتراضية لـ`$remember` هي:

```php
false
```

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

وبالتالي فإن الاستخدام السابق:

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

يكون التدفق:

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
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
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

من خلال نفس abstraction path.

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
✓ StatefulGuard support
✓ Unit coverage
✓ Integration coverage
```

أما الاستعادة الكاملة للمستخدم من Remember Me cookie فلم تتم إضافتها كاختبار Integration مستقل حتى الآن.

---

# 14. Login Behavior

التدفق الأساسي الحالي:

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
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
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

أما unexpected exceptions فتدخل إلى Authentication Exception Boundary الموجودة داخل `AuthGuard`.

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
Laravel StatefulGuard
       │
       ▼
false
```

بينما exception غير متوقع:

```text
Laravel StatefulGuard
       │
       ▼
Throwable
       │
       ▼
AuthGuard
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

* توفير واجهة Authentication Manager.
* تنفيذ Login باستخدام الـdefault guard.
* تمرير credentials.
* تمرير Remember Me option.
* تنفيذ Logout باستخدام الـdefault guard.
* تنفيذ Check باستخدام الـdefault guard.
* استرجاع User باستخدام الـdefault guard.
* إنشاء `AuthGuard` للـNamed Guards.
* توحيد الوصول إلى الـdefault guard من خلال `defaultGuard()`.
* تفويض Authentication behavior إلى `AuthGuard`.

ولا يعتبر `AuthManager` مسؤولًا عن تحويل unexpected authentication exceptions.

هذه المسؤولية تقع في:

```text
AuthGuard
```

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

كما أن `AuthManager` لا يحتاج إلى معرفة تفاصيل `StatefulGuard` عند تنفيذ العمليات اليومية؛ إذ يتم التعامل معها من خلال `AuthGuard`.

---

# 18. AuthManager Login Implementation

التنفيذ الحالي:

```php
return $this->defaultGuard()->login(
    $credentials,
    $remember
);
```

وبالتالي تكون المسؤوليات:

```text
AuthManager
     │
     │ credentials + remember
     ▼
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
     │
     ▼
attempt()
```

ولا يحتوي `AuthManager` على `try/catch` خاص بـAuthentication exceptions.

---

# 19. AuthManager Logout

التوقيع:

```php
public function logout(): void;
```

التنفيذ الحالي يعتمد على الـdefault `AuthGuard`:

```text
AuthManager
     │
     ▼
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
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

التنفيذ:

```text
AuthManager
     │
     ▼
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
check()
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

التنفيذ:

```text
AuthManager
     │
     ▼
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
user()
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
Laravel StatefulGuard
```

وبذلك يستخدم كل من الـdefault guard والـnamed guards نفس package-level guard abstraction.

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

ولا يتم كشف Laravel `StatefulGuard` مباشرة إلى التطبيق من خلال هذا contract.

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
Laravel StatefulGuard
```

ويستخدم `AuthGuard` لكل من:

```text
Default Guard
Named Guards
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

إذا تم دعم Token/API Authentication مستقبلًا، يجب تصميم abstraction مناسبة لذلك السيناريو بدل إجبار token-based authentication على نفس stateful contract.

---

# 26. AuthGuard Login

التوقيع:

```php
public function login(
    array $credentials,
    bool $remember = false
): bool;
```

ويتم تمرير القيم إلى Laravel:

```php
return $this->guard->attempt(
    $credentials,
    $remember
);
```

مع وجود exception boundary داخل `AuthGuard` لحماية package abstraction من unexpected throwables.

---

# 27. AuthGuard Exception Boundary

`AuthGuard` هو المكان المركزي لمعالجة unexpected authentication exceptions.

التدفق:

```text
Laravel StatefulGuard
       │
       ▼
Throwable
       │
       ▼
AuthGuard
       │
       ▼
AuthenticationException
       │
       ▼
Application
```

وعند إنشاء:

```php
AuthenticationException
```

يتم الحفاظ على الـoriginal exception كـprevious exception.

وبذلك لا يحتاج `AuthManager` إلى تكرار نفس `try/catch` logic.

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

وهذا يوفر type safety أفضل للمستهلكين.

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

التدفق المفاهيمي الحالي:

```text
AuthManager
     │
     ▼
AuthGuard
     │
     ▼
StatefulGuard
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

وهذا يعني أن الحزمة لا تحاول استبدال Laravel Event system أو إعادة تنفيذ lifecycle الخاص بالمصادقة.

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

ويتم تطبيق هذه boundary داخل:

```text
AuthGuard
```

وليس داخل `AuthManager`.

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

وهذا يحافظ على معلومات debugging الأصلية ويمنع فقدان سبب الخطأ الأساسي.

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
AuthGuard
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
defaultGuard()
     │
     ▼
AuthGuard
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
Password Reset Manager Binding
Email Verification Manager Binding
```

ولا يحتوي Service Provider على Authentication business logic.

تفاصيل Password Reset وEmail Verification موثقة في الملفات الخاصة بكل feature.

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

ويتم استخدام كل مستوى عندما يكون مناسبًا لطبيعة behavior المراد اختباره.

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
✓ AuthenticationException propagation
```

اختبارات `AuthManager` تتحقق من أن الـmanager يمرر behavior بشكل صحيح إلى الـguard abstraction، بينما يتم اختبار exception wrapping نفسه داخل `AuthGuard`.

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

وتعتبر هذه الاختبارات المستوى الأساسي لاختبار behavior الخاص بالـguard adapter وexception boundary.

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

ويتم التحقق من التوقيع:

```php
login(
    array $credentials,
    bool $remember = false
): bool;
```

بالإضافة إلى بقية public methods الموجودة في contract.

---

# 46. Service Provider Tests

يتم اختبار:

```text
✓ Binding
✓ Container Resolution
✓ Singleton Behavior
```

لـAuthentication Manager.

كما يتم اختبار bindings الخاصة بالـpackage services الأخرى في اختبارات Service Provider المناسبة.

---

# 47. Authentication Exception Tests

يتم اختبار:

```text
✓ Message
✓ Code
✓ Previous Exception
✓ Exception Inheritance
```

لضمان أن `AuthenticationException` تحافظ على exception information المطلوبة.

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

وهذا يسمح باختبار behavior الذي يعتمد على تفاعل أكثر من component داخل Laravel.

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

يتم استخدام typed mocks عند التعامل مع Laravel authentication contracts.

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

يتم تمريرهما إلى Laravel Guard abstraction.

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

وهذا يثبت انتقال Remember Me option عبر:

```text
AuthManager
     │
     ▼
AuthGuard
     │
     ▼
StatefulGuard
```

---

# 55. Exception Mocking

يمكن محاكاة unexpected exception على مستوى الـguard dependency:

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

المسؤول عن wrapping هو:

```text
AuthGuard
```

بينما يقوم `AuthManager` بتفويض العملية ولا يعيد إنشاء exception boundary.

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

يجب أن يعكس PHPDoc مكان responsibility الفعلي.

لذلك فإن `AuthGuard` هو المكان الأساسي الذي يوثق Authentication exception behavior، بينما `AuthManager` يوثق delegation behavior.

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

ويتم استخدام نفس abstraction لكل من:

```text
Default Guard
Named Guards
```

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

وتقع هذه المسؤولية في:

```text
AuthGuard
```

وليس `AuthManager`.

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
AuthGuard
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

## 58.11 Centralized Guard Handling

يتم توحيد default guard operations داخل:

```php
private function defaultGuard(): GuardInterface
```

ويتم كذلك إرجاع `AuthGuard` عند استخدام Named Guard.

وبالتالي لا يحتوي `AuthManager` على مسارين مختلفين للتعامل مع Laravel guards.

النتيجة:

```text
Default Guard ──┐
                ├──> AuthGuard ──> StatefulGuard
Named Guard ────┘
```

وهذا يقلل duplication ويحافظ على consistency في architecture.

---

# 59. Current Limitations

الميزات أو التحسينات التالية لم يتم تنفيذها ضمن النطاق الحالي:

```text
Authentication Logging Abstraction
Rate Limiting
Authentication Throttling
Multi-Factor Authentication
Authentication Error Categories
API / Token Authentication
Authorization
Session Management
Social Authentication
Advanced Account Security
```

أما الميزات التالية فقد تم تنفيذها في package:

```text
Password Reset
Email Verification
```

وتوجد لكل منهما documentation مستقلة.

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
✓ Default Guard Adapter
✓ StatefulGuard integration
✓ Centralized Guard Handling
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
✓ Centralized exception handling in AuthGuard
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

## Account Recovery

```text
✓ Password Reset
✓ Email Verification
```

تفاصيل هذه الميزات موجودة في وثائقها المستقلة.

## Contract / Public API Refinement

```text
✓ AuthManager guard handling refinement
✓ Default Guard centralized through AuthGuard
✓ Named Guard uses AuthGuard
✓ Exception boundary centralized in AuthGuard
✓ AuthManager responsibilities reduced to orchestration/delegation
✓ Public API consistency reviewed
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
✓ Password Reset documentation
✓ Email Verification documentation
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
Guard Abstraction               ✓
Centralized Guard Handling      ✓
Unit Tests                      ✓
Integration Tests               ✓
Laravel Testbench               ✓
PHPDoc                          ✓
Documentation                   ✓
Service Container               ✓
Contract Refinement             ✓
Public API Refinement            ✓
```

وعلى مستوى package توجد كذلك:

```text
Password Reset                   ✓
Email Verification               ✓
```

---

# 62. Current Test Result

آخر تشغيل كامل لـPHPUnit بعد Contract/Public API Refinement:

```bash
vendor/bin/phpunit
```

النتيجة:

```text
58 tests
110 assertions
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

التاريخ الرئيسي للميزات المتعلقة بـAuthentication يتضمن:

```text
develop
   │
   ├── feature/generalize-auth-login
   ├── feature/auth-manager-guard-support
   ├── feature/auth-manager-user
   ├── feature/authentication-exception-integration
   ├── feature/authentication-events
   ├── feature/authentication-remember-me
   ├── feature/password-reset
   ├── feature/core-auth-architecture-refinement
   ├── feature/email-verification
   └── feature/auth-contract-refinement
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

بعد الدمج والتأكد من استقرار `develop` يتم حذف Feature Branch المنتهي محليًا ومن GitHub.

---

# 65. Latest Completed Feature

آخر Feature تم تنفيذها ودمجها:

```text
feature/auth-contract-refinement
```

Commit:

```text
66d666f
```

Commit message:

```text
refactor: centralize guard handling in auth manager
```

التعديلات الرئيسية:

```text
AuthManager
AuthManagerInterface
AuthGuard
AuthManagerTest
```

وتشمل:

```text
Centralized Default Guard Handling
AuthGuard Usage for Default Operations
Named Guard Consistency
Centralized Authentication Exception Boundary
AuthManager Responsibility Refinement
Public API Consistency
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

تم دمج الـFeature في `develop` من خلال:

```text
d711753
Merge pull request #12 from Ahmed-Salah-Dev/feature/auth-contract-refinement
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
Architecture Review
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
Consistency Review
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

ولا يتم الانتقال مباشرة من Requirement إلى Implementation دون مراجعة architecture عندما تكون الميزة ذات تأثير على public API أو boundaries.

---

# 67. Development Principles

## 67.1 Contract First

تعريف Contract قبل Implementation عندما يكون ذلك مناسبًا.

---

## 67.2 Test First / Test Driven Where Practical

تحديد behavior واختباره قبل أو بالتزامن مع implementation.

---

## 67.3 Architecture Review

قبل تنفيذ feature تؤثر على architecture يجب مراجعة:

```text
Responsibilities
Dependencies
Contracts
Abstractions
Extension Points
Backward Compatibility
```

---

## 67.4 Loose Coupling

تقليل الارتباط المباشر بين Application وLaravel implementations.

---

## 67.5 Dependency Injection

استخدام Constructor Injection وLaravel Container.

---

## 67.6 Type Safety

استخدام الأنواع الواضحة:

```text
bool
array<string, mixed>
?Authenticatable
GuardInterface
```

---

## 67.7 Small Features

تقسيم التطوير إلى Features صغيرة قابلة للاختبار والمراجعة والدمج.

---

## 67.8 Backward Compatibility

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

---

## 67.9 Responsibility Separation

كل class أو abstraction يجب أن يمتلك مسؤولية محددة.

في Authentication architecture الحالية:

```text
AuthManager
    │
    │ orchestration
    ▼
AuthGuard
    │
    │ authentication adapter
    ▼
Laravel StatefulGuard
```

---

## 67.10 Testability

تصميم المكونات بحيث يمكن اختبارها مع أقل اعتماد ممكن على environment حقيقي.

---

## 67.11 Documentation

يجب أن يعكس التوثيق behavior الفعلي للكود والarchitecture الحالية.

---

## 67.12 Stable Develop

يجب أن يبقى:

```text
develop
```

قابلًا للاختبار بعد دمج الميزات.

---

## 67.13 Exception Boundary

يجب الحفاظ على boundary واضحة بين:

```text
Laravel / Infrastructure
        │
        ▼
AuthGuard
        │
        ▼
CoreAuth Exception Layer
        │
        ▼
Application
```

وتحديد مكان exception handling بوضوح يمنع تكرار responsibility في أكثر من class.

---

## 67.14 Integration Testing

يجب استخدام Integration Tests عندما يكون behavior متعلقًا بتكامل عدة مكونات Laravel وليس class واحدًا فقط.

---

## 67.15 Framework Delegation

عندما توفر Laravel Authentication behavior مناسبًا، يجب أن تعتمد الحزمة عليه بدل إعادة تنفيذ نفس infrastructure داخل package.

ينطبق ذلك حاليًا على:

```text
Remember Me
Authentication Events
Session Authentication
User Provider interaction
```

---

## 67.16 Consistency Review

بعد implementation والاختبارات يجب مراجعة:

```text
Contracts
Implementations
Dependencies
Exception Boundaries
Tests
Documentation
```

للتأكد من أن جميعها تعكس architecture نفسها.

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
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
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
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
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
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
StatefulGuard
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
defaultGuard()
     │
     ▼
AuthGuard
     │
     ▼
Laravel StatefulGuard
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

المهم هنا أن:

```text
AuthManager
```

لا يقوم بعملية wrapping.

الـexception boundary موجودة في:

```text
AuthGuard
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
                    ┌─────────┴─────────┐
                    │                   │
                    ▼                   ▼
             defaultGuard()         guard($name)
                    │                   │
                    ▼                   ▼
                AuthGuard            AuthGuard
                    │                   │
                    └─────────┬─────────┘
                              │
                              ▼
                    Laravel StatefulGuard
                              │
                    ┌─────────┼─────────┐
                    │         │         │
                    ▼         ▼         ▼
                 Session    Events   Remember Me
                                      │
                                      ▼
                               Remember Token
```

Exception boundary:

```text
Laravel StatefulGuard
          │
          ▼
       Throwable
          │
          ▼
      AuthGuard
          │
          ▼
AuthenticationException
          │
          ▼
      Application
```

المبدأ الأساسي:

```text
Application
      │
      ▼
CoreAuth Contracts
      │
      ▼
CoreAuth Implementations
      │
      ▼
Laravel Authentication Contracts
      │
      ▼
Laravel Authentication Infrastructure
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

ويظل التطبيق يتعامل مع package contracts بدل Laravel guard implementations مباشرة.

---

# 71. Future Extension Strategy

عند إضافة Feature جديدة، يجب الحفاظ على نفس المبادئ:

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
Architecture Review
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
Consistency Review
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

وعند إضافة authentication mechanism جديد مثل:

```text
Token Authentication
Social Authentication
MFA
```

يجب أولًا تحديد ما إذا كان يحتاج abstraction مستقلة بدل توسيع abstraction موجودة بشكل يؤدي إلى coupling أو responsibilities غير متجانسة.

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
✓ Centralized Guard Handling
✓ Contract/Public API Refinement
```

وعلى مستوى package توجد كذلك:

```text
✓ Password Reset
✓ Email Verification
```

ويستخدم Authentication Manager حاليًا architecture موحدة:

```text
AuthManager
     │
     ├── defaultGuard()
     │        │
     │        ▼
     │     AuthGuard
     │
     └── guard($name)
              │
              ▼
           AuthGuard
              │
              ▼
     Laravel StatefulGuard
```

وبذلك أصبح `AuthGuard` هو الـadapter والـexception boundary الأساسية بين package وLaravel Authentication.

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
58 tests
110 assertions
OK
```

وآخر Feature مكتملة هي:

```text
feature/auth-contract-refinement
```

مع commit:

```text
66d666f
refactor: centralize guard handling in auth manager
```

وقد تم دمجها في `develop`، وأصبحت الشجرة الحالية مستقرة ونظيفة.

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
