# Authentication Manager

## Phase

Authentication Manager and Service Container Binding

## Goal

إنشاء طبقة موحدة للتعامل مع نظام المصادقة في Laravel، مع فصل الحزمة عن التنفيذ المباشر لنظام Authentication.

الهدف من هذه المرحلة هو بناء أساس واضح وقابل للتوسع لطبقة المصادقة داخل الحزمة، مع الاعتماد على Laravel Contracts وإمكانية اختبار المكونات بشكل مستقل.

---

## What Was Implemented

تم تنفيذ المكونات التالية:

* `AuthManagerInterface`
* `AuthManager`
* تسجيل `AuthManager` داخل Laravel Service Container
* تسجيل `AuthManager` باستخدام `singleton`
* استخدام Laravel `AuthFactory`
* استخدام Laravel Testbench للاختبارات
* إضافة اختبارات Unit وIntegration بسيطة
* إضافة PHPDoc للدوال الأساسية في `AuthManager`
* فصل Contract عن Implementation

---

## Project Structure

```text
src/
├── Contracts/
│   └── AuthManagerInterface.php
├── Services/
│   └── AuthManager.php
└── CoreAuthServiceProvider.php

tests/
└── Unit/
    ├── AuthManagerContractTest.php
    ├── AuthManagerTest.php
    └── CoreAuthServiceProviderTest.php

docs/
└── 04-auth-manager.md
```

---

## Architecture

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
Laravel AuthFactory
     │
     ▼
Laravel Guard
```

هذا التصميم يسمح للحزمة بالتعامل مع Laravel Authentication من خلال Contract بدل ربط منطق الحزمة مباشرة بتنفيذ محدد.

---

# AuthManagerInterface

يوفر `AuthManagerInterface` العقد الأساسي الذي يجب أن يلتزم به مدير المصادقة.

المسؤوليات الحالية هي:

```php
public function login(
    string $identifier,
    string $password
): bool;

public function logout(): void;

public function check(): bool;

public function user(): mixed;
```

### Methods

#### `login()`

مسؤولة عن محاولة تسجيل دخول المستخدم.

تستقبل:

* `identifier`
* `password`

وتعيد:

```text
true  → authentication succeeded
false → authentication failed
```

---

#### `logout()`

مسؤولة عن تسجيل خروج المستخدم الحالي.

---

#### `check()`

تتحقق مما إذا كان هناك مستخدم authenticated حاليًا.

تعيد:

```text
true  → user is authenticated
false → user is not authenticated
```

---

#### `user()`

ترجع المستخدم authenticated الحالي.

إذا لم يوجد مستخدم authenticated فإنها ترجع:

```text
null
```

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

ويعتمد على:

```php
Illuminate\Contracts\Auth\Factory
```

يتم حقن `AuthFactory` عن طريق Constructor Injection.

---

## Constructor

```php
public function __construct(
    private readonly AuthFactory $auth
) {
}
```

يستقبل Laravel `AuthFactory` ويحتفظ به لاستخدامه في عمليات Authentication.

استخدام Dependency Injection يجعل الكلاس:

* أسهل للاختبار
* أقل ارتباطًا بالتنفيذ الداخلي
* أكثر قابلية لإعادة الاستخدام
* أسهل في التطوير مستقبلًا

---

# AuthManager Responsibilities

## login()

تقوم بمحاولة تسجيل الدخول باستخدام Laravel Guard.

التنفيذ الحالي:

```php
return $this->auth
    ->guard()
    ->attempt([
        'email' => $identifier,
        'password' => $password,
    ]);
```

في الإصدار الحالي يتم التعامل مع `identifier` على أنه **email**.

### Parameters

```text
identifier
password
```

### Return

```text
true  → authentication succeeded
false → authentication failed
```

---

## logout()

تقوم بتسجيل خروج المستخدم الحالي باستخدام Laravel Guard:

```php
$this->auth
    ->guard()
    ->logout();
