# Authentication Manager

## Phase

**Authentication Manager, Generalized Login, Guard Support, Typed Authenticated User Support, Authentication Exception Integration, and Authentication Events Integration**

---

# 1. Overview

تم بناء طبقة Authentication موحدة داخل حزمة `core-auth` بهدف توفير API واضحة وقابلة للتوسع للتعامل مع Laravel Authentication، مع تقليل الارتباط المباشر بتفاصيل Laravel Authentication Infrastructure.

تعتمد هذه الطبقة على مفهوم:

```text
Contract → Implementation → Laravel Authentication
```

بحيث يتعامل التطبيق مع Contracts الخاصة بالحزمة، بينما تتولى الحزمة عملية الربط مع Laravel Authentication.

التصميم الحالي يوفر:

```text
AuthManagerInterface
        │
        ▼
    AuthManager
        │
        ├── Default Guard
        │
        └── Named Guard
                │
                ▼
            AuthGuard
                │
                ▼
          GuardInterface
```

كما تمت إضافة طبقة موحدة لمعالجة الأخطاء غير المتوقعة:

```text
Laravel Authentication Error
          │
          ▼
AuthenticationException
          │
          ▼
Application
```

بالإضافة إلى ذلك، تم اختبار تكامل Laravel Authentication Events مع الحزمة من خلال Integration Tests.

---

# 2. Goals

الهدف الرئيسي من هذه المرحلة هو إنشاء أساس قوي وقابل للتوسع لطبقة Authentication.

الأهداف الأساسية:

* توفير Authentication Manager موحد.
* فصل Contract عن Implementation.
* استخدام Laravel Authentication Contracts.
* دعم Dependency Injection.
* دعم Laravel Service Container.
* دعم Generalized Login Credentials.
* عدم فرض `email` كـ identifier.
* دعم Named Guards.
* توفير Guard abstraction خاص بالحزمة.
* توفير Typed Authenticated User.
* دعم `Authenticatable|null`.
* توفير Authentication Exception Boundary.
* الحفاظ على Previous Exception.
* اختبار Authentication behavior باستخدام Unit Tests.
* اختبار Laravel integration باستخدام Testbench.
* التحقق من Laravel Authentication Events.
* الحفاظ على الفرق بين Authentication Failure وUnexpected Exception.
* توفير بنية قابلة للتوسع للميزات المستقبلية.

---

# 3. Technologies Used

تم بناء واختبار هذه المرحلة باستخدام:

```text
PHP 8.2.12
Laravel Framework
PHPUnit 11.5.56
Laravel Testbench
Mockery
Composer
Git
```

ويجب اعتبار `composer.json` المصدر النهائي للـ dependencies والإصدارات المستخدمة في المشروع.

---

# 4. Implemented Components

المكونات الرئيسية الحالية:

```text
src/
├── Contracts/
│   ├── AuthManagerInterface.php
│   └── GuardInterface.php
│
├── Exceptions/
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
│   ├── AuthGuardTest.php
│   ├── AuthManagerContractTest.php
│   ├── AuthManagerTest.php
│   └── CoreAuthServiceProviderTest.php
│
└── Integration/
    └── AuthenticationEventsTest.php
```

التوثيق:

```text
docs/
└── 04-auth-manager.md
```

---

# 5. Architecture

المعمارية الحالية:

```text
                         Application
                              │
                              ▼
                    AuthManagerInterface
                              │
                              ▼
                         AuthManager
                       /            \
                      /              \
                     ▼                ▼
             Default Guard       Named Guard
                     │                │
                     ▼                ▼
             Laravel Guard        AuthGuard
                                      │
                                      ▼
                                GuardInterface
```

أما Authentication Exception Boundary:

```text
Application
     │
     ▼
AuthManager / AuthGuard
     │
     ▼
Laravel Guard
     │
     │ unexpected Throwable
     ▼
AuthenticationException
     │
     ▼
Application
```

أما Authentication Events:

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Laravel Guard
     │
     ▼
Laravel Authentication
     │
     ├── Attempting
     ├── Failed
     ├── Authenticated
     ├── Login
     └── Logout
```

الحزمة لا تقوم بإنشاء Events مخصصة في هذه المرحلة، وإنما تعتمد على Laravel Authentication Events وتتحقق من تكاملها الصحيح مع Authentication flow.

---

# 6. AuthManagerInterface

المسار:

```text
src/Contracts/AuthManagerInterface.php
```

يمثل `AuthManagerInterface` العقد الرئيسي الذي يتعامل معه التطبيق.

العمليات الحالية:

```php
public function login(array $credentials): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;

