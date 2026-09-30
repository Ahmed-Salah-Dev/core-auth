# Core Auth — Password Reset

## 1. Phase

**Password Reset / Account Recovery**

---

# 2. Overview

يوفر `core-auth` طبقة abstraction فوق Laravel Password Reset infrastructure للتعامل مع استعادة كلمة المرور.

الهدف هو توفير API مستقلة وواضحة للتطبيق من أجل:

* إرسال Password Reset Link.
* تنفيذ Password Reset باستخدام Token صالح.
* تشفير كلمة المرور الجديدة.
* التعامل مع Laravel Password Broker.
* الحفاظ على فصل المسؤوليات بين Authentication وAccount Recovery.
* توفير Unit Tests.
* دعم الاختبار من خلال Laravel Container.
* الحفاظ على قابلية التوسع المستقبلية.

لا تحاول الحزمة إعادة تنفيذ Password Reset infrastructure الخاصة بـLaravel.

بدلًا من ذلك:

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
     └── Reset Link Notification
```

---

# 3. Responsibility Separation

تم فصل Password Reset عن Authentication Manager.

البنية:

```text
Authentication
     │
     ▼
AuthManager
```

بينما Account Recovery:

```text
Account Recovery
     │
     ▼
PasswordResetManager
```

ولا يتم إضافة Password Reset methods إلى:

```text
AuthManagerInterface
AuthManager
```

لأن Authentication وPassword Reset يمثلان مسؤوليتين مختلفتين.

---

# 4. Design Goals

تم تصميم Password Reset لتحقيق الأهداف التالية:

* توفير API بسيطة لاستعادة كلمة المرور.
* الفصل بين Authentication وAccount Recovery.
* الاعتماد على Laravel Password Broker.
* عدم إعادة تنفيذ Token infrastructure.
* عدم إعادة تنفيذ Token expiration.
* عدم إعادة تنفيذ Password Reset notifications.
* استخدام Dependency Injection.
* دعم اختبار المكونات باستخدام Mockery.
* استخدام Laravel Hasher لتشفير كلمة المرور.
* عدم تخزين Password خام.
* الحفاظ على abstraction مستقلة عن Laravel concrete implementations قدر الإمكان.
* توفير Service Container binding.
* الحفاظ على قابلية التوسع مستقبلًا.

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

البنية الحالية المتعلقة بـPassword Reset:

```text
src/
├── Contracts/
│   ├── AuthManagerInterface.php
│   ├── GuardInterface.php
│   └── PasswordResetManagerInterface.php
│
├── Exceptions/
│   ├── CoreAuthException.php
│   └── AuthenticationException.php
│
├── Services/
│   ├── AuthManager.php
│   ├── AuthGuard.php
│   └── PasswordResetManager.php
│
└── CoreAuthServiceProvider.php
```

اختبارات Password Reset:

```text
tests/
└── Unit/
    └── PasswordResetManagerTest.php
```

ويتم كذلك اختبار تسجيل الخدمة من خلال:

```text
tests/Unit/CoreAuthServiceProviderTest.php
```

---

# 7. PasswordResetManagerInterface

المسار:

```text
src/Contracts/PasswordResetManagerInterface.php
```

يمثل الـpublic contract الخاص بـPassword Reset.

التوقيع الحالي:

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

# 8. Send Reset Link API

يتم إرسال رابط استعادة كلمة المرور باستخدام:

```php
$passwordResetManager->sendResetLink(
    $credentials
);
```

مثال:

```php
[
    'email' => 'user@example.com',
]
```

والتوقيع:

```php
public function sendResetLink(
    array $credentials
): bool;
```

---

# 9. Credentials

لا تفرض الحزمة identifier محددًا لإرسال Reset Link.

يمكن أن تعتمد credentials على إعدادات Laravel Password Broker والتطبيق.

مثال:

```php
[
    'email' => 'user@example.com',
]
```

ويمكن للتطبيق استخدام حقول أخرى بحسب الـUser Provider والـPassword Broker configuration.

المسؤولية عن تحديد المستخدم وإدارة Password Reset infrastructure تبقى لدى Laravel.

---

# 10. Send Reset Link Behavior

يستدعي `PasswordResetManager`:

```php
$this->broker->sendResetLink(
    $credentials
);
```

ثم يتم تحويل Laravel broker status إلى:

```text
true
```

أو:

```text
false
```

النجاح:

```php
PasswordBroker::RESET_LINK_SENT
```

يعاد على شكل:

```php
true
```

وأي broker status آخر يعاد على شكل:

```php
false
```

---

# 11. Reset API

بعد حصول المستخدم على Reset Token صالح، يمكن تنفيذ Password Reset باستخدام:

```php
$passwordResetManager->reset(
    $credentials,
    $token,
    $password
);
```

مثال:

```php
$passwordResetManager->reset(
    [
        'email' => 'user@example.com',
    ],
    $token,
    'new-password'
);
```

---

# 12. Reset Parameters

تحتاج عملية reset إلى:

```text
$credentials
$token
$password
```

حيث:

```text
$credentials
    تحديد المستخدم.