```

ولا تقوم بإرجاع قيمة:

```text
void
```

---

## check()

تتحقق من حالة Authentication الحالية:

```php
$this->auth
    ->guard()
    ->check();
```

النتيجة:

```text
true  → user is authenticated
false → user is not authenticated
```

---

## user()

تقوم بإرجاع المستخدم الحالي:

```php
$this->auth
    ->guard()
    ->user();
```

إذا لم يكن هناك مستخدم authenticated فإن Laravel Guard يعيد:

```text
null
```

---

# Code Documentation

تمت إضافة PHPDoc إلى الدوال الأساسية في `AuthManager` لتوضيح مسؤولية كل دالة وسلوكها.

يشمل التوثيق:

* `__construct()`
* `login()`
* `logout()`
* `check()`
* `user()`

ويتضمن PHPDoc معلومات عن:

* وظيفة الدالة
* المدخلات
* نوع القيمة المرجعة
* السلوك المتوقع

الهدف من ذلك هو جعل الكود أسهل للفهم والصيانة، خصوصًا عند استخدام الحزمة داخل مشاريع Laravel أخرى.

---

# Service Container Binding

تم تسجيل:

```php
AuthManagerInterface
```

داخل Laravel Service Container في:

```text
src/CoreAuthServiceProvider.php
```

باستخدام:

```php
$this->app->singleton(
    AuthManagerInterface::class,
    AuthManager::class
);
```

وبالتالي يمكن الحصول على مدير المصادقة من الـ Container باستخدام:

```php
$app->make(AuthManagerInterface::class);
```

وسيتم إرجاع نفس الـ instance في كل مرة بسبب استخدام:

```php
singleton
```

---

# Service Provider

المسؤول عن تسجيل خدمات الحزمة هو:

```text
CoreAuthServiceProvider
```

ويقوم حاليًا بتسجيل:

```text
AuthManagerInterface
        ↓
AuthManager
```

---

# Testing

تم استخدام:

```text
PHPUnit 11.5.56
Laravel Testbench 10.11.0
PHP 8.2.12
```

يتم تشغيل الاختبارات باستخدام:

```bash
vendor/bin/phpunit
```

آخر نتيجة ناجحة:

```text
12 tests
15 assertions
OK
```

---

# Testing Strategy

تم اختبار المكونات التالية:

### AuthManager

* تطبيق `AuthManagerInterface`
* نجاح تسجيل الدخول
* فشل تسجيل الدخول
* استخدام `identifier` كـ email
* إرسال بيانات الاعتماد الصحيحة إلى Guard
* رفض Guard لبيانات الدخول
* حالة عدم تسجيل الدخول
* حالة المستخدم authenticated
* عدم وجود المستخدم
* إرجاع المستخدم authenticated
* تنفيذ logout

### CoreAuthServiceProvider

* تسجيل Service Provider
* تسجيل `AuthManager` داخل Container
* التأكد من أن `AuthManager` يتم تسجيله كـ Singleton

---

# Mocking Strategy

تم استخدام:

```php
Mockery
```

لعزل `AuthManager` عن Laravel Authentication أثناء اختبارات Unit.

يتم عمل Mock لـ:

```php
Illuminate\Contracts\Auth\Factory
```

ثم يتم إنشاء Mock للـ Guard عند الحاجة.

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

هذا يسمح بالتأكد من أن `AuthManager` يستدعي Laravel Authentication بالطريقة الصحيحة دون الحاجة إلى قاعدة بيانات أو مستخدم حقيقي.

---

# Dependencies

## Runtime

```json
"php": "^8.2",
"illuminate/support": "^12.0"
```

## Development

```json
"laravel/framework": "^12.0",
"phpunit/phpunit": "^11.5",
"orchestra/testbench": "^10.0"
```

---

# Important Design Decisions

## 1. استخدام Laravel Contracts

الحزمة تعتمد على Laravel Contracts بدل الاعتماد المباشر على Implementation محدد.

يتم حقن:

```php
Illuminate\Contracts\Auth\Factory
```

داخل:

```php
AuthManager
```

وهذا يجعل الكلاس:

* أسهل للاختبار
* أقل ارتباطًا بالتنفيذ الداخلي لـ Laravel
* أسهل في التطوير مستقبلًا
* مناسبًا كجزء من Laravel Package

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

بحيث يعتمد التطبيق على:

```text
Contract
```

بدل الاعتماد مباشرة على:

```text
Implementation
```

وهذا يسهل تغيير التنفيذ مستقبلًا دون تغيير الكود الذي يعتمد على الـ Contract.

---

## 3. Singleton Binding

تم استخدام:

```php
$this->app->singleton()
```

بدل:

```php
$this->app->bind()
```

لأن `AuthManager` في المرحلة الحالية يمثل خدمة واحدة مشتركة داخل دورة حياة Laravel Application.

---

# Current Authentication Flow

التدفق الحالي للمصادقة:

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
Guard
     │
     ├── attempt()
     ├── logout()
     ├── check()
     └── user()
```