public function guard(string $name): GuardInterface;
```

ويعتمد على:

```php
use Illuminate\Contracts\Auth\Authenticatable;
```

لتمثيل المستخدم authenticated.

---

# 7. AuthManagerInterface Responsibilities

## 7.1 login()

التوقيع:

```php
public function login(array $credentials): bool;
```

تستقبل العملية credentials عامة:

```php
[
    'email' => 'user@example.com',
    'password' => 'password',
]
```

ولا تفرض الحزمة identifier محددًا.

يمكن أن تكون credentials:

```text
email
username
phone
employee_id
national_id
custom identifier
```

بحسب Authentication Guard وUser Provider المستخدمين من التطبيق.

القيم المرجعة:

```text
true
```

عند نجاح Authentication.

أو:

```text
false
```

عند رفض credentials بشكل طبيعي.

أما عند حدوث خطأ غير متوقع:

```text
AuthenticationException
```

---

## 7.2 logout()

التوقيع:

```php
public function logout(): void;
```

تقوم بتسجيل خروج المستخدم الحالي من خلال الـ default guard.

---

## 7.3 check()

التوقيع:

```php
public function check(): bool;
```

تتحقق من وجود مستخدم authenticated.

النتائج:

```text
true  → authenticated
false → unauthenticated
```

---

## 7.4 user()

التوقيع:

```php
public function user(): ?Authenticatable;
```

ترجع المستخدم authenticated الحالي.

الحالات:

```text
Authenticated
     │
     ▼
Authenticatable
```

أو:

```text
Unauthenticated
     │
     ▼
null
```

---

## 7.5 guard()

التوقيع:

```php
public function guard(string $name): GuardInterface;
```

تسمح بالحصول على Guard باسم محدد:

```php
$guard = $authManager->guard('api');
```

القيمة المرجعة:

```text
GuardInterface
```

وليس Laravel Guard مباشرة.

---

# 8. AuthManager

المسار:

```text
src/Services/AuthManager.php
```

يقوم `AuthManager` بتنفيذ:

```php
AuthManagerInterface
```

وهو نقطة الدخول الرئيسية إلى Authentication abstraction الخاصة بالحزمة.

يعتمد على:

```php
Illuminate\Contracts\Auth\Factory
```

ويتم حقنه باستخدام Constructor Dependency Injection.

---

# 9. Dependency Injection

التصميم:

```php
public function __construct(
    private readonly AuthFactory $auth
) {
}
```

بدل إنشاء Authentication Manager داخليًا، يتم تمرير dependency من Laravel Container.

الفوائد:

```text
Loose Coupling
Testability
Maintainability
Extensibility
```

كما يتم حقن Laravel Guard داخل `AuthGuard`.

---

# 10. AuthManager Login

التدفق الأساسي:

```text
AuthManager
     │
     ▼
AuthFactory::guard()
     │
     ▼
Default Laravel Guard
     │
     ▼
attempt($credentials)
```

في حالة نجاح Authentication:

```text
attempt()
    │
    ▼
true
```

في حالة فشل credentials:

```text
attempt()
    │
    ▼
false
```

في حالة حدوث exception:

```text
attempt()
    │
    ▼
Throwable
    │
    ▼
AuthenticationException
```

ويتم الاحتفاظ بالاستثناء الأصلي.

---

# 11. Generalized Login

كان التصميم السابق يفرض شكلًا محددًا لعملية Login.

مثال:

```php
$authManager->login(
    'user@example.com',
    'password'
);
```

هذا التصميم يربط Authentication Manager بشكل identifier محدد.

تم تغيير ذلك إلى:

```php
$authManager->login([
    'email' => 'user@example.com',
    'password' => 'password',
]);
```

وأصبحت مسؤولية تحديد identifier لدى التطبيق وLaravel Authentication.

---

# 12. Authentication Identifier Flexibility

لا تقوم الحزمة بتحديد أن تسجيل الدخول يجب أن يتم باستخدام email.

## Email

```php
$authManager->login([
    'email' => 'user@example.com',
    'password' => 'password',
]);
```

## Username

```php
$authManager->login([
    'username' => 'ahmed',
    'password' => 'password',
]);
```

## Phone

```php
$authManager->login([
    'phone' => '777123456',
    'password' => 'password',
]);
```

## Employee ID

```php
$authManager->login([
    'employee_id' => 'EMP-1001',
    'password' => 'password',
]);
```

## Custom Identifier

```php
$authManager->login([
    'national_id' => '123456789',
    'password' => 'password',
]);
```

المبدأ الأساسي:

```text
AuthManager does not decide the identifier.
```

بل يقوم بتمرير credentials إلى Laravel Guard.

---

# 13. Responsibility Boundary

يجب الحفاظ على الفصل التالي:

```text
Application
     │
     │ defines credentials
     ▼
AuthManager
     │
     │ passes credentials
     ▼
Laravel Guard
     │
     ▼
User Provider
     │
     ▼
