# Authentication Manager

## Phase

Authentication Manager, Generalized Login, Guard Support, Typed Authenticated User Support, and Authentication Exception Integration

## Goal

إنشاء طبقة موحدة وقابلة للتوسع للتعامل مع نظام المصادقة في Laravel، مع فصل الحزمة عن الاستخدام المباشر لتفاصيل Authentication قدر الإمكان.

تهدف هذه المرحلة إلى بناء أساس واضح وقابل للاختبار والتوسع لطبقة المصادقة داخل الحزمة، مع الاعتماد على:

* Laravel Contracts
* Dependency Injection
* Laravel Service Container
* Laravel Testbench
* PHPUnit
* Mockery
* Custom Authentication Exception Handling

كما تم تطوير واجهة `login()` لتقبل credentials عامة دون فرض نوع محدد من identifiers، وإضافة دعم لاختيار Guard محدد من خلال abstraction مستقل، مع استخدام Laravel `Authenticatable` Contract لتمثيل المستخدم authenticated بشكل typed وواضح.

بالإضافة إلى ذلك، تم إضافة طبقة موحدة لمعالجة الاستثناءات غير المتوقعة أثناء عملية Authentication من خلال `AuthenticationException` الخاصة بالحزمة، مع الحفاظ على الاستثناء الأصلي كـ previous exception.

---

# What Was Implemented

تم تنفيذ المكونات والتغييرات التالية:

* إنشاء `AuthManagerInterface`
* إنشاء `AuthManager`
* إنشاء `GuardInterface`
* إنشاء `AuthGuard`
* إنشاء `AuthenticationException`
* تسجيل `AuthManagerInterface` داخل Laravel Service Container
* ربط `AuthManagerInterface` بـ `AuthManager`
* تسجيل `AuthManager` باستخدام `singleton`
* استخدام Laravel `AuthFactory`
* استخدام Constructor Dependency Injection
* فصل Contract عن Implementation
* تعميم API الخاص بـ `login()`
* إزالة الاعتماد على `email` كـ identifier إجباري
* دعم credentials عامة
* دعم أنواع مختلفة من identifiers
* إضافة Guard abstraction
* دعم الحصول على Guard باسم محدد
* استخدام `Illuminate\Contracts\Auth\Authenticatable`
* جعل `user()` يعيد `Authenticatable|null`
* دعم حالة عدم وجود مستخدم authenticated من خلال `null`
* إضافة Authentication Exception abstraction
* تحويل الاستثناءات غير المتوقعة أثناء `login()` إلى `AuthenticationException`
* الحفاظ على الاستثناء الأصلي باستخدام `previous exception`
* تطبيق Authentication Exception handling في `AuthManager`
* تطبيق Authentication Exception handling في `AuthGuard`
* إضافة اختبارات للتأكد من تحويل الاستثناءات
* إضافة اختبار للتأكد من الحفاظ على الاستثناء الأصلي
* إضافة Unit Tests
* إضافة Contract Tests
* إضافة Service Provider Tests
* استخدام Laravel Testbench
* استخدام Mockery لعزل dependencies
* إضافة PHPDoc للدوال الأساسية
* تحديث الاختبارات لتتوافق مع typed authenticated user contract
* تحديث التوثيق ليعكس التصميم الفعلي الحالي

---

# Project Structure

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
│   ├── AuthGuard.php
│   └── AuthManager.php
│
└── CoreAuthServiceProvider.php

tests/
└── Unit/
    ├── AuthGuardTest.php
    ├── AuthManagerContractTest.php
    ├── AuthManagerTest.php
    └── CoreAuthServiceProviderTest.php

docs/
└── 04-auth-manager.md
```

---

# Architecture

يعتمد التصميم الحالي على فصل Contract عن Implementation، مع استخدام Laravel Authentication Contracts للوصول إلى نظام المصادقة.

التصميم الفعلي الحالي:

```text
                    Application
                         │
                         ▼
               AuthManagerInterface
                         │
                         ▼
                    AuthManager
                    /         \
                   /           \
                  ▼             ▼
          Default Guard     Named Guard
                │                │
                ▼                ▼
        Laravel Guard       AuthGuard
                │                │
                │                ▼
                │         GuardInterface
                │
                ▼
        AuthenticationException
```

يوجد مساران رئيسيان للتعامل مع Authentication:

1. Default Guard من خلال `AuthManager`
2. Named Guard من خلال `AuthManager` و`AuthGuard`

وتوجد طبقة موحدة للتعامل مع الاستثناءات غير المتوقعة أثناء `login()`:

```text
Laravel / Guard Exception
          │
          ▼
AuthenticationException
          │
          ▼
Application
```

---

# Default Guard

يستخدم `AuthManager` الـ default guard من خلال Laravel `AuthFactory`.

التدفق:

```text
AuthManager
     │
     ▼
AuthFactory::guard()
     │
     ▼
Default Laravel Guard
```

ويتم من خلال هذا المسار تنفيذ:

* `login()`
* `logout()`
* `check()`
* `user()`

أما في حالة حدوث استثناء غير متوقع أثناء `login()`:

```text
AuthManager
     │
     ▼
Default Laravel Guard
     │
     │ throws Throwable
     ▼
AuthenticationException
     │
     ▼
Application
```

---

# Named Guard

عند طلب Guard باسم محدد:

```php
$authManager->guard('api');
```

يقوم `AuthManager` بالحصول على Laravel Guard المطلوب ثم تغليفه داخل `AuthGuard`.

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

وبذلك لا يتم إعادة Laravel Guard مباشرة إلى التطبيق، وإنما يتم إرجاع abstraction خاص بالحزمة:

```php
GuardInterface
```

---

# AuthManagerInterface

المسار:

```text
src/Contracts/AuthManagerInterface.php
```

يوفر `AuthManagerInterface` العقد الرئيسي للتعامل مع Authentication Manager.

العمليات الحالية:

```php
public function login(array $credentials): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;