$token
    Password Reset Token.

$password
    كلمة المرور الجديدة.
```

---

# 13. Password Hashing

لا يتم حفظ كلمة المرور الجديدة بشكل خام.

يستخدم `PasswordResetManager`:

```php
Illuminate\Contracts\Hashing\Hasher
```

لتشفير كلمة المرور:

```php
$this->hasher->make(
    $password
);
```

ثم يتم تحديث المستخدم:

```php
$user->forceFill([
    'password' => $hashedPassword,
])->save();
```

وبالتالي:

```text
Plain Password
      │
      ▼
Laravel Hasher
      │
      ▼
Hashed Password
      │
      ▼
User Model
```

---

# 14. Password Reset Implementation

المسار:

```text
src/Services/PasswordResetManager.php
```

يعتمد implementation على:

```php
PasswordBroker
Hasher
```

ويتم حقنهما من خلال Constructor Injection.

المفهوم الحالي:

```php
public function __construct(
    private readonly PasswordBroker $broker,
    private readonly Hasher $hasher
) {
}
```

---

# 15. Password Broker Dependency

يعتمد `PasswordResetManager` على:

```php
Illuminate\Contracts\Auth\PasswordBroker
```

بدل استخدام:

```php
Password facade
```

السبب هو الحفاظ على:

```text
Dependency Injection
Testability
Loose Coupling
```

البنية:

```text
PasswordResetManager
        │
        ▼
PasswordBroker Contract
        │
        ▼
Laravel Password Reset Infrastructure
```

---

# 16. Why PasswordBroker?

يوفر Laravel Password Broker infrastructure المطلوبة لإدارة Password Reset.

وتبقى مسؤولية Laravel في:

```text
Token Generation
Token Storage
Token Validation
Token Expiration
User Retrieval
Reset Link Handling
Password Reset Status
```

ولا يعيد `core-auth` تنفيذ هذه الآليات.

---

# 17. Send Reset Link Flow

التدفق الحالي:

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
     ▼
sendResetLink($credentials)
     │
     ├── Success
     │      │
     │      ▼
     │     true
     │
     └── Failure
            │
            ▼
           false
```

---

# 18. Reset Flow

التدفق الحالي:

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
     ▼
reset(
    $credentials,
    $callback,
    $token
)
     │
     ▼
Valid Token
     │
     ▼
Reset Callback
     │
     ▼
Hasher
     │
     ▼
Hashed Password
     │
     ▼
User->save()
     │
     ▼
true
```

---

# 19. Reset Callback

يستخدم Laravel Password Broker callback لتنفيذ تحديث كلمة المرور.

المفهوم الحالي:

```php
function ($user) use ($password): void {
    $user->forceFill([
        'password' => $this->hasher->make($password),
    ])->save();
}
```

هذا يسمح لـLaravel بالتحكم في:

```text
Token Validation
User Resolution
Token Expiration
```

بينما يتولى `PasswordResetManager` عملية تحديث كلمة المرور.

---

# 20. Password Reset Status

يعيد Laravel Password Broker status string.

مثل:

```php
PasswordBroker::PASSWORD_RESET
```

لكن `core-auth` لا يعرض هذه status strings مباشرة ضمن public API.

بدلًا من ذلك يتم تحويلها إلى:

```text
Success → true
Failure → false
```

وبذلك تصبح API الخاصة بالحزمة مستقلة عن status strings الخاصة بـLaravel.

---

# 21. Reset Success

عند نجاح العملية:

```php
PasswordBroker::PASSWORD_RESET
```

يعيد `PasswordResetManager`:

```php
true
```

---

# 22. Reset Failure

عند فشل العملية، مثل Token غير صالح:

```text
Laravel Password Broker
        │
        ▼