Authentication System
```

`AuthManager` لا يقرر:

* ما هو identifier الصحيح.
* كيف يتم البحث عن المستخدم.
* كيف يتم تخزين المستخدم.
* كيف يتم التحقق من password.
* ما هو User Model.
* ما هو User Provider المستخدم.

هذه مسؤوليات Laravel Authentication والتطبيق.

---

# 14. Guard Support

تمت إضافة abstraction مستقل للتعامل مع Guards.

المكونات:

```text
src/Contracts/GuardInterface.php
src/Services/AuthGuard.php
```

الهدف هو منع التطبيق من الارتباط مباشرة بـ Laravel Guard implementation.

---

# 15. GuardInterface

المسار:

```text
src/Contracts/GuardInterface.php
```

العمليات:

```php
public function login(array $credentials): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;
```

---

# 16. AuthGuard

المسار:

```text
src/Services/AuthGuard.php
```

`AuthGuard` هو Adapter بين:

```text
GuardInterface
```

و:

```text
Illuminate\Contracts\Auth\Guard
```

التصميم:

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
Laravel Guard
```

يعتمد على Constructor Injection:

```php
public function __construct(
    private readonly LaravelGuard $guard
) {
}
```

---

# 17. Named Guard

يمكن الحصول على Guard باسم محدد:

```php
$guard = $authManager->guard('api');
```

التدفق:

```text
AuthManager
     │
     ▼
AuthFactory::guard('api')
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

وبالتالي لا يتم تسريب Laravel Guard إلى application layer.

---

# 18. AuthGuard Login

يقوم `AuthGuard` بتمرير credentials إلى Laravel Guard:

```text
AuthGuard
     │
     ▼
Laravel Guard
     │
     ▼
attempt($credentials)
```

السلوك:

```text
true
```

عند نجاح Authentication.

```text
false
```

عند فشل credentials.

```text
AuthenticationException
```

عند حدوث unexpected exception.

---

# 19. Typed Authenticated User

تعتمد الحزمة على Laravel Contract:

```php
Illuminate\Contracts\Auth\Authenticatable
```

بدل:

```php
mixed
```

وبالتالي:

```php
public function user(): ?Authenticatable;
```

هذا يوفر:

```text
Type Safety
Static Analysis
IDE Support
Maintainability
Extensibility
```

---

# 20. User Model Independence

لا تفرض الحزمة:

```text
App\Models\User
```

ولا أي Model محدد.

يمكن للتطبيق استخدام أي User implementation يطبق:

```php
Authenticatable
```

وهذا يحافظ على استقلال الحزمة عن تطبيق معين.

---

# 21. User State

يمكن استخدام:

```php
$authManager->check();
```

ثم:

```php
$user = $authManager->user();
```

عند وجود مستخدم:

```text
check() === true
        │
        ▼
user() → Authenticatable
```

وعند عدم وجود مستخدم:

```text
check() === false
        │
        ▼
user() → null
```

---

# 22. AuthenticationException

المسار:

```text
src/Exceptions/AuthenticationException.php
```

تمت إضافة `AuthenticationException` كـ exception موحد على مستوى الحزمة.

الهدف:

```text
Laravel / Infrastructure
          │
          ▼
AuthenticationException
          │
          ▼
Application
```

بدل تمرير كل أنواع exceptions الناتجة من dependencies الداخلية مباشرة إلى application.

---

# 23. Exception Normalization

عند حدوث exception غير متوقع أثناء Authentication:

```text
RuntimeException
        │
        ▼
AuthManager / AuthGuard
        │
        ▼
AuthenticationException
```

هذا يوفر Exception Boundary واضحًا.

---

# 24. Previous Exception Preservation

عند إنشاء `AuthenticationException` يتم الاحتفاظ بالاستثناء الأصلي:

```php
throw new AuthenticationException(
    $exception->getMessage(),
    (int) $exception->getCode(),
    $exception
);
```

وبذلك:

```php
$exception->getPrevious()
```

يعيد الاستثناء الأصلي.

الفائدة:

```text
Unified API
+
Original debugging information
```

---

# 25. Normal Authentication Failure vs Exception

يجب التفريق بين حالتين.

## Normal Authentication Failure

```text
attempt()
     │
     ▼
false
```

النتيجة:

```php
false
```

ولا يتم إطلاق `AuthenticationException`.

## Unexpected Authentication Error

```text
attempt()
     │
     ▼
Throwable
     │
     ▼
AuthenticationException
```

النتيجة:

```php
AuthenticationException
```

وهذا الفصل جزء أساسي من API design.

---

# 26. Laravel Authentication Events

تمت إضافة Integration Tests للتحقق من تكامل Authentication Manager مع Laravel Authentication Events.

لا تقوم الحزمة في هذه المرحلة بإنشاء Events مخصصة.

بدلًا من ذلك، يتم الاعتماد على Events التي يوفرها Laravel Authentication system.

الأحداث التي تم اختبارها:

```text
Attempting
Failed
Authenticated
Login
Logout
```

---

# 27. Authentication Events Flow

عند تنفيذ Login:

```text
Application
     │
     ▼