public function guard(string $name): GuardInterface;
```

ويستخدم الـ Contract:

```php
use Illuminate\Contracts\Auth\Authenticatable;
```

لتمثيل المستخدم authenticated.

كما يوضح `login()` إمكانية إطلاق:

```php
AuthenticationException
```

عند حدوث فشل غير متوقع أثناء Authentication.

---

# AuthManagerInterface Responsibilities

## `login()`

تحاول تسجيل دخول المستخدم باستخدام credentials يتم تمريرها إلى الـ default guard.

التوقيع:

```php
public function login(array $credentials): bool;
```

تستقبل:

```text
array<string, mixed> $credentials
```

وتعيد:

```text
true  → authentication succeeded
false → authentication failed
```

وفي حالة حدوث استثناء غير متوقع أثناء محاولة Authentication، يتم إطلاق:

```php
AuthenticationException
```

لا يفرض الـ Contract استخدام identifier محدد.

يمكن أن تحتوي credentials على:

```text
email
username
phone
employee_id
custom identifier
```

بحسب ما يدعمه التطبيق وAuthentication Guard.

---

## `logout()`

تقوم بتسجيل خروج المستخدم الحالي من خلال الـ default guard.

التوقيع:

```php
public function logout(): void;
```

القيمة المرجعة:

```text
void
```

---

## `check()`

تتحقق مما إذا كان هناك مستخدم authenticated حاليًا باستخدام الـ default guard.

التوقيع:

```php
public function check(): bool;
```

النتيجة:

```text
true  → user is authenticated
false → user is not authenticated
```

---

## `user()`

ترجع المستخدم authenticated الحالي باستخدام الـ default guard.

التوقيع:

```php
public function user(): ?Authenticatable;
```

يعتمد هذا النوع على Laravel Contract:

```php
Illuminate\Contracts\Auth\Authenticatable
```

القيمة المرجعة يمكن أن تكون:

```text
Authenticatable → عند وجود مستخدم authenticated
null            → عند عدم وجود مستخدم authenticated
```

---

## `guard()`

ترجع Guard abstraction باسم محدد.

التوقيع:

```php
public function guard(string $name): GuardInterface;
```

مثال:

```php
$guard = $authManager->guard('api');
```

القيمة المرجعة هي:

```text
GuardInterface
```

وليس Laravel Guard مباشرة.

---

# AuthManager

المسار:

```text
src/Services/AuthManager.php
```

يقوم `AuthManager` بتنفيذ:

```php
AuthManagerInterface
```

وهو الطبقة الرئيسية التي توفر API موحدًا للتعامل مع Authentication.

يعتمد `AuthManager` على Laravel Authentication من خلال:

```php
Illuminate\Contracts\Auth\Factory
```

ويتم حقن `AuthFactory` باستخدام Constructor Injection.

---

# Constructor

التصميم الحالي:

```php
public function __construct(
    private readonly AuthFactory $auth
) {
}
```

يتم تمرير Laravel `AuthFactory` إلى `AuthManager` عن طريق Dependency Injection.

هذا يقلل coupling ويسمح بعزل dependency أثناء الاختبارات.

الفوائد:

* Testability
* Loose Coupling
* Maintainability
* Extensibility

---

# AuthManager Responsibilities

## `login()`

تقوم بمحاولة تسجيل الدخول باستخدام الـ default guard.

المنطق الأساسي:

```php
try {
    return $this->auth
        ->guard()
        ->attempt($credentials);
} catch (Throwable $exception) {
    throw new AuthenticationException(
        $exception->getMessage(),
        (int) $exception->getCode(),
        $exception
    );
}
```

يتم تمرير credentials كما هي إلى Laravel Guard.

في حالة نجاح Authentication:

```text
true
```

في حالة فشل credentials بدون استثناء:

```text
false
```

في حالة حدوث استثناء غير متوقع:

```text
Throwable
   │
   ▼
AuthenticationException
```

ويتم الاحتفاظ بالاستثناء الأصلي كـ previous exception.

---

## `logout()`

تقوم بتسجيل خروج المستخدم الحالي باستخدام الـ default guard.

التنفيذ:

```php
public function logout(): void
{
    $this->auth
        ->guard()
        ->logout();
}
```

---

## `check()`

تتحقق من حالة Authentication الحالية باستخدام الـ default guard.

التنفيذ:

```php
public function check(): bool
{
    return $this->auth
        ->guard()
        ->check();
}
```

---

## `user()`

ترجع المستخدم authenticated الحالي من الـ default guard.

التنفيذ:

```php
public function user(): ?Authenticatable
{
    return $this->auth
        ->guard()
        ->user();
}
```

الحالات الممكنة:

```text
Authenticated
     │
     ▼
Authenticatable
```

أو:

```text
Not authenticated
     │
     ▼