Failure Status
        │
        ▼
PasswordResetManager
        │
        ▼
false
```

لا يتم اعتبار فشل Password Reset الطبيعي Exception.

---

# 23. Failure vs Exception

يجب التفريق بين:

```text
Normal Password Reset Failure
```

و:

```text
Unexpected Exception
```

مثال على failure طبيعي:

```text
Invalid Token
Expired Token
Unknown User
```

ويتم التعبير عنه من خلال:

```php
false
```

أما unexpected infrastructure exception فيمكن أن ينتشر من الـbroker.

---

# 24. Exception Strategy

لا يوجد حاليًا:

```text
PasswordResetException
```

وذلك قرار مقصود في هذه المرحلة.

الـpublic contract لا يعلن exception محددة لعمليات Password Reset.

كما أن `PasswordResetManager` حاليًا لا يحول كل Throwable إلى exception مخصصة كما يحدث في Authentication Manager.

هذا يبقي abstraction بسيطة إلى أن يظهر requirement حقيقي لتصنيف Password Reset exceptions.

---

# 25. Authentication vs Password Reset

المسؤوليات:

```text
Authentication
     │
     ▼
AuthManager
     │
     ├── Login
     ├── Logout
     ├── Check
     ├── User
     └── Guards
```

بينما:

```text
Account Recovery
     │
     ▼
PasswordResetManager
     │
     ├── Send Reset Link
     └── Reset Password
```

ولا ينبغي دمج المسؤوليتين في manager واحد.

---

# 26. Service Container Binding

المسار:

```text
src/CoreAuthServiceProvider.php
```

يتم تسجيل:

```php
PasswordResetManagerInterface::class
```

مع:

```php
PasswordResetManager::class
```

باستخدام:

```php
$this->app->singleton(
    PasswordResetManagerInterface::class,
    PasswordResetManager::class
);
```

---

# 27. Container Resolution

يمكن للتطبيق الحصول على Password Reset Manager من Laravel Container:

```php
$passwordResetManager = $app->make(
    PasswordResetManagerInterface::class
);
```

وبذلك لا يحتاج التطبيق إلى إنشاء:

```php
new PasswordResetManager(...)
```

مباشرة.

---

# 28. Singleton Binding

تم تسجيل `PasswordResetManager` كـSingleton.

المبدأ:

```text
PasswordResetManagerInterface
            │
            ▼
PasswordResetManager
            │
            ▼
         Singleton
```

ويضمن ذلك أن Container يعيد نفس instance أثناء دورة حياة الـapplication container.

---

# 29. Testing Strategy

تم اختبار Password Reset باستخدام:

```text
Unit Tests
Service Provider Tests
```

Unit Tests تستخدم Mockery لعزل:

```text
PasswordBroker
Hasher
```

بينما Service Provider Tests تستخدم Laravel Testbench للتحقق من Container integration.

---

# 30. PasswordResetManager Unit Tests

المسار:

```text
tests/Unit/PasswordResetManagerTest.php
```

تغطي الاختبارات الحالية:

```text
✓ Successful Send Reset Link
✓ Failed Send Reset Link
✓ Successful Password Reset
✓ Failed Password Reset
✓ Broker Exception on Send Reset Link
✓ Broker Exception on Password Reset
✓ PasswordResetManager Contract
```

---

# 31. Successful Send Reset Link Test

يتم التحقق من أن:

```php
sendResetLink($credentials)
```

يستدعي:

```php
$broker->sendResetLink(
    $credentials
);
```

وعند إرجاع:

```php
PasswordBroker::RESET_LINK_SENT
```

تكون النتيجة:

```php
true
```

---

# 32. Failed Send Reset Link Test

يتم اختبار broker status غير الناجح.

مثال:

```text
passwords.user
```

ويتم التأكد من أن النتيجة:

```php
false
```

---

# 33. Successful Reset Test

يتم التحقق من:

```text
Credentials forwarding
Token forwarding
Reset callback
Password hashing
User update
Successful status
```

ويتم التأكد من أن:

```php
$hasher->make($password)
```

يتم استدعاؤه.

ثم:

```php
$user->forceFill(...)
```

ثم:

```php
$user->save()
```

---

# 34. Failed Reset Test

يتم اختبار Password Broker status غير الناجح.

عند عدم إرجاع:

```php
PasswordBroker::PASSWORD_RESET
```

تكون النتيجة:

```php
false
```

---

# 35. Exception Tests

يتم اختبار أن exceptions القادمة من:

```text
PasswordBroker
```

لا يتم ابتلاعها.

مثال:

```php
RuntimeException
```

يجب أن تصل إلى caller.

وهذا يختلف عن Authentication Manager الذي يمتلك حاليًا `AuthenticationException` boundary مخصصة.

---

# 36. Contract Test

يتم التحقق من أن:

```text
PasswordResetManager
```

يطبق:

```text
PasswordResetManagerInterface
```

بشكل صحيح.

الاختبار:

```php
$this->assertInstanceOf(
    PasswordResetManagerInterface::class,
    $this->passwordResetManager
);
```

---

# 37. Service Provider Tests

يتم اختبار:

```text
✓ PasswordResetManager binding
✓ Container resolution
✓ Singleton behavior
```

ويتم تنفيذ هذه الاختبارات من خلال:

```text
tests/Unit/CoreAuthServiceProviderTest.php
```

---

# 38. Test Environment

يستخدم `CoreAuthServiceProviderTest`:

```text
Orchestra Testbench
```

لإنشاء Laravel application test environment.

ويتم توفير:

```text
Laravel Application
Password Broker
Hasher
Service Provider
Container
```

ويحتاج Password Broker إلى `app.key` صالح أثناء بناء Testbench environment.

يتم تعريف مفتاح اختبار داخل:

```php
defineEnvironment()
```

بدل الاعتماد على إعداد بيئة خارجية.

---

# 39. Mocking Strategy

تستخدم Unit Tests:

```text
Mockery
```

لعزل Laravel dependencies.

مثال:

```php
$broker = Mockery::mock(
    PasswordBroker::class
);