AuthManager::login()
     │
     ▼
Laravel Guard
     │
     ▼
attempt()
     │
     ├── Attempting
     │
     ├── Failed
     │
     ├── Authenticated
     │
     └── Login
```

أما عند تنفيذ Logout:

```text
Application
     │
     ▼
AuthManager::logout()
     │
     ▼
Laravel Guard
     │
     ▼
logout()
     │
     ▼
Logout Event
```

ملاحظة مهمة:

ترتيب الأحداث الداخلي الدقيق مسؤولية Laravel Authentication implementation، بينما اختبارات الحزمة تتحقق من أن الأحداث المتوقعة يتم إطلاقها وأن بياناتها الأساسية صحيحة.

---

# 28. Authentication Events Integration Test

المسار:

```text
tests/Integration/AuthenticationEventsTest.php
```

تم إنشاء Integration Test مستقل لهذا الغرض.

يستخدم:

```php
Orchestra\Testbench\TestCase
```

لتوفير Laravel application environment مناسب للاختبار.

---

# 29. Test Authentication Environment

يتم تعريف Guard للاختبارات:

```php
$app['config']->set('auth.defaults.guard', 'web');
```

ثم:

```php
$app['config']->set('auth.guards.web', [
    'driver' => 'session',
    'provider' => 'users',
]);
```

ويتم تعريف Test User Provider:

```php
$app['config']->set('auth.providers.users', [
    'driver' => 'test',
]);
```

ثم تسجيل provider:

```php
$app['auth']->provider('test', function () {
    return new TestUserProvider();
});
```

هذا يسمح باختبار Authentication flow دون الاعتماد على database حقيقية.

---

# 30. TestUser

يحتوي Integration Test على `TestUser` بسيط يطبق:

```php
Illuminate\Contracts\Auth\Authenticatable
```

الغرض منه هو توفير authenticated user حقيقي بالنسبة إلى Laravel Authentication أثناء Integration Testing.

وهذا يتوافق مع التصميم الحالي الذي يعتمد على:

```php
Authenticatable
```

بدل فرض User Model معين.

---

# 31. TestUserProvider

يحتوي Integration Test على `TestUserProvider` يطبق:

```php
Illuminate\Contracts\Auth\UserProvider
```

ويوفر behavior مبسطًا للاختبار.

يتم قبول:

```text
user@example.com
```

مع password:

```text
password
```

بينما يتم رفض password خاطئة.

هذا يسمح باختبار:

```text
Successful Authentication
Failed Authentication
```

داخل Laravel Authentication environment حقيقي نسبيًا.

---

# 32. Login Event Test

يتم اختبار أن Login Event يتم إطلاقه عند نجاح authentication.

يتم تسجيل listener:

```php
$this->app['events']->listen(
    Login::class,
    ...
);
```

ثم تنفيذ:

```php
$auth->login([
    'email' => 'user@example.com',
    'password' => 'password',
]);
```

ويتم التحقق من:

```text
Login Event exists
Guard = web
User = TestUser
```

وهذا لا يختبر مجرد إطلاق event فقط، بل يتحقق من البيانات الأساسية الموجودة داخله.

---

# 33. Failed Event Test

يتم اختبار فشل Authentication:

```php
$credentials = [
    'email' => 'user@example.com',
    'password' => 'wrong-password',
];
```

ثم:

```php
$result = $auth->login($credentials);
```

النتيجة:

```text
false
```

ويتم التحقق من:

```text
Failed Event exists
Guard = web
Credentials = original credentials
```

وهذا يؤكد أن Authentication failure الطبيعي لا يتحول إلى exception.

---

# 34. Logout Event Test

يتم تنفيذ Login أولًا:

```php
$auth->login([
    'email' => 'user@example.com',
    'password' => 'password',
]);
```

ثم:

```php
$auth->logout();
```

ويتم التحقق من:

```text
Logout Event exists
Guard = web
User = TestUser
```

---

# 35. Attempting Event Test

يتم تسجيل listener على:

```php
Attempting::class
```

ثم تنفيذ Login.

يتم التأكد من إطلاق:

```text
Attempting
```

أثناء محاولة Authentication.

---

# 36. Authenticated Event Test

عند نجاح Authentication يتم التحقق من:

```text
Authenticated Event exists
Guard = web
User = TestUser
```

وهذا يثبت أن Laravel Authentication lifecycle يعمل بشكل صحيح من خلال `AuthManager`.

---

# 37. Events Responsibility Boundary

يجب التفريق بين:

```text
Laravel Authentication Events
```

و:

```text
Custom Package Events
```

المرحلة الحالية تختبر تكامل الحزمة مع Laravel Events فقط.

لم يتم بعد تصميم:

```text
CoreAuth-specific Events
```

مثل:

```text
UserLoggedIn
UserLoggedOut
AuthenticationFailed
```

وأي تصميم لهذه الأحداث يجب أن يتم في Feature مستقلة بعد تحديد الحاجة المعمارية إليها.

---

# 38. Service Container Binding

المسار:

```text
src/CoreAuthServiceProvider.php
```

يتم تسجيل:

```php
$this->app->singleton(
    AuthManagerInterface::class,
    AuthManager::class
);
```

وبالتالي يستطيع Laravel Container توفير:

```php
$app->make(AuthManagerInterface::class);
```

---

# 39. Singleton Binding

تم استخدام:

```php
singleton()
```

بدل:

```php
bind()
```

والهدف هو الحصول على نفس `AuthManager` instance خلال دورة حياة Laravel Application Container.

تم اختبار هذا السلوك داخل:

```text
CoreAuthServiceProviderTest
```

---

# 40. Service Provider Responsibility

المسؤولية الحالية للـ Service Provider هي تسجيل الخدمات الأساسية للحزمة.

التدفق:

```text
AuthManagerInterface
        │
        ▼