---

# Current Limitations

هذه المرحلة تمثل الأساس الأولي لطبقة Authentication، ولذلك لا تزال بعض الأمور غير مطبقة.

حاليًا:

* `identifier` يتم التعامل معه كـ email.
* لا يوجد دعم لتغيير نوع الـ identifier.
* لا توجد معالجة مخصصة لأخطاء Authentication.
* لا توجد Exceptions مخصصة للحزمة.
* لا توجد Events خاصة بالمصادقة.
* لا توجد API خاصة بالـ Authentication.
* لا يوجد Token Authentication داخل الحزمة.
* لا توجد Guards مخصصة.

هذه الأمور لن تتم إضافتها إلا بعد تحديد التصميم النهائي للحزمة.

---

# Git

تم تقسيم العمل إلى commits صغيرة وواضحة لتسهيل تتبع تطور الحزمة.

من الـ commits المهمة في هذه المرحلة:

```text
451be57 test: integrate Laravel Testbench
41fbd34 chore: stop tracking IDE files
7c291b2 refactor: optimize package dependencies
d8adbf4 feat: add authentication manager container binding
34d9680 feat: implement authentication manager
6b05e30 docs: document authentication manager
```

يجب التأكد من أن المستودع نظيف قبل الانتقال إلى المرحلة التالية:

```bash
git status
```

والنتيجة المتوقعة:

```text
nothing to commit, working tree clean
```

---

# Current Status

تم الانتهاء من الأساس الأولي لـ Authentication Manager.

الحالة الحالية:

```text
AuthManagerInterface       ✓
AuthManager                ✓
Service Container Binding  ✓
Singleton Binding          ✓
Laravel Testbench          ✓
Unit Tests                 ✓
PHPDoc                     ✓
Documentation              ✓
```

آخر اختبار ناجح:

```text
12 tests
15 assertions
OK
```

---

# Next Phase

المرحلة التالية ستكون تطوير Authentication Flow بشكل تدريجي.

قبل إضافة ميزات كبيرة، سيتم تحديد الـ API النهائي للحزمة، ثم بناء المكونات خطوة بخطوة.

الأولوية ستكون:

1. تحديد شكل `identifier` النهائي.
2. تحديد طريقة التعامل مع Authentication failures.
3. تحديد Exceptions الخاصة بالحزمة عند الحاجة.
4. تحديد ما إذا كانت الحزمة ستدعم أكثر من Guard.
5. تحديد API النهائي لـ Authentication Manager.
6. كتابة الاختبارات قبل تنفيذ السلوك الجديد.
7. تحديث التوثيق مع كل مرحلة.
8. تثبيت كل مرحلة مستقرة في Git.

القاعدة المتبعة في تطوير الحزمة:

```text
Design
   ↓
Test
   ↓
Implementation
   ↓
Documentation
   ↓
PHPUnit
   ↓
Git Commit
```
