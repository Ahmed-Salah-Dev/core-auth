# Authentication Manager

## Phase

Authentication Manager and Service Container Binding

## Goal

إنشاء طبقة موحدة للتعامل مع نظام المصادقة في Laravel، مع فصل الحزمة عن التنفيذ المباشر لنظام Authentication.

## What Was Implemented

تم تنفيذ المكونات التالية:

* `AuthManagerInterface`
* `AuthManager`
* تسجيل `AuthManager` داخل Laravel Service Container
* استخدام Laravel `AuthFactory`
* استخدام Laravel Testbench للاختبارات
* إضافة اختبارات Unit وIntegration بسيطة

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
```

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

## AuthManager Responsibilities

### login()

يستقبل:

* identifier
* password

ويستخدم:

```php
$auth->guard()->attempt([
    'email' => $identifier,
    'password' => $password,
]);
```

ويرجع:

```text
true  → authentication succeeded
false → authentication failed
```

### logout()

يقوم بتسجيل خروج المستخدم الحالي باستخدام:

```php
$auth->guard()->logout();
```

### check()

يتحقق من وجود مستخدم authenticated:

```php
$auth->guard()->check();
```

### user()

يرجع المستخدم الحالي:

```php
$auth->guard()->user();
```

أو `null` في حالة عدم وجود مستخدم authenticated.

## Service Container Binding

تم تسجيل `AuthManagerInterface` كـ singleton داخل:

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

وبالتالي فإن:

```php
$app->make(AuthManagerInterface::class);
```

يعيد نفس instance في كل مرة.

## Testing

تم استخدام:

```text
PHPUnit 11.5.56
Laravel Testbench 10.11.0
PHP 8.2.12
```

آخر نتيجة ناجحة:

```text
9 tests
12 assertions
OK
```

## Testing Strategy

تم اختبار:

* تطبيق `AuthManagerInterface`
* فشل تسجيل الدخول
* حالة عدم تسجيل الدخول
* عدم وجود المستخدم
* تنفيذ logout
* تسجيل Service Provider
* تسجيل `AuthManager` داخل Container
* Singleton binding

## Dependencies

Runtime:

```json
"php": "^8.2",
"illuminate/support": "^12.0"
```

Development:

```json
"laravel/framework": "^12.0",
"phpunit/phpunit": "^11.5",
"orchestra/testbench": "^10.0"
```

## Important Design Decision

الحزمة تعتمد على Laravel Contracts بدل الاعتماد على implementation محدد.

يتم حقن:

```php
Illuminate\Contracts\Auth\Factory
```

داخل `AuthManager`.

هذا يجعل `AuthManager`:

* أسهل للاختبار
* أقل ارتباطًا بالتنفيذ الداخلي لـ Laravel
* أسهل في التطوير مستقبلًا
* مناسبًا كجزء من Laravel Package

## Git

تم تثبيت مرحلة Authentication Manager في Git بعد نجاح الاختبارات.

يجب أن تكون حالة المستودع نظيفة قبل بدء المرحلة التالية:

```bash
git status
```

والنتيجة المتوقعة:

```text
nothing to commit, working tree clean
```

## Next Phase

المرحلة التالية ستكون تطوير Authentication Flow بشكل تدريجي، مع التركيز على:

1. نجاح `login()`
2. التعامل مع identifier بشكل واضح
3. اختبار `logout()`
4. اختبار `check()`
5. اختبار `user()`
6. تحديد API النهائي للحزمة قبل إضافة ميزات إضافية.