AuthManager
```

ولا يقوم Service Provider حاليًا بوضع Authentication business logic.

---

# 41. Testing Strategy

تم تقسيم الاختبارات إلى نوعين:

```text
Unit Tests
Integration Tests
```

---

# 42. Unit Tests

Unit Tests تعزل dependencies باستخدام Mockery.

الهدف:

```text
اختبار behavior الخاص بالمكون
بدون الاعتماد على Laravel Authentication حقيقي
```

---

# 43. AuthManager Tests

يتم اختبار:

* تطبيق `AuthManagerInterface`.
* نجاح Login.
* فشل Login.
* تمرير credentials.
* Generalized Credentials.
* Email credentials.
* Username credentials.
* Authentication state.
* Authenticated user.
* Unauthenticated user.
* Logout.
* Named Guard.
* Guard abstraction.
* AuthenticationException.
* Previous Exception.
* Normal false behavior.

---

# 44. AuthGuard Tests

يتم اختبار:

* تطبيق `GuardInterface`.
* نجاح Login.
* فشل Login.
* Logout.
* Check.
* User.
* Null user.
* AuthenticationException.
* Previous Exception.

---

# 45. Contract Tests

يتم التحقق من أن:

```text
AuthManagerInterface
```

يوفر:

```text
login()
logout()
check()
user()
guard()
```

وأن:

```text
AuthManager
```

يطبق الـ Contract بشكل صحيح.

---

# 46. Service Provider Tests

يتم اختبار:

```text
Service Provider
Container Binding
Interface → Implementation
Singleton behavior
```

---

# 47. Authentication Exception Tests

يتم اختبار:

```text
Unexpected Throwable
        │
        ▼
AuthenticationException
```

مع التحقق من:

1. إطلاق `AuthenticationException`.
2. الحفاظ على message.
3. الحفاظ على code عند الحاجة.
4. الحفاظ على previous exception.
5. عدم تحويل authentication failure الطبيعي `false` إلى exception.

---

# 48. Integration Tests

تمت إضافة:

```text
tests/Integration/AuthenticationEventsTest.php
```

لاختبار التكامل الحقيقي نسبيًا مع Laravel Authentication.

تم استخدام:

```text
Laravel Testbench
Test User
Test User Provider
Laravel Event Dispatcher
Laravel Authentication
```

---

# 49. Integration Events Coverage

الاختبارات الحالية تغطي:

```text
✓ Login
✓ Failed
✓ Logout
✓ Attempting
✓ Authenticated
```

ولا تكتفي الاختبارات الحالية بالتحقق من وجود event فقط في الأحداث الأساسية، بل تتحقق أيضًا من بعض بيانات event مثل:

```text
Guard
User
Credentials
```

بحسب نوع الحدث.

---

# 50. PHPUnit Configuration

تم تحديث:

```text
phpunit.xml
```

لإضافة Integration Test Suite:

```xml
<testsuite name="Integration">
    <directory>tests/Integration</directory>
</testsuite>
```

وبذلك أصبح PHPUnit يكتشف Integration Tests تلقائيًا عند تنفيذ:

```bash
vendor/bin/phpunit
```

---

# 51. Current Test Structure

البنية الحالية:

```text
tests/
├── Unit/
│   ├── AuthGuardTest.php
│   ├── AuthManagerContractTest.php
│   ├── AuthManagerTest.php
│   └── CoreAuthServiceProviderTest.php
│
└── Integration/
    └── AuthenticationEventsTest.php
```

---

# 52. Mocking Strategy

يتم استخدام:

```text
Mockery
```

لعزل Laravel dependencies في Unit Tests.

مثال:

```php
$guard = Mockery::mock();