$hasher = Mockery::mock(
    Hasher::class
);
```

ثم يتم تمريرها إلى:

```php
new PasswordResetManager(
    $broker,
    $hasher
);
```

وهذا يسمح باختبار manager behavior بدون إنشاء Password Reset infrastructure كاملة.

---

# 40. Password Hashing Test

يتم التأكد من عدم حفظ كلمة المرور الخام.

مثلًا:

```php
$hasher
    ->shouldReceive('make')
    ->once()
    ->with($password)
    ->andReturn($hashedPassword);
```

ثم:

```php
$user
    ->shouldReceive('forceFill')
    ->once()
    ->with([
        'password' => $hashedPassword,
    ]);
```

وهذا يثبت أن Manager يعتمد على Hasher قبل تحديث المستخدم.

---

# 41. Laravel Infrastructure Delegation

يعتمد `core-auth` على Laravel في:

```text
Password Broker
Token generation
Token storage
Token validation
Token expiration
User resolution
Reset status
```

بينما يوفر:

```text
PasswordResetManagerInterface
PasswordResetManager
Password hashing boundary
Application-facing API
```

---

# 42. Why Not Reimplement Password Reset?

إعادة تنفيذ Password Reset داخل package ستؤدي إلى تكرار Laravel infrastructure.

مثل:

```text
Token Generation
Token Repository
Token Expiration
Token Validation
Notification Handling
User Resolution
```

وهذا يزيد:

```text
Complexity
Maintenance Cost
Framework Coupling
Security Risk
```

لذلك يتم اعتماد Laravel Password Broker بدل إعادة بناء هذه المكونات.

---

# 43. Why Hasher Injection?

يعتمد `PasswordResetManager` على:

```php
Illuminate\Contracts\Hashing\Hasher
```

بدل استدعاء hashing implementation مباشرة.

الفوائد:

```text
Dependency Injection
Testability
Loose Coupling
Laravel Configuration Compatibility
```

ويمكن اختبار hashing behavior بسهولة باستخدام Mockery.

---

# 44. Current Public API

إرسال Reset Link:

```php
$passwordResetManager->sendResetLink(
    [
        'email' => 'user@example.com',
    ]
);
```

Reset Password:

```php
$passwordResetManager->reset(
    [
        'email' => 'user@example.com',
    ],
    $token,
    'new-password'
);
```

النتيجة:

```text
true
```

أو:

```text
false
```

---

# 45. Current Architecture

البنية الحالية:

```text
                         Application
                              │
                              ▼
                PasswordResetManagerInterface
                              │
                              ▼
                     PasswordResetManager
                       │             │
                       │             │
                       ▼             ▼
                PasswordBroker     Hasher
                       │             │
                       ▼             ▼
             Laravel Password     Laravel
                Infrastructure     Hashing
                       │
          ┌────────────┼────────────┐
          │            │            │
          ▼            ▼            ▼
        Tokens      Validation    User Reset