null
```

---

## `guard()`

تسمح بالحصول على Guard باسم محدد.

التنفيذ:

```php
public function guard(string $name): GuardInterface
{
    return new AuthGuard(
        $this->auth->guard($name)
    );
}
```

يتم الحصول على Laravel Guard من `AuthFactory` ثم تغليفه داخل `AuthGuard`.

---

# Generalized Login API

تم تطوير `login()` ليكون عامًا وغير مرتبط باستخدام email أو username.

## Previous API

كان التصميم السابق يعتمد على identifier محدد، مثل:

```php
$authManager->login(
    'user@example.com',
    'password'
);
```

هذا التصميم يفرض شكلًا محددًا لبيانات تسجيل الدخول.

---

## Current API

أصبح `login()` يستقبل credentials كاملة:

```php
$authManager->login([
    'email' => 'user@example.com',
    'password' => 'password',
]);
```

وبذلك أصبحت مسؤولية تحديد نوع identifier لدى التطبيق وLaravel Guard.

---

# Authentication Identifier Flexibility

الحزمة لا تفرض على التطبيق استخدام حقل معين لتسجيل الدخول.

يمكن للتطبيق استخدام:

## Email

```php
$authManager->login([
    'email' => 'user@example.com',
    'password' => 'correct-password',
]);
```

## Username

```php
$authManager->login([
    'username' => 'ahmed',
    'password' => 'correct-password',
]);
```

## Phone

```php
$authManager->login([
    'phone' => '777123456',
    'password' => 'correct-password',
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

يمكن استخدام أي credential structure يدعمها Authentication Guard.

مثال:

```php
$authManager->login([
    'national_id' => '123456789',
    'password' => 'password',
]);
```

المهم أن `AuthManager` لا يفرض نوع identifier.

---

# Responsibility Boundary

يجب الحفاظ على الفصل التالي:

```text
Application
     │
     │ Defines credentials
     ▼
AuthManager
     │
     │ Passes credentials
     ▼
Laravel Guard
     │
     │ Performs authentication
     ▼
Authentication System
```

`AuthManager` لا يقرر:

* ما هو identifier الصحيح
* كيف يتم البحث عن المستخدم
* كيف يتم التحقق من password
* أين يتم تخزين المستخدم
* ما هو User Model

هذه مسؤوليات Laravel Authentication وUser Provider والتطبيق.

---

# Guard Support

تمت إضافة طبقة مستقلة للتعامل مع Guards.

المكونات:

```text
src/Contracts/GuardInterface.php
src/Services/AuthGuard.php
```

الهدف هو توفير abstraction خاص بالحزمة عند التعامل مع Guard محدد.

---

# GuardInterface

المسار:

```text
src/Contracts/GuardInterface.php
```

يوفر `GuardInterface` العمليات الأساسية التي تحتاجها الحزمة عند التعامل مع Guard.

العمليات الحالية:

```php
public function login(array $credentials): bool;

public function logout(): void;

public function check(): bool;

public function user(): ?Authenticatable;
```

ويستخدم:

```php
Illuminate\Contracts\Auth\Authenticatable
```

لتمثيل المستخدم authenticated.

كما يوضح `login()` إمكانية إطلاق:

```php
AuthenticationException
```

عند حدوث فشل غير متوقع أثناء Authentication.

---

# GuardInterface Responsibilities

## `login()`

تحاول المصادقة باستخدام credentials:

```php
public function login(array $credentials): bool;
```

لا يتم فرض identifier معين.

النتائج الممكنة:

```text
true  → authentication succeeded
false → authentication failed
```

وفي حالة حدوث استثناء غير متوقع:

```text
AuthenticationException
```

---

## `logout()`

تسجل خروج المستخدم الحالي:

```php
public function logout(): void;
```

---

## `check()`

تتحقق من حالة Authentication:

```php
public function check(): bool;
```

---

## `user()`

ترجع المستخدم authenticated الحالي:

```php
public function user(): ?Authenticatable;
```

النتيجة:

```text
Authenticatable → إذا كان المستخدم authenticated
null            → إذا لم يكن هناك مستخدم authenticated
```

---

# AuthGuard

المسار:

```text
src/Services/AuthGuard.php
```

يقوم `AuthGuard` بتنفيذ:

```php
GuardInterface
```

وهو Adapter يربط `GuardInterface` الخاص بالحزمة مع Laravel Guard.

يعتمد على:

```php
Illuminate\Contracts\Auth\Guard
```

من خلال Constructor Injection:

```php
public function __construct(
    private readonly LaravelGuard $guard
) {
}
```

---

# AuthGuard Responsibilities

## `login()`

تقوم بمحاولة المصادقة باستخدام Laravel Guard.

التنفيذ الحالي يتضمن Authentication Exception handling:

```php
try {
    return $this->guard->attempt($credentials);
} catch (Throwable $exception) {
    throw new AuthenticationException(
        $exception->getMessage(),
        (int) $exception->getCode(),
        $exception
    );
}
```

في حالة نجاح Authentication:

```text
true
```

في حالة فشل credentials:

```text
false
```

في حالة حدوث استثناء غير متوقع:

```text
Throwable
   │
   ▼
AuthenticationException
```

ويتم الاحتفاظ بالاستثناء الأصلي كـ previous exception.

---

## `logout()`

```php
public function logout(): void
{
    $this->guard->logout();
}
```

---

## `check()`

```php
public function check(): bool
{
    return $this->guard->check();
}
```

---

## `user()`

```php
public function user(): ?Authenticatable
{
    return $this->guard->user();
}
```

يعتمد `AuthGuard` على Laravel Guard لتنفيذ عملية الحصول على المستخدم.

---

# AuthenticationException

المسار:

```text
src/Exceptions/AuthenticationException.php
```

تمت إضافة `AuthenticationException` كاستثناء مخصص للحزمة للتعامل مع الأخطاء غير المتوقعة أثناء عمليات Authentication.

الهدف من هذا الاستثناء هو منع تسريب تفاصيل implementation الخاصة بـ Laravel أو dependencies إلى الطبقات الأعلى من التطبيق.

التدفق:

```text
Laravel Guard
      │
      │ throws Throwable
      ▼
AuthManager / AuthGuard
      │
      ▼
AuthenticationException
      │
      │ previous exception
      ▼
Application
```

---

# Exception Normalization

تقوم طبقة Authentication بتحويل الاستثناءات غير المتوقعة إلى exception موحد خاص بالحزمة.

مثال:

```text
RuntimeException
       │
       ▼
AuthenticationException
```

وبذلك يستطيع التطبيق التعامل مع:

```php
AuthenticationException
```

بدل الحاجة إلى معرفة كل أنواع الاستثناءات التي يمكن أن تنتجها dependencies الداخلية.

---

# Previous Exception Preservation

عند تحويل الاستثناء الأصلي، لا يتم التخلص منه.

يتم تمريره إلى:

```php
AuthenticationException
```

كـ previous exception.

مثال:

```php
throw new AuthenticationException(
    $exception->getMessage(),
    (int) $exception->getCode(),
    $exception
);
```

وبذلك يمكن الوصول إلى الاستثناء الأصلي من خلال:

```php
$exception->getPrevious();
```

وهذا يحافظ على معلومات debugging ويمنع فقدان السبب الحقيقي للمشكلة.

---

# Authentication Exception Boundary

حدود معالجة الاستثناءات الحالية هي:

```text
Laravel Authentication
        │
        ▼
Laravel Guard
        │
        ▼
AuthManager / AuthGuard
        │
        │ normalize unexpected Throwable
        ▼
AuthenticationException
        │
        ▼
Application
```

المسؤولية الأساسية هي توفير exception type موحد على مستوى الحزمة، مع الحفاظ على السبب الأصلي.

---

# Exception Behavior

يجب التفريق بين Authentication failure العادي والاستثناء غير المتوقع.

## Authentication Failure

عندما يرفض Laravel Guard credentials:

```text
attempt()
    │
    ▼
false
```

يتم إرجاع:

```php
false
```

ولا يتم إطلاق `AuthenticationException`.

---

## Unexpected Authentication Error

عندما يحدث exception أثناء Authentication:

```text
attempt()
    │
    ▼
Throwable
    │
    ▼
AuthenticationException
```

وبذلك يتم التمييز بين:

```text
Invalid credentials
        ↓
false
```

و:

```text
Unexpected authentication error
        ↓
AuthenticationException
```

هذا الفصل مهم للحفاظ على API واضح وقابل للتعامل معه من التطبيق.

---

# Authenticated User Contract

تعتمد الحزمة على Laravel Contract:

```php
Illuminate\Contracts\Auth\Authenticatable
```

بدل فرض Model محدد على التطبيق.

لذلك يمكن أن يكون المستخدم:

```text
Laravel User Model
```

أو أي implementation آخر يطبق:

```php
Authenticatable
```

وهذا يمنع الحزمة من الارتباط بـ:

```text
App\Models\User
```

أو أي User Model خاص بتطبيق معين.

---

# User State

يمكن التحقق من حالة المستخدم من خلال:

```php
$authManager->check();
```

ثم الحصول على المستخدم:

```php
$user = $authManager->user();
```

الحالات:

```text
check() === true
        │
        ▼
user() returns Authenticatable
```

أو:

```text
check() === false
        │
        ▼
user() returns null
```

وتعتمد النتيجة الفعلية على Laravel Guard المستخدم.

---

# Named Guard Usage

يمكن طلب Guard باسم محدد من خلال:

```php
$guard = $authManager->guard('api');
```

ثم استخدام الـ abstraction:

```php
$guard->login([
    'email' => 'user@example.com',
    'password' => 'password',
]);
```

أو:

```php
$guard->check();
```

أو:

```php
$user = $guard->user();
```

أو:

```php
$guard->logout();
```

الـ application يتعامل مع:

```php
GuardInterface
```

بدل التعامل مباشرة مع:

```php
Illuminate\Contracts\Auth\Guard
```

---

# Authentication Flow

## Default Guard Login

```text
Application
     │
     │ credentials
     ▼
AuthManagerInterface
     │
     ▼
AuthManager
     │
     ▼
AuthFactory::guard()
     │
     ▼
Default Laravel Guard
     │
     ▼
attempt()
     │
     ├──► true
     │
     ├──► false
     │
     └──► Throwable
              │
              ▼
    AuthenticationException
```

---

## Default Guard Logout

```text
Application
     │
     ▼
AuthManager
     │
     ▼
AuthFactory::guard()
     │
     ▼
Default Laravel Guard
     │
     ▼
logout()
```

---

## Default Guard Check

```text
Application
     │
     ▼
AuthManager
     │
     ▼
AuthFactory::guard()
     │
     ▼
Default Laravel Guard
     │
     ▼
check()
```

---

## Default Guard User

```text
Application
     │
     ▼
AuthManager
     │
     ▼
AuthFactory::guard()
     │
     ▼
Default Laravel Guard
     │
     ▼
user()
     │
     ├──► Authenticatable
     │
     └──► null
```

---

## Named Guard

```text
Application
     │
     │ guard name
     ▼
AuthManager
     │
     ▼
AuthFactory::guard(name)
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

# Service Container Binding

تم تسجيل `AuthManagerInterface` داخل Laravel Service Container.

المسؤول عن ذلك:

```text
src/CoreAuthServiceProvider.php
```

ويتم التسجيل باستخدام:

```php
$this->app->singleton(
    AuthManagerInterface::class,
    AuthManager::class
);
```

وبذلك يمكن الحصول على `AuthManager` من Laravel Container من خلال:

```php
$app->make(AuthManagerInterface::class);
```

---

# Singleton Binding

تم استخدام:

```php
singleton()
```

بدل:

```php
bind()
```

وبالتالي يقوم Laravel Container بإعادة نفس instance من `AuthManager` خلال دورة حياة الـ Application Container.

تم اختبار هذا السلوك في:

```text
CoreAuthServiceProviderTest
```

---

# Service Provider

المسار:

```text
src/CoreAuthServiceProvider.php
```

المسؤولية الحالية للـ Service Provider هي تسجيل الخدمات الأساسية للحزمة داخل Laravel Service Container.

التدفق:

```text
AuthManagerInterface
        │
        ▼
AuthManager
```

ويتم الاعتماد على Laravel Container لإدارة Dependency Injection.

---

# Dependency Injection

يعتمد `AuthManager` على Dependency Injection بدل إنشاء dependencies داخله.

يتم حقن:

```php
Illuminate\Contracts\Auth\Factory
```

من خلال Constructor.

كما يتم حقن Laravel Guard داخل `AuthGuard`:

```php
Illuminate\Contracts\Auth\Guard
```

من خلال Constructor.

هذا يسمح بعزل dependencies أثناء الاختبارات.

الفوائد:

* Testability
* Loose Coupling
* Maintainability
* Extensibility

---

# Code Documentation

تمت إضافة PHPDoc إلى الدوال الأساسية.

يشمل ذلك:

* `__construct()`
* `login()`
* `logout()`
* `check()`
* `user()`
* `guard()`

ويتم توضيح نوع credentials باستخدام:

```php
@param array<string, mixed> $credentials
```

كما يتم توضيح الاستثناء المتوقع من `login()`:

```php
@throws AuthenticationException
```

ويتم توضيح المستخدم authenticated باستخدام:

```php
@return Authenticatable|null
```

وهذا يعكس طبيعة API الحالية بشكل واضح.

---

# Testing

تم استخدام:

```text
PHPUnit 11.5.56
Laravel Testbench
Mockery
PHP 8.2.12
```

لتشغيل الاختبارات:

```bash
vendor/bin/phpunit
```

الحالة الحالية:

```text
29 tests
34 assertions
OK
```

آخر تشغيل ناجح:

```text
............................. 29 / 29 (100%)

OK (29 tests, 34 assertions)
```

ولا توجد حاليًا:

```text
Failures
Errors
Risky Tests
```

---

# Testing Structure

الاختبارات الحالية:

```text
tests/
└── Unit/
    ├── AuthGuardTest.php
    ├── AuthManagerContractTest.php
    ├── AuthManagerTest.php
    └── CoreAuthServiceProviderTest.php
```

---

# AuthManager Tests

يتم اختبار:

* تطبيق `AuthManagerInterface`
* نجاح تسجيل الدخول
* فشل تسجيل الدخول
* تمرير credentials إلى الـ default guard
* دعم email credentials
* دعم username credentials
* دعم credentials عامة
* التحقق من Authentication state
* إرجاع المستخدم authenticated
* إرجاع `null` عند عدم وجود مستخدم
* تنفيذ logout
* الحصول على Guard باسم محدد
* إرجاع `GuardInterface` من `guard()`
* تحويل unexpected authentication exceptions إلى `AuthenticationException`
* الحفاظ على behavior الخاص بـ `false` عند فشل credentials

---

# AuthGuard Tests

يتم اختبار:

* تطبيق `GuardInterface`
* نجاح تسجيل الدخول
* فشل تسجيل الدخول
* تنفيذ logout
* التحقق من Authentication state
* إرجاع المستخدم authenticated
* إرجاع `null` عند عدم وجود مستخدم
* تحويل unexpected authentication exceptions إلى `AuthenticationException`
* الحفاظ على الاستثناء الأصلي كـ previous exception

---

# Authentication Exception Tests

تمت إضافة اختبارات خاصة بسلوك Authentication Exception.

يتم اختبار السيناريو التالي:

```text
Laravel Guard
      │
      │ throws RuntimeException
      ▼
AuthManager / AuthGuard
      │
      ▼
AuthenticationException
```

ويتم التأكد من:

1. أن `AuthenticationException` يتم إطلاقه.
2. أن رسالة الاستثناء الأصلي يتم الحفاظ عليها.
3. أن الاستثناء الأصلي يتم تخزينه كـ previous exception.

مثال:

```php
$originalException = new RuntimeException(
    'Unexpected authentication failure.'
);
```

ثم:

```php
$this->assertSame(
    $originalException,
    $exception->getPrevious()
);
```

وبذلك يتم ضمان عدم فقدان السبب الأصلي للخطأ.

---

# Authentication Failure vs Exception Testing

يتم اختبار حالتين مختلفتين:

## Normal Authentication Failure

```text
attempt()
     │
     ▼
false
```

والنتيجة:

```php
false
```

## Unexpected Authentication Error

```text
attempt()
     │
     ▼
RuntimeException
     │
     ▼
AuthenticationException
```

وهذا يضمن أن Authentication failure العادي لا يتم التعامل معه كـ system exception.

---

# AuthManager Contract Tests

يتم التحقق من أن `AuthManagerInterface` يوفر العمليات الأساسية المتوقعة:

```text
login()
logout()
check()
user()
guard()
```

ويضمن ذلك وجود API واضح وثابت لمدير المصادقة.

---

# CoreAuthServiceProvider Tests

يتم اختبار:

* تسجيل Service Provider
* تسجيل `AuthManager`
* ربط `AuthManagerInterface`
* Singleton binding
* إمكانية الحصول على الخدمة من Container

---

# Mocking Strategy

تم استخدام:

```text
Mockery
```

لعزل Authentication dependencies أثناء Unit Testing.

يتم Mock للـ Laravel Guard وAuthFactory بدل الاعتماد على Authentication حقيقي.

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

كما تم اختبار credentials مختلفة:

```php
$guard
    ->shouldReceive('attempt')
    ->once()
    ->with([
        'username' => 'ahmed',
        'password' => 'correct-password',
    ])
    ->andReturn(true);
```

وهذا يثبت أن `AuthManager` لا يفرض استخدام email.

---

# Authentication Exception Mocking

يتم استخدام Mockery لمحاكاة حدوث exception من Laravel Guard.

مثال:

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

ثم يتم التحقق من أن الطبقة تقوم بتحويله إلى:

```php
AuthenticationException
```

مع الحفاظ على:

```php
$exception->getPrevious()
```

---

# Authenticated User Testing

يتم استخدام:

```php
Mockery::mock(Authenticatable::class)
```

عند اختبار وجود مستخدم authenticated.

وهذا أفضل من استخدام Model حقيقي أو `stdClass` في Unit Tests، لأنه يختبر الـ Contract مباشرة.

مثال:

```php
$user = Mockery::mock(Authenticatable::class);
```

ثم يتم إرجاعه من Guard:

```php
$guard
    ->shouldReceive('user')
    ->once()
    ->andReturn($user);
```

كما يتم اختبار حالة عدم وجود مستخدم:

```php
$guard
    ->shouldReceive('user')
    ->once()
    ->andReturn(null);
```

وبذلك يتم اختبار الحالتين:

```text
Authenticatable
null
```

---

# Laravel Testbench

تم استخدام Laravel Testbench لتوفير بيئة اختبار مناسبة لحزمة Laravel.

يستخدم Testbench لاختبار:

* Service Provider
* Laravel Container
* Package integration
* Dependency Injection
* Laravel services

مع الحفاظ على عزل Unit Tests قدر الإمكان.

---

# Dependencies

تعتمد الحزمة على Laravel Contracts وLaravel Framework وفق إعدادات `composer.json` الحالية.

تعتمد بيئة التطوير والاختبارات على:

* Laravel Framework
* PHPUnit
* Laravel Testbench
* Mockery

يجب اعتبار `composer.json` المصدر النهائي لقائمة dependencies والإصدارات.

---

# Important Design Decisions

## 1. استخدام Laravel Contracts

تعتمد الحزمة على Contracts بدل الارتباط المباشر بتنفيذ محدد عندما يكون ذلك مناسبًا للتصميم.

من أهم dependencies المستخدمة:

```php
Illuminate\Contracts\Auth\Factory
```

و:

```php
Illuminate\Contracts\Auth\Guard
```

و:

```php
Illuminate\Contracts\Auth\Authenticatable
```

---

## 2. فصل Contract عن Implementation

تم فصل:

```text
AuthManagerInterface
```

عن:

```text
AuthManager
```

كما تم فصل:

```text
GuardInterface
```

عن:

```text
AuthGuard
```

وهذا يسمح بتغيير implementation مستقبلًا دون تغيير الطبقات التي تعتمد على Contracts.

---

## 3. Generalized Credentials

لا يفرض `AuthManager` استخدام:

```text
email
```

أو:

```text
username
```

بل يستقبل credentials عامة:

```php
[
    'identifier' => '...',
    'password' => '...',
]
```

بحسب ما يدعمه Guard والتطبيق.

يمكن أن تكون credentials باستخدام:

```text
email
username
phone
employee_id
custom identifier
```

ولا تقوم الحزمة بتحديد أي منها كخيار إجباري.

---

## 4. Guard Abstraction

تمت إضافة:

```text
GuardInterface
AuthGuard
```

لتوفير abstraction خاص بالحزمة عند التعامل مع Guard محدد.

ويتم الوصول إلى Guard من خلال:

```php
$authManager->guard('api');
```

ثم يعاد:

```php
GuardInterface
```

بدل Laravel Guard مباشرة.

---

## 5. Typed Authenticated User

تم استخدام:

```php
Illuminate\Contracts\Auth\Authenticatable
```

بدل استخدام:

```php
mixed
```

لتمثيل المستخدم authenticated.

ويكون النوع:

```php
?Authenticatable
```

لأن حالة Authentication قد تكون:

```text
Authenticated user → Authenticatable
No authenticated user → null
```

هذا يوفر Contract واضحًا ويحسن:

* Type Safety
* Static Analysis
* IDE Support
* Maintainability
* Extensibility

---

## 6. عدم فرض User Model

الحزمة لا تفرض:

```text
App\Models\User
```

ولا أي Model محدد.

بل تعتمد على:

```php
Illuminate\Contracts\Auth\Authenticatable
```

وهذا يسمح للتطبيق باستخدام أي User implementation متوافقة مع Laravel Authentication.

---

## 7. Singleton Binding

تم استخدام:

```php
$this->app->singleton()
```

لإدارة `AuthManager` كخدمة مشتركة داخل Laravel Application Container.

---

## 8. Dependency Injection

يتم حقن dependencies بدل إنشائها داخل الخدمات.

هذا يحسن:

* Testability
* Maintainability
* Loose Coupling
* Extensibility

---

## 9. Responsibility Separation

تلتزم الحزمة بالفصل بين مسؤوليات Authentication المختلفة.

`AuthManager` مسؤول عن توفير API موحد.

`AuthGuard` مسؤول عن Adapter بين package contract وLaravel Guard.

Laravel Guard مسؤول عن تنفيذ Authentication.

والتطبيق مسؤول عن تحديد credentials المناسبة.

`AuthenticationException` مسؤول عن تقديم exception موحد على مستوى الحزمة عند حدوث unexpected authentication errors.

---

## 10. Exception Normalization

يتم تحويل الاستثناءات غير المتوقعة أثناء Authentication إلى:

```php
AuthenticationException
```

بدل تمرير كل أنواع الاستثناءات الداخلية مباشرة إلى التطبيق.

هذا يوفر boundary واضحًا بين:

```text
Laravel / Infrastructure
```

و:

```text
Application
```

مع الحفاظ على الاستثناء الأصلي كـ previous exception.

---

## 11. Normal Failure vs Exceptional Failure

يجب الحفاظ على الفرق بين:

```text
Invalid credentials
        ↓
false
```

وبين:

```text
Unexpected authentication error
        ↓
AuthenticationException
```

هذا القرار يحافظ على semantics واضحة لعملية `login()`.

---

# Current Limitations

تم الانتهاء من الأساس الحالي لـ Authentication Manager وGeneralized Login وGuard abstraction وTyped Authenticated User Support وAuthentication Exception Integration، ولكن بعض الوظائف المتقدمة ما زالت خارج نطاق هذه المرحلة.

حاليًا لا توجد:

* Events مخصصة للمصادقة
* Token Authentication كامل داخل الحزمة
* Guards مخصصة خاصة بالحزمة
* نظام متقدم لإدارة عدة Guards
* سياسات متقدمة لمعالجة Authentication failures
* User abstraction خاصة بالحزمة
* User Repository abstraction
* عمليات CRUD أو إدارة متقدمة للمستخدمين
* Password management abstraction
* Password reset workflow داخل الحزمة
* Exception hierarchy متقدمة لأنواع Authentication المختلفة
* Error codes أو error categories مخصصة لـ AuthenticationException
* Logging abstraction مخصصة لأحداث Authentication

هذه الوظائف يجب تصميمها واختبارها بشكل مستقل قبل تنفيذها.

---

# Completed Features

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
✓ Email-compatible credentials
✓ Username-compatible credentials
✓ Phone-compatible credentials
✓ Custom identifier support
✓ No forced email identifier
✓ No forced username identifier
```

## Guard Support

```text
✓ GuardInterface
✓ AuthGuard
✓ Named Guard support
✓ Guard abstraction
✓ AuthGuard tests
✓ AuthManager Guard tests
```

## Authenticated User Support

```text
✓ Authenticatable Contract
✓ Typed user() return type
✓ Authenticatable|null support
✓ Authenticated user tests
✓ Unauthenticated user tests
```

## Authentication Exception Integration

```text
✓ AuthenticationException
✓ AuthManager exception handling
✓ AuthGuard exception handling
✓ Unexpected Throwable normalization
✓ Previous exception preservation
✓ Authentication exception tests
✓ Normal authentication failure remains false
```

## Service Container

```text
✓ AuthManagerInterface binding
✓ AuthManager implementation binding
✓ Singleton registration
✓ Service Provider tests
```

## Testing

```text
✓ PHPUnit
✓ Laravel Testbench
✓ Mockery
✓ AuthManager tests
✓ AuthGuard tests
✓ Contract tests
✓ Service Provider tests
✓ User state tests
✓ Authentication exception tests
✓ Previous exception tests
```

## Documentation

```text
✓ AuthManager documentation
✓ Generalized Login documentation
✓ Guard Support documentation
✓ Authenticated User documentation
✓ Authentication Exception documentation
✓ Exception normalization documentation
✓ Architecture documentation
✓ Testing documentation
✓ Service Container documentation
```

---

# Current Status

المرحلة الحالية من `Authentication Manager` مكتملة من ناحية الكود والاختبارات ضمن النطاق الحالي.

الحالة:

```text
AuthManagerInterface       ✓
AuthManager                ✓
GuardInterface             ✓
AuthGuard                  ✓
AuthenticationException    ✓
Service Container Binding  ✓
Singleton Binding          ✓
Dependency Injection       ✓
Generalized Login API      ✓
Named Guard Support        ✓
Typed User Contract        ✓
Authenticatable|null       ✓
Exception Normalization    ✓
Previous Exception         ✓
Laravel Testbench          ✓
Unit Tests                 ✓
PHPDoc                     ✓
Documentation              ✓
```

نتيجة الاختبارات الأخيرة:

```text
29 tests
34 assertions
OK
```

---

# Git Development Workflow

تم تقسيم التطوير إلى Feature Branches مستقلة.

النمط المستخدم:

```text
develop
    │
    ├── feature/generalize-auth-login
    │
    ├── feature/auth-manager-guard-support
    │
    ├── feature/auth-manager-user
    │
    └── feature/authentication-exception-integration
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

بعد دمج Feature والتأكد من استقرارها يمكن حذف Feature Branch المنتهي.

يجب عدم الاعتماد على قائمة Branches ثابتة داخل التوثيق كمصدر للحالة الحالية؛ يتم استخدام Git نفسه لمعرفة الفروع الموجودة.

---

# Git History

تم تقسيم العمل إلى Features وCommits صغيرة لتسهيل:

* مراجعة التغييرات
* تتبع التطور
* اكتشاف المشاكل
* مراجعة Pull Requests
* دمج الميزات بشكل مستقل

تم تنفيذ مراحل مثل:

```text
Authentication Manager
Generalized Login
Guard Support
Authenticated User Support
Authentication Exception Integration
```

ولمراجعة التاريخ الفعلي للمشروع يمكن استخدام:

```bash
git log --oneline
```

---

# Verification

قبل اعتبار المرحلة مستقرة يجب تشغيل:

```bash
git diff --check
```

ثم:

```bash
vendor/bin/phpunit
```

ويجب أن تكون جميع الاختبارات ناجحة.

الحالة الحالية:

```text
29 tests
34 assertions
OK
```

كما يمكن التحقق من حالة Git باستخدام:

```bash
git status
```

ويجب التأكد من عدم وجود تغييرات غير مقصودة قبل تنفيذ Commit أو Pull Request.

ولمراجعة التغييرات الموجودة في staging يمكن استخدام:

```bash
git diff --cached
```

ولفحص whitespace داخل التغييرات staged:

```bash
git diff --cached --check
```

---

# Current Development Phase

العمل الحالي يتم على:

```text
feature/authentication-exception-integration
```

تم في هذه المرحلة دمج Authentication Exception handling داخل طبقات Authentication الحالية.

تم تحديث:

```text
AuthManagerInterface
AuthManager
AuthGuard
AuthGuardTest
AuthManagerTest
```

كما تم إضافة:

```text
AuthenticationException
```

وأصبح `login()` يدعم السلوك التالي:

```text
Authentication succeeds
        ↓
true
```

أو:

```text
Authentication fails normally
        ↓
false
```

أو:

```text
Unexpected exception
        ↓
AuthenticationException
        ↓
previous exception preserved
```

الاختبارات الحالية ناجحة:

```text
29 tests
34 assertions
OK
```

---

# Development Methodology

سيتم تطوير المراحل القادمة وفق المنهجية التالية:

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
Static / Quality Checks
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

ويجب عدم إضافة abstraction أو feature جديدة بدون تحديد مسؤوليتها وعلاقتها بالمعمارية الحالية.

---

# Development Principles

تعتمد الحزمة على المبادئ التالية:

## 1. Contract First

يتم تعريف Contract قبل Implementation عندما يكون ذلك مناسبًا للتصميم.

## 2. Test First / Test Driven Where Practical

يتم تحديد السلوك المتوقع واختباره قبل أو بالتزامن مع تنفيذ الميزة.

## 3. Loose Coupling

تقليل الاعتماد المباشر على implementations قدر الإمكان.

## 4. Dependency Injection

استخدام Laravel Container وConstructor Injection لإدارة dependencies.

## 5. Type Safety

استخدام أنواع واضحة بدل `mixed` عندما يكون النوع معروفًا.

مثال:

```php
?Authenticatable
```

بدل:

```php
mixed
```

## 6. Small Features

تقسيم العمل إلى Features صغيرة يمكن اختبارها ومراجعتها ودمجها بشكل مستقل.

## 7. Backward Compatibility

يجب مراعاة عدم كسر API الحالي عند إضافة Features جديدة، إلا عند وجود قرار معماري واضح ومبرر.

## 8. Responsibility Separation

كل طبقة يجب أن تمتلك مسؤولية واضحة.

## 9. Testability

يجب تصميم المكونات بحيث يمكن اختبارها دون الاعتماد غير الضروري على Database أو Authentication environment حقيقي.

## 10. Documentation

كل Feature مهمة يجب أن يكون لها توثيق يعكس السلوك الفعلي للكود.

## 11. Stable Develop

يجب أن يبقى `develop` في حالة قابلة للاختبار بعد دمج الميزات.

## 12. Exception Boundary

يجب أن توفر الحزمة exception boundary واضحة بين Laravel/infrastructure وبين application، مع الحفاظ على السبب الأصلي للخطأ عند الحاجة إلى debugging.

---

# Summary

تم بناء أساس قابل للتوسع لطبقة Authentication داخل `core-auth`.

أصبح لدينا:

```text
                    Application
                         │
                         ▼
               AuthManagerInterface
                         │
                         ▼
                    AuthManager
                    /         \
                   /           \
                  ▼             ▼
          Default Guard     Named Guard
                │                │
                ▼                ▼
        Laravel Guard       AuthGuard
                │                │
                │                ▼
                │         GuardInterface
                │
                ▼
        AuthenticationException
```

تم تعميم `login()` بحيث لا يفرض نوعًا محددًا من الـ identifier.

يمكن للتطبيق استخدام:

```text
email
username
phone
employee_id
custom identifier
```

بحسب ما يدعمه Laravel Guard والتطبيق.

كما تمت إضافة:

```text
GuardInterface
AuthGuard
Named Guard Support
```

لتوفير abstraction خاص بالحزمة عند التعامل مع Guards.

كما تم تحسين typed authenticated user support باستخدام:

```php
Illuminate\Contracts\Auth\Authenticatable
```

بحيث أصبحت واجهة المستخدم:

```php
public function user(): ?Authenticatable;
```

وهذا يعني:

```text
Authenticated user → Authenticatable
No authenticated user → null
```

كما تمت إضافة:

```text
AuthenticationException
```

لتوحيد التعامل مع unexpected authentication errors.

أصبح behavior الخاص بـ `login()` واضحًا:

```text
Valid authentication
        ↓
true
```

```text
Invalid credentials
        ↓
false
```

```text
Unexpected authentication error
        ↓
AuthenticationException
        ↓
Original exception preserved
```

وهذا يسمح للتطبيق بالتمييز بوضوح بين فشل Authentication العادي وبين الأخطاء غير المتوقعة.

تم اختبار المكونات الأساسية باستخدام:

```text
PHPUnit
Laravel Testbench
Mockery
```

والنتيجة الحالية:

```text
29 tests
34 assertions
OK
```

أصبحت المرحلة الحالية أساسًا مناسبًا لبناء Features المصادقة المتقدمة مستقبلًا مع الحفاظ على:

```text
Contract-based Design
Loose Coupling
Dependency Injection
Type Safety
Testability
Exception Boundary
Laravel Compatibility
Extensibility
```

ويجب أن تعتمد المراحل القادمة على التصميم الحالي بدل إضافة abstractions متداخلة أو فرض نوع محدد من User أو Authentication Identifier على التطبيق.