$guard
    ->shouldReceive('attempt')
    ->once()
    ->with([
        'email' => 'user@example.com',
        'password' => 'correct-password',
    ])
    ->andReturn(true);
```

وهذا يثبت أن `AuthManager` يمرر credentials كما هي.

---

# 53. Exception Mocking

يمكن محاكاة unexpected exception:

```php
$guard
    ->shouldReceive('attempt')
    ->once()
    ->with($credentials)
    ->andThrow(
        new RuntimeException(
            'Unexpected authentication failure.'
        )
    );
```

ثم يتم التحقق من:

```php
AuthenticationException
```

ومن:

```php
$exception->getPrevious()
```

---

# 54. Laravel Testbench

يتم استخدام Testbench لتوفير Laravel application environment مناسب لاختبار الحزمة.

يستخدم في:

```text
Service Provider Testing
Container Testing
Integration Testing
Laravel Authentication Integration
```

وهذا يسمح باختبار الحزمة ضمن بيئة Laravel قريبة من الاستخدام الفعلي.

---

# 55. Code Documentation

تمت إضافة PHPDoc إلى الدوال الأساسية.

يشمل ذلك:

```text
__construct()
login()
logout()
check()
user()
guard()
```

ويتم توضيح:

```php
@param array<string, mixed> $credentials
```

و:

```php
@return Authenticatable|null
```

و:

```php
@throws AuthenticationException
```

عند الحاجة.

---

# 56. Important Design Decisions

## 56.1 Laravel Contracts

تم الاعتماد على Contracts بدل implementations مباشرة عندما يكون ذلك مناسبًا.

من أهمها:

```php
Illuminate\Contracts\Auth\Factory
Illuminate\Contracts\Auth\Guard
Illuminate\Contracts\Auth\Authenticatable
Illuminate\Contracts\Auth\UserProvider
```

---

## 56.2 Contract / Implementation Separation

تم الفصل بين:

```text
AuthManagerInterface
AuthManager
```

وبين:

```text
GuardInterface
AuthGuard
```

وهذا يسمح بتغيير implementation مستقبلًا دون تغيير application code الذي يعتمد على Contracts.

---

## 56.3 Generalized Credentials

لا يتم فرض:

```text
email
```

ولا:

```text
username
```

بل:

```php
array<string, mixed>
```

بحسب احتياجات التطبيق.

---

## 56.4 Guard Abstraction

لا يتم إرجاع Laravel Guard مباشرة إلى التطبيق.

بدلًا من ذلك:

```text
Laravel Guard
      │
      ▼
AuthGuard
      │
      ▼
GuardInterface
```

---

## 56.5 Typed User

تم استخدام:

```php
?Authenticatable
```

بدل:

```php
mixed
```

لتوفير type واضح.

---

## 56.6 User Model Independence

لا تعتمد الحزمة على:

```text
App\Models\User
```

ولا على أي Model خاص بتطبيق معين.

---

## 56.7 Exception Boundary

تم إنشاء:

```text
AuthenticationException
```

كحد فاصل بين infrastructure وapplication.

---

## 56.8 Previous Exception Preservation

يتم الاحتفاظ بالـ original exception حتى لا تضيع معلومات debugging.

---

## 56.9 Normal Failure vs Exceptional Failure

السلوك المعتمد:

```text
Invalid credentials
        ↓
false
```

بينما:

```text
Unexpected error
        ↓