```

---

# 46. Password Reset Flow

## 46.1 Send Reset Link

```text
Application
     │
     ▼
PasswordResetManager
     │
     ▼
PasswordBroker
     │
     ▼
sendResetLink()
     │
     ├── Reset Link Sent
     │       │
     │       ▼
     │      true
     │
     └── Failure
             │
             ▼
            false
```

---

## 46.2 Reset Password

```text
Application
     │
     ▼
PasswordResetManager
     │
     ▼
PasswordBroker
     │
     ▼
Token Validation
     │
     ▼
Reset Callback
     │
     ▼
Hasher
     │
     ▼
User Update
     │
     ▼
true
```

---

# 47. Responsibility Boundary

```text
CoreAuth
    │
    │ Application API
    ▼
PasswordResetManager
    │
    ├── API abstraction
    └── Password hashing coordination
    │
    ▼
Laravel PasswordBroker
    │
    ├── Token generation
    ├── Token storage
    ├── Token validation
    ├── Expiration
    ├── User resolution
    └── Reset infrastructure
```

---

# 48. Important Design Decisions

## 48.1 Separate Manager

Password Reset لا يتم وضعه داخل:

```text
AuthManager
```

لأن:

```text
Authentication
```

و:

```text
Account Recovery
```

مسؤوليتان مختلفتان.

---

## 48.2 Contract / Implementation Separation

يوجد فصل بين:

```text
PasswordResetManagerInterface
            │
            ▼
PasswordResetManager
```

وهذا يسمح بالتغيير المستقبلي دون ربط التطبيق بالimplementation مباشرة.

---

## 48.3 Laravel Password Broker

تعتمد الحزمة على:

```php
PasswordBroker
```

بدل إعادة تنفيذ Password Reset infrastructure.

---

## 48.4 Dependency Injection

يتم حقن:

```text
PasswordBroker
Hasher
```

من خلال Constructor.

---

## 48.5 Password Hashing

لا يتم حفظ password خام.

يجب أن تمر كلمة المرور الجديدة عبر:

```php
Hasher::make()
```

قبل حفظها.

---

## 48.6 Boolean Public API

يتم إخفاء Laravel broker status strings خلف:

```text
true / false
```

للحفاظ على public API بسيطة ومستقلة.

---

## 48.7 No Specialized Exception Yet

لم تتم إضافة:

```text
PasswordResetException
```

لعدم وجود requirement حالي يستدعي abstraction إضافية.

---

## 48.8 Framework Delegation

عندما توفر Laravel Password Reset behavior المطلوب، تعتمد الحزمة عليه بدل إعادة تنفيذه.

---

# 49. Current Limitations

الميزات التالية ليست ضمن Password Reset الحالي:

```text
PasswordResetException
Custom Password Reset Result object
Password Reset event abstraction
Password Reset logging abstraction
Rate Limiting abstraction
Password Reset throttling abstraction
Email Verification
Multi-Factor Authentication
```

كما أن public API الحالية لا تعرض Laravel broker status strings بشكل مباشر.

---

# 50. Current Test Coverage

الاختبارات الخاصة بـPassword Reset:

```text
PasswordResetManagerTest
    ✓ Send Reset Link success
    ✓ Send Reset Link failure
    ✓ Reset success
    ✓ Reset failure
    ✓ Send Reset Link exception
    ✓ Reset exception
    ✓ Contract implementation
```

Service Provider:

```text
CoreAuthServiceProviderTest
    ✓ PasswordResetManager binding
    ✓ PasswordResetManager singleton
```

إجمالي اختبارات المشروع بعد إضافة Password Reset:

```text
46 tests
72 assertions
OK
```

آخر تشغيل:

```bash
vendor/bin/phpunit
```

والنتيجة:

```text
46 tests
72 assertions
OK
```

---

# 51. Verification Workflow

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

وبعد staging:

```bash
git diff --cached
git diff --cached --check
```

بعد Commit:

```bash
git status
```

ويجب أن تكون:

```text
working tree clean
```

---

# 52. Development Workflow

يتم تطوير Password Reset باستخدام:

```text
feature/password-reset
```

النمط:

```text
develop
    │
    ▼
