# API Token Management

## Overview

يوفر `CoreAuth` طبقة موحدة لإدارة API Tokens داخل تطبيقات Laravel، مع الاعتماد على Laravel Sanctum لتنفيذ آلية إنشاء وتخزين وإدارة الرموز.

تهدف هذه الميزة إلى توفير API واضحة وقابلة للتوسع لإدارة Tokens المرتبطة بالمستخدم، دون إعادة تنفيذ آلية إدارة التوكنات التي يوفرها Laravel Sanctum.

الميزة الحالية مسؤولة عن:

* إنشاء API Token.
* تحديد اسم الـ Token.
* تحديد Abilities الخاصة بالـ Token.
* تحديد تاريخ انتهاء الصلاحية.
* استرجاع Tokens الخاصة بالمستخدم.
* إلغاء Token محدد.
* إلغاء جميع Tokens الخاصة بالمستخدم.

أما **المصادقة الفعلية باستخدام Bearer Token وإعادة المستخدم المصادق عليه من الطلب** فهي ليست جزءًا من هذه الميزة، وسيتم التعامل معها في ميزة مستقلة لاحقًا.

---

## Architecture

تعتمد ميزة API Token Management على المكونات التالية:

```text
ApiTokenManagerInterface
        │
        ▼
ApiTokenManager
        │
        ├── Laravel Sanctum
        │       └── HasApiTokens
        │
        ▼
ApiTokenResult
        │
        ▼
Application
```

هذا التصميم يفصل العقدة العامة التي يتعامل معها التطبيق عن تفاصيل تنفيذ Laravel Sanctum.

---

## Requirements

تحتاج الميزة إلى Laravel Sanctum.

تمت إضافة الاعتماديات التالية إلى الحزمة:

```json
"laravel/sanctum": "^4.0",
"illuminate/database": "^12.0"
```

كما يجب أن يستخدم User model الخاص بالتطبيق trait:

```php
Laravel\Sanctum\HasApiTokens
```

مثال:

```php
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

final class User extends Model implements Authenticatable
{
    use AuthenticatableTrait;
    use HasApiTokens;
}
```

---

# ApiTokenManagerInterface

المسار:

```text
src/Contracts/ApiTokenManagerInterface.php
```

تمثل هذه الواجهة العقد العام لإدارة API Tokens.

## createToken()

```php
public function createToken(
    Authenticatable $user,
    string $name,
    array $abilities = [],
    ?DateTimeInterface $expiresAt = null
): ApiTokenResult;
```

تستخدم لإنشاء Token جديد للمستخدم.

### Parameters

| Parameter    | Type                      | Description                              |
| ------------ | ------------------------- | ---------------------------------------- |
| `$user`      | `Authenticatable`         | المستخدم الذي سيملك الـ Token            |
| `$name`      | `string`                  | اسم الـ Token                            |
| `$abilities` | `array`                   | الصلاحيات أو Abilities الخاصة بالـ Token |
| `$expiresAt` | `DateTimeInterface\|null` | تاريخ انتهاء الصلاحية                    |

### Return

ترجع العملية:

```php
ApiTokenResult
```

مثال:

```php
$result = $apiTokens->createToken(
    user: $user,
    name: 'mobile-app',
    abilities: ['orders:read', 'orders:create'],
);
```

يمكن الوصول إلى الـ Token الناتج من:

```php
$result->token
```

---

## tokens()

```php
public function tokens(
    Authenticatable $user
): Collection;
```

تستخدم لاسترجاع جميع API Tokens المرتبطة بالمستخدم.

مثال:

```php
$tokens = $apiTokens->tokens($user);
```

ترجع:

```php
Collection
```

ولا تعيد الـ plain-text Token الذي تم إنشاؤه سابقًا؛ إدارة البيانات المخزنة تتم من خلال Laravel Sanctum.

---

## revoke()

```php
public function revoke(
    Authenticatable $user,
    int|string $tokenId
): void;
```

تستخدم لإلغاء Token محدد.

مثال:

```php
$apiTokens->revoke(
    user: $user,
    tokenId: $tokenId,
);
```

يتم التأكد من أن الـ Token تابع للمستخدم المحدد قبل حذفه.

وهذا يمنع المستخدم من إلغاء Token يخص مستخدمًا آخر.

---

## revokeAll()