AuthenticationException
```

---

## 56.10 Laravel Events

تعتمد الحزمة حاليًا على Laravel Authentication Events الموجودة أصلًا.

لا يتم إنشاء custom authentication events داخل الحزمة إلا عند وجود حاجة معمارية واضحة.

---

# 57. Current Limitations

الميزات التالية لم يتم تنفيذها بعد:

```text
Custom Authentication Events
Token Authentication
Custom Guards
Advanced Multi-Guard Management
Password Management
Password Reset Workflow
User Repository
User Abstraction
Advanced Authentication Failure Policies
Advanced Authentication Exception Hierarchy
Authentication Error Codes
Authentication Error Categories
Authentication Logging Abstraction
Rate Limiting
Authentication Throttling
Remember Me Abstraction
```

هذه الميزات يجب تصميم كل منها بشكل مستقل قبل التنفيذ.

---

# 58. Completed Features

## Authentication Manager

```text
✓ AuthManagerInterface
✓ AuthManager
✓ Service Container Binding
✓ Singleton Binding
✓ Dependency Injection
```

## Generalized Login

```text
✓ Generalized login() API
✓ Credentials array
✓ Email credentials
✓ Username credentials
✓ Phone credentials
✓ Employee ID credentials
✓ Custom identifier support
✓ No forced email identifier
✓ No forced username identifier
```

## Guard Support

```text
✓ GuardInterface
✓ AuthGuard
✓ Named Guard Support
✓ Guard abstraction
✓ AuthManager Guard tests
✓ AuthGuard tests
```

## Authenticated User Support

```text
✓ Authenticatable Contract
✓ Typed user()
✓ Authenticatable|null
✓ Authenticated user tests
✓ Unauthenticated user tests
```

## Authentication Exception

```text
✓ AuthenticationException
✓ AuthManager exception handling
✓ AuthGuard exception handling
✓ Throwable normalization
✓ Previous exception preservation
✓ Exception tests
✓ Normal authentication failure remains false
```

## Authentication Events Integration

```text
✓ Laravel Attempting Event integration test
✓ Laravel Failed Event integration test
✓ Laravel Authenticated Event integration test
✓ Laravel Login Event integration test
✓ Laravel Logout Event integration test
✓ Event guard assertions
✓ Event user assertions
✓ Failed credentials assertions
```

## Testing Infrastructure

```text
✓ PHPUnit
✓ Laravel Testbench
✓ Mockery
✓ Unit Tests
✓ Integration Tests
✓ Integration Test Suite
```

## Documentation

```text
✓ Authentication Manager documentation
✓ Generalized Login documentation
✓ Guard Support documentation
✓ Typed User documentation
✓ Authentication Exception documentation
✓ Authentication Events documentation
✓ Architecture documentation
✓ Testing documentation
✓ Service Container documentation
```

---

# 59. Current Status

الأساس الحالي لـ Authentication Manager مكتمل ضمن النطاق الحالي.

الحالة:

```text
AuthManagerInterface             ✓
AuthManager                      ✓
GuardInterface                   ✓
AuthGuard                        ✓
AuthenticationException          ✓
Service Container Binding        ✓
Singleton Binding                ✓
Dependency Injection             ✓
Generalized Login API            ✓
Named Guard Support              ✓
Typed User Contract              ✓
Authenticatable|null              ✓
Exception Normalization           ✓
Previous Exception               ✓
Laravel Testbench                 ✓
Unit Tests                        ✓
Integration Tests                 ✓
Authentication Events Coverage    ✓
PHPDoc                            ✓
Documentation                     ✓
```

---

# 60. Current Test Result

آخر تشغيل كامل لـ PHPUnit:

```bash
vendor/bin/phpunit
```

النتيجة:

```text
34 tests
50 assertions
OK
```

الحالة:

```text
✓ Tests passing
✓ Assertions passing
✓ No failures
✓ No errors
✓ No risky tests
```

كما تم تشغيل Integration Tests بشكل مستقل:

```bash
vendor/bin/phpunit tests/Integration/AuthenticationEventsTest.php
```

والنتيجة:

```text
5 tests
16 assertions
OK
```

---

# 61. Verification

قبل Commit أو Pull Request يجب تنفيذ:

```bash
git diff --check
```

ثم:

```bash
vendor/bin/phpunit
```

ويجب أن تكون النتيجة:

```text
OK
```

بعد ذلك:

```bash
git status
```

ويجب التأكد من عدم وجود تغييرات غير مقصودة.

لمراجعة staged changes:

```bash
git diff --cached
```

ولفحص whitespace:

```bash
git diff --cached --check
```

---

# 62. Git Development Workflow

تم تقسيم التطوير إلى Feature Branches مستقلة.

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
    └── feature/authentication-events
```

بعد اكتمال Feature:

```text
Feature Branch
      │
      ▼
Tests
      │
      ▼
Documentation
      │
      ▼
Git Review
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

بعد الدمج والتأكد من استقرار `develop` يمكن حذف Feature Branch المنتهي.

---

# 63. Current Git Feature

الميزة الحالية التي تم تنفيذها هي:

```text
feature/authentication-events
```

وقد تم إنشاء Commit لها:

```text
1471ac7
```

Commit message:

```text
test: add authentication events integration coverage
```

يشمل الـ commit:

```text
phpunit.xml
tests/Integration/AuthenticationEventsTest.php
```

ويضيف:

```text
Integration Test Suite
Authentication Events Integration Coverage
```

---

# 64. Development Methodology

سيتم تطوير المراحل القادمة وفق:

```text
Architecture
     ↓
Contract
     ↓
Behavior Definition
     ↓
Tests
     ↓
Implementation
     ↓
Documentation
     ↓
Quality Checks
     ↓
PHPUnit
     ↓
Git Review
     ↓
Commit
     ↓
Pull Request
     ↓