feature/password-reset
    │
    ├── Contract
    ├── Implementation
    ├── Unit Tests
    ├── Service Provider Binding
    └── Documentation
```

ثم:

```text
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

وبعد الدمج والتأكد من استقرار `develop` يتم حذف Feature Branch.

---

# 53. Development Methodology

تم تنفيذ Password Reset وفق المنهجية:

```text
Requirement
    │
    ▼
Architecture Decision
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
Container Integration
    │
    ▼
Full Test Suite
    │
    ▼
Documentation
```

---

# 54. Development Principles

## 54.1 Responsibility Separation

Password Reset مسؤولية مستقلة عن Authentication.

## 54.2 Contract First

يتم تعريف public contract قبل implementation عندما يكون ذلك مناسبًا.

## 54.3 Dependency Injection

يتم استخدام Constructor Injection مع Laravel Contracts.

## 54.4 Testability

يجب أن تكون dependencies قابلة للعزل والـmocking.

## 54.5 Type Safety

يتم استخدام:

```text
bool
array<string, mixed>
string
```

مع Laravel Contracts المناسبة.

## 54.6 Framework Delegation

لا يتم إعادة تنفيذ Laravel Password Reset infrastructure دون requirement واضح.

## 54.7 Security by Delegation

يتم الاعتماد على Laravel في Token infrastructure، ويتم استخدام Hasher لتشفير كلمة المرور قبل التخزين.

## 54.8 Small Features

يتم تطوير الميزات في Feature Branches مستقلة.

## 54.9 Documentation

يجب أن يعكس التوثيق behavior الفعلي للكود.

---

# 55. Future Extension Strategy

يمكن توسيع Password Reset مستقبلًا عند وجود requirements حقيقية.

أمثلة:

```text
PasswordResetException
PasswordResetResult
Named Password Brokers
Password Reset Events
Reset Link Customization
Reset Notifications Abstraction
Rate Limiting
Password Reset Auditing
```

لكن لا تتم إضافة هذه abstractions لمجرد التوسع النظري.

يجب أن يكون هناك:

```text
Real Requirement
Clear Responsibility
Stable Boundary
Testable Behavior
```

قبل إضافة abstraction جديدة.

---

# 56. Final Architecture

البنية العامة لـ`core-auth` أصبحت تتضمن مسؤوليتين منفصلتين:

```text
                         Application
                              │
              ┌───────────────┴────────────────┐
              │                                │
              ▼                                ▼
      AuthManagerInterface          PasswordResetManagerInterface
              │                                │
              ▼                                ▼
         AuthManager                   PasswordResetManager
              │                                │
              ▼                                ├── PasswordBroker
     Laravel Authentication                  │
              │                                └── Hasher
      ┌───────┼────────┐
      │       │        │
      ▼       ▼        ▼
   Session  Events  Remember Me
```

---

# 57. Summary

تمت إضافة Password Reset إلى `core-auth` كمسؤولية مستقلة عن Authentication Manager.

الدعم الحالي يشمل:

```text
✓ PasswordResetManagerInterface
✓ PasswordResetManager
✓ Send Reset Link
✓ Password Reset
✓ PasswordBroker integration
✓ Hasher integration
✓ Password hashing
✓ Contract testing
✓ Unit testing
✓ Service Container binding
✓ Singleton binding
✓ Laravel Testbench integration
✓ Failure handling
✓ Broker exception propagation
```

وتستخدم الحزمة:

```php
$passwordResetManager->sendResetLink(
    $credentials
);
```

و:

```php
$passwordResetManager->reset(
    $credentials,
    $token,
    $password
);
```

بينما تبقى مسؤوليات Laravel Password Reset infrastructure مثل:

```text
Token Generation
Token Storage
Token Validation
Token Expiration
User Resolution
Reset Link Infrastructure
```

خارج مسؤولية `core-auth` المباشرة.

الحالة الحالية للاختبارات:

```text
46 tests
72 assertions
OK
```

وبذلك أصبح Account Recovery منفصلًا معماريًا عن Authentication:

```text
Authentication
      │
      ▼
AuthManager

Account Recovery
      │
      ▼
PasswordResetManager
```

مع الحفاظ على:

```text
Contract Separation
Dependency Injection
Testability
Type Safety
Laravel Delegation
Responsibility Separation
Future Extensibility
```