```php
public function revokeAll(
    Authenticatable $user
): void;
```

تستخدم لإلغاء جميع Tokens الخاصة بالمستخدم.

مثال:

```php
$apiTokens->revokeAll($user);
```

---

# ApiTokenResult

المسار:

```text
src/Data/ApiTokenResult.php
```

يمثل نتيجة إنشاء API Token.

```php
final readonly class ApiTokenResult
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $token,
        public ?DateTimeInterface $expiresAt = null,
    ) {
    }
}
```

## Properties

| Property    | Type                      | Description                             |
| ----------- | ------------------------- | --------------------------------------- |
| `id`        | `int\|string`             | معرف Token                              |
| `name`      | `string`                  | اسم Token                               |
| `token`     | `string`                  | الـ plain-text Token الناتج عند الإنشاء |
| `expiresAt` | `DateTimeInterface\|null` | تاريخ انتهاء الصلاحية                   |

الـ DTO معرف كـ `readonly` لمنع تعديل بيانات النتيجة بعد إنشائها.

---

# ApiTokenException

المسار:

```text
src/Exceptions/ApiTokenException.php
```

يستخدم للتعامل مع الأخطاء الخاصة بإدارة API Tokens.

```php
final class ApiTokenException extends CoreAuthException
{
}
```

ومن أهم الحالات التي يتم التعامل معها أن المستخدم المرسل إلى `ApiTokenManager` لا يستخدم:

```php
HasApiTokens
```

في هذه الحالة يتم إلقاء:

```php
ApiTokenException
```

برسالة توضح أن User model يجب أن يدعم Laravel Sanctum API Tokens.

---

# ApiTokenManager

المسار:

```text
src/Services/ApiTokenManager.php
```

هذه هي طبقة التنفيذ الفعلية للواجهة:

```php
ApiTokenManagerInterface
```

ويتم الاعتماد على Laravel Sanctum لتنفيذ عمليات Tokens.

---

## Token Support Validation

قبل تنفيذ عمليات إدارة الـ Tokens، يتحقق `ApiTokenManager` من أن المستخدم يستخدم:

```php
HasApiTokens
```

ويتم ذلك من خلال:

```php
class_uses_recursive($user)
```

إذا لم يكن الدعم موجودًا، يتم إلقاء:

```php
ApiTokenException
```

وهذا يجعل الخطأ واضحًا بدلًا من السماح بحدوث خطأ غير واضح أثناء تنفيذ العملية.

---

# Creating Tokens

عند إنشاء Token:

```php
$result = $apiTokens->createToken(
    user: $user,
    name: 'mobile-app',
);
```

يمكن استخدام النتيجة:

```php
$result->id;
$result->name;
$result->token;
$result->expiresAt;
```

### Abilities

يمكن تحديد الصلاحيات:

```php
$result = $apiTokens->createToken(
    user: $user,
    name: 'mobile-app',
    abilities: [
        'users:read',
        'orders:read',
    ],
);
```

تحدد الـ Abilities نطاق العمليات التي يمكن ربطها بالـ Token.

---

# Token Expiration

يمكن تحديد تاريخ انتهاء Token:

```php
$expiresAt = now()->addDays(30);

$result = $apiTokens->createToken(
    user: $user,
    name: 'mobile-app',
    expiresAt: $expiresAt,
);
```

وإذا لم يتم تحديد تاريخ:

```php
$expiresAt = null;
```

فإن `CoreAuth` يمرر القيمة إلى Laravel Sanctum دون فرض سياسة انتهاء صلاحية خاصة به.

وهذا القرار يحافظ على مسؤولية Sanctum في تنفيذ آلية Token expiration بدلًا من إعادة تنفيذها داخل الحزمة.

---

# Retrieving User Tokens

يمكن الحصول على Tokens الخاصة بالمستخدم:

```php
$tokens = $apiTokens->tokens($user);
```

مثال على الاستخدام:

```php
foreach ($tokens as $token) {
    // Token information
}
```

هذه العملية تعتمد على علاقة Tokens التي يوفرها Laravel Sanctum من خلال:

```php
HasApiTokens
```

---

# Revoking a Token

لإلغاء Token محدد:

```php
$apiTokens->revoke(
    user: $user,
    tokenId: $tokenId,
);
```

يتم تنفيذ العملية على Tokens الخاصة بالمستخدم نفسه:

```php
$user->tokens()
    ->whereKey($tokenId)
    ->delete();
```

وبالتالي لا يتم حذف Token لمستخدم آخر حتى إذا تم تمرير معرفه.

---

# Revoking All Tokens

لإلغاء جميع Tokens:

```php
$apiTokens->revokeAll($user);
```

يتم حذف جميع Tokens المرتبطة بالمستخدم.

هذا مفيد مثلًا في حالات:

* تسجيل الخروج من جميع الأجهزة.
* إلغاء جلسات API القديمة.
* تغيير بيانات اعتماد حساسة.
* إبطال جميع Tokens قبل إصدار Tokens جديدة.

---

# Service Container Registration

يتم تسجيل `ApiTokenManager` داخل:

```text
src/CoreAuthServiceProvider.php
```

باستخدام:

```php
$this->app->singleton(
    ApiTokenManagerInterface::class,
    ApiTokenManager::class
);
```

وبالتالي يمكن للتطبيق الاعتماد على الواجهة:

```php
ApiTokenManagerInterface
```

بدلًا من الاعتماد المباشر على:

```php
ApiTokenManager
```

مثال:

```php
use AhmedSalahDev\CoreAuth\Contracts\ApiTokenManagerInterface;

public function __construct(
    private readonly ApiTokenManagerInterface $apiTokens
) {
}
```

هذا يحافظ على Dependency Inversion ويسمح بتغيير التنفيذ مستقبلًا دون تغيير المستهلكين.

---

# Scope of Responsibility

مسؤولية هذه الميزة هي:

```text
Token Creation
Token Listing
Token Revocation
Token Expiration Configuration
Token Abilities
```

ولا تشمل حاليًا:

```text
Bearer Token Authentication
Request Authentication
Authenticated User Resolution
API Authentication Middleware
Authorization Policies
```

سيتم بناء التكامل الخاص بالمصادقة باستخدام API Token في مرحلة مستقلة.

---

# Testing

تمت إضافة اختبارات Unit وIntegration للميزة.

## Unit Tests

تتحقق اختبارات Unit من:

* تطبيق `ApiTokenManagerInterface`.
* وجود العمليات العامة المطلوبة.
* رفض User غير المدعوم بـ `HasApiTokens`.
* عمل `ApiTokenException`.
* تسجيل `ApiTokenManager` في Service Container.

## Integration Tests

تتحقق اختبارات Integration من:

* إنشاء Token.
* حفظ Token في قاعدة البيانات.
* حفظ Abilities.
* حفظ تاريخ انتهاء الصلاحية.
* استرجاع Tokens.
* إلغاء Token محدد.
* إلغاء جميع Tokens.
* عدم إمكانية إلغاء Token تابع لمستخدم آخر.

---

# Current API Token Flow

التدفق الحالي للميزة:

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
HasApiTokens
    │
    ▼
Laravel Sanctum
    │
    ▼
Personal Access Token
```

أما التدفق الكامل المستهدف للمصادقة عبر API فسيصبح لاحقًا:

```text
HTTP Request
     │
     ▼
Bearer Token
     │
     ▼
API Token Authentication
     │
     ▼
Authenticated User
     │
     ▼
AuthorizationManager
     │
     ▼
Gate / Policy
```

وهذا هو السبب في فصل **Token Management** عن **Token Authentication**؛ فإدارة الـ Token وإنشاءه وإلغاؤه مسؤولية مختلفة عن استخدامه للمصادقة على HTTP Request.

---

# Summary

أضاف `CoreAuth` طبقة متخصصة لإدارة API Tokens مع الاستفادة من Laravel Sanctum بدل إعادة بناء نظام Token مستقل.

المكونات الرئيسية:

```text
ApiTokenManagerInterface
ApiTokenManager
ApiTokenResult
ApiTokenException
```

وتوفر الميزة حاليًا:

* إنشاء Tokens.
* Abilities.
* Expiration.
* استرجاع Tokens.
* إلغاء Token محدد.
* إلغاء جميع Tokens.
* التكامل مع Service Container.
* اختبارات Unit وIntegration.

الخطوة التالية بعد اكتمال توثيق هذه الميزة هي إضافة **API Token Authentication / Guard Integration** لإكمال دورة المصادقة باستخدام Bearer Tokens.