Merge
```

ولا تتم إضافة abstraction جديد إلا بعد تحديد:

```text
Purpose
Responsibility
Boundary
Dependencies
Testing Strategy
Extension Strategy
```

---

# 65. Development Principles

## 65.1 Contract First

تعريف Contract قبل Implementation عندما يكون ذلك مناسبًا.

## 65.2 Test First / Test Driven Where Practical

تحديد behavior واختباره قبل أو بالتزامن مع implementation.

## 65.3 Loose Coupling

تقليل الارتباط المباشر بين application وLaravel implementations.

## 65.4 Dependency Injection

استخدام Constructor Injection وLaravel Container.

## 65.5 Type Safety

استخدام الأنواع الواضحة:

```php
?Authenticatable
```

بدل:

```php
mixed
```

عندما يكون النوع معروفًا.

## 65.6 Small Features

تقسيم التطوير إلى Features صغيرة قابلة للاختبار والمراجعة والدمج.

## 65.7 Backward Compatibility

عدم كسر API الحالي إلا بقرار معماري واضح.

## 65.8 Responsibility Separation

كل class أو abstraction يجب أن يمتلك مسؤولية محددة.

## 65.9 Testability

تصميم المكونات بحيث يمكن اختبارها مع أقل اعتماد ممكن على environment حقيقي.

## 65.10 Documentation

يجب أن يعكس التوثيق behavior الفعلي للكود.

## 65.11 Stable Develop

يجب أن يبقى:

```text
develop
```

قابلًا للاختبار بعد دمج الميزات.

## 65.12 Exception Boundary

يجب الحفاظ على boundary واضحة بين:

```text
Laravel / Infrastructure
```

و:

```text
Application
```

## 65.13 Integration Testing

يجب استخدام Integration Tests عندما يكون behavior متعلقًا بتكامل عدة مكونات Laravel وليس class واحدًا فقط.

---

# 66. Authentication Flow Summary

## Successful Login

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
Laravel Guard
     │
     ▼
attempt()
     │
     ├── Attempting
     │
     ├── Authenticated
     │
     └── Login
     │
     ▼
true
```

## Failed Login

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Laravel Guard
     │
     ▼
attempt()
     │
     ├── Attempting
     │
     └── Failed
     │
     ▼
false
```

## Unexpected Login Error

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Laravel Guard
     │
     ▼
attempt()
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

## Logout

```text
Application
     │
     ▼
AuthManager
     │
     ▼
Laravel Guard
     │
     ▼
logout()
     │
     ▼
Logout Event
```

## Named Guard

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
Laravel Guard
     │
     ▼
AuthGuard
     │
     ▼
GuardInterface
```

---

# 67. Final Architecture

التصميم الحالي يمكن تلخيصه كالتالي:

```text
                         Application
                              │
                              ▼
                    AuthManagerInterface
                              │
                              ▼
                         AuthManager
                       /            \
                      /              \
                     ▼                ▼
             Default Guard       Named Guard
                     │                │
                     ▼                ▼
             Laravel Guard        AuthGuard
                                      │
                                      ▼
                                GuardInterface

                         Authentication Errors
                                  │
                                  ▼
                         AuthenticationException

                         Laravel Authentication
                                  │
             ┌────────────────────┼────────────────────┐
             ▼                    ▼                    ▼
        Attempting             Failed             Authenticated
                                                      │
                                                      ▼
                                                   Login

                              Logout
                                │
                                ▼
                             Logout Event
```

---

# 68. Summary

تم بناء أساس قوي وقابل للتوسع لطبقة Authentication داخل `core-auth`.

المكونات الأساسية الحالية:

```text
AuthManagerInterface
AuthManager
GuardInterface
AuthGuard
AuthenticationException
CoreAuthServiceProvider
```

أصبح التطبيق قادرًا على استخدام:

```php
$authManager->login($credentials);
```

دون فرض نوع identifier معين.

كما أصبح بالإمكان:

```php
$authManager->guard('api');
```

للحصول على Guard abstraction مستقل.

ويمكن الحصول على المستخدم authenticated باستخدام:

```php
$user = $authManager->user();
```

مع type واضح:

```php
?Authenticatable
```

كما أصبحت أخطاء Authentication غير المتوقعة تمر عبر:

```text
AuthenticationException
```

مع الحفاظ على:

```php
$exception->getPrevious()
```

لأغراض debugging.

تم كذلك اختبار تكامل Authentication lifecycle مع Laravel من خلال:

```text
Attempting
Failed
Authenticated
Login
Logout
```

باستخدام Integration Tests وLaravel Testbench.

الحالة النهائية الحالية:

```text
34 tests
50 assertions
OK
```

وأصبح لدينا أساس معماري واضح يسمح بإضافة ميزات Authentication مستقبلية دون ربط الحزمة بشكل مباشر بـ:

```text
User Model
Authentication Identifier
Laravel Guard Implementation
Authentication Infrastructure Exceptions
```

مع الحفاظ على:

```text
Contract-based Design
Loose Coupling
Dependency Injection
Type Safety
Testability
Integration Testing
Exception Boundary
Laravel Compatibility
Extensibility
Maintainability
```

ويجب أن تعتمد المراحل القادمة على هذه البنية بدل إضافة abstractions متداخلة أو مسؤوليات لا تنتمي إلى `AuthManager`.
