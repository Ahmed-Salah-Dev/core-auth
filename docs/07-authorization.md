# Core Authorization

## 1. Overview

تمت إضافة Authorization إلى `core-auth` كمسؤولية مستقلة عن Authentication.

الهدف من هذه الطبقة هو توفير abstraction واضحة فوق Laravel Authorization / Gate، بحيث تستطيع التطبيقات التي تستخدم الحزمة التعامل مع authorization من خلال Contract ثابت، مع إبقاء تفاصيل تنفيذ Laravel خلف هذه الطبقة.

يعتمد التصميم على:

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
```

وبذلك تكون الحزمة مسؤولة عن توفير API مستقرة للتطبيق، بينما تبقى آلية authorization الفعلية مسؤولية Laravel.

التصميم لا يعيد تنفيذ Laravel Gate، وإنما يوفر طبقة منظمة وقابلة للاختبار فوقها.

---

# 2. Goals

الـAuthorization layer صممت لتحقيق الأهداف التالية:

```text
✓ Authorization abstraction
✓ Contract-first design
✓ Laravel Gate integration
✓ Ability checks
✓ Negative ability checks
✓ Authorization enforcement
✓ Advanced ability checks
✓ Authorization inspection
✓ User context support
✓ Dependency Injection
✓ Exception boundary
✓ Container integration
✓ Singleton binding
✓ Unit testing
✓ Contract testing
✓ Laravel Testbench integration
```

---

# 3. Authorization Responsibility

الـAuthorization Manager مسؤول عن توفير API موحدة للتعامل مع Laravel Gate.

المسؤوليات الحالية تشمل:

```text
AuthorizationManager
│
├── allows()
├── denies()
├── authorize()
├── check()
├── any()
├── none()
├── inspect()
└── forUser()
```

بينما تبقى تفاصيل authorization الفعلية داخل Laravel.

---

# 4. Contract

الواجهة الرئيسية هي:

```text
src/Contracts/AuthorizationManagerInterface.php
```

وتحدد الـPublic API الخاصة بالـAuthorization Manager.

الفكرة الأساسية هي أن التطبيق يعتمد على:

```php
AuthorizationManagerInterface
```

بدل الاعتماد مباشرة على:

```php
AuthorizationManager
```

وهذا يحافظ على:

```text
Loose Coupling
Dependency Inversion
Testability
Replaceability
API Stability
```

---

# 5. AuthorizationManagerInterface

الـContract يوفر مجموعة من عمليات authorization.

## 5.1 allows()

```php
public function allows(
    mixed $ability,
    mixed $arguments = []
): bool;
```

تستخدم للتحقق مما إذا كان المستخدم الحالي مسموحًا له بتنفيذ Ability معينة.

مثال:

```php
$authorizationManager->allows(
    'update'
);
```

يمكن أيضًا تمرير arguments:

```php
$authorizationManager->allows(
    'update',
    $post
);
```

النتيجة:

```text
true
```

إذا كان الـAbility مسموحًا.

أو:

```text
false
```

إذا لم يكن مسموحًا.

الـManager لا ينفذ authorization بنفسه، وإنما يمرر الطلب إلى Laravel Gate.

---

# 6. denies()

```php
public function denies(
    mixed $ability,
    mixed $arguments = []
): bool;
```

تستخدم للتحقق مما إذا كان المستخدم ممنوعًا من تنفيذ Ability معينة.

مثال:

```php
$authorizationManager->denies(
    'delete'
);
```

النتيجة:

```text
true
```

إذا كان الـAbility مرفوضًا.

أو:

```text
false
```

إذا كان مسموحًا.

ويتم تفويض العملية إلى Laravel Gate.

---

# 7. authorize()

```php
public function authorize(
    mixed $ability,
    mixed $arguments = []
): void;
```

تستخدم عندما يكون المطلوب ليس مجرد معرفة النتيجة، وإنما فرض authorization.

مثال:

```php
$authorizationManager->authorize(
    'update',
    $post
);
```

إذا نجح authorization تستمر العملية بشكل طبيعي.

أما إذا فشل authorization أو حدث خطأ أثناء تنفيذ Gate، يتم التعامل مع الاستثناء من خلال `AuthorizationException`.

---

# 8. Authorization Exception Boundary

يستخدم `AuthorizationManager` حدودًا واضحة للاستثناءات حول عملية:

```php
authorize()
```

التنفيذ يعتمد على:

```php
try {
    $this->gate->authorize(
        $ability,
        $arguments
    );
} catch (Throwable $exception) {
    throw new AuthorizationException(
        $exception->getMessage(),
        (int) $exception->getCode(),
        $exception
    );
}
```

وبذلك يتم تحويل الأخطاء القادمة من طبقة authorization إلى:

```text
AuthorizationException
```

مع الاحتفاظ بالاستثناء الأصلي كـprevious exception.

هذا يوفر للتطبيق نقطة استثناء موحدة على مستوى الحزمة.

---

# 9. AuthorizationException

الملف:

```text
src/Exceptions/AuthorizationException.php
```

يمثل الاستثناء الخاص بطبقة Authorization.

وهو جزء من exception hierarchy الخاصة بالحزمة:

```text
CoreAuthException
       │
       └── AuthorizationException
```

الهدف منه هو منع تسرب تفاصيل الاستثناءات الداخلية إلى التطبيق، وتوفير exception type واضح مرتبط بمسؤولية Authorization.

---

# 10. check()

تمت إضافة:

```php
public function check(
    mixed $ability,
    mixed $arguments = []
): bool;
```

وتستخدم للتحقق من إمكانية تنفيذ Ability.

مثال:

```php
$authorizationManager->check(
    'update',
    $post
);
```

تعيد:

```text
true
```

أو:

```text
false
```

ويتم تفويض العملية إلى Laravel Gate.

---

# 11. any()

```php
public function any(
    mixed $abilities,
    mixed $arguments = []
): bool;
```

تستخدم للتحقق من السماح بأي Ability من مجموعة من الـAbilities.

مثال:

```php
$authorizationManager->any(
    [
        'update',
        'delete',
    ],
    $post
);
```

إذا تم السماح بأي Ability مناسبة، تعيد العملية:

```text
true
```

وإلا:

```text
false
```

الـManager لا يعيد تنفيذ منطق Laravel، وإنما delegates العملية مباشرة إلى Gate.

---

# 12. none()

```php
public function none(
    mixed $abilities,
    mixed $arguments = []
): bool;
```

تستخدم للتحقق من عدم السماح بأي Ability من مجموعة معينة.

مثال:

```php
$authorizationManager->none(
    [
        'update',
        'delete',
    ],
    $post
);
```

وتعيد:

```text
true
```

عندما لا يكون أي من الـAbilities مسموحًا.

---

# 13. inspect()

```php
public function inspect(
    mixed $ability,
    mixed $arguments = []
): Response;
```

تستخدم للحصول على نتيجة authorization التفصيلية من Laravel.

تعتمد على:

```php
Illuminate\Auth\Access\Response
```

مثال:

```php
$response = $authorizationManager->inspect(
    'update',
    $post
);
```

بدل الاكتفاء بـ:

```text
true / false
```

يمكن التعامل مع Authorization Response التي يوفرها Laravel.

وهذا يسمح بالاحتفاظ بالمعلومات المرتبطة بنتيجة authorization بدل تحويلها مباشرة إلى Boolean.

---

# 14. User Context

يدعم Authorization Manager العمل باستخدام user context محدد من خلال:

```php
forUser()
```

التوقيع:

```php
public function forUser(
    mixed $user
): static;
```

مثال:

```php
$userAuthorization = $authorizationManager->forUser(
    $user
);
```

بعد ذلك يمكن استخدام الـManager الناتج لإجراء authorization باستخدام المستخدم المحدد.

التنفيذ يعتمد على:

```php
$this->gate->forUser($user)
```

وبذلك تبقى عملية تحديد user context مسؤولية Laravel Gate.

---

# 15. Why forUser() Exists

بدون `forUser()` يكون authorization مرتبطًا بالسياق الحالي المستخدم بواسطة Laravel.

لكن بعض التطبيقات تحتاج إلى فحص authorization لمستخدم محدد.

لذلك يوفر:

```php
forUser()
```

طريقة واضحة لإنشاء Authorization Manager مرتبط بسياق مستخدم معين.

التدفق:

```text
Application
      │
      ▼
AuthorizationManager
      │
      ▼
forUser($user)
      │
      ▼
Laravel Gate::forUser()
      │
      ▼
Authorization Manager
      │
      ▼
Ability Checks
```

---

# 16. Ability and Arguments

تم تصميم API بحيث لا تكون الـAbilities مرتبطة بنوع واحد من identifiers.

يمكن استخدام:

```php
'update'
```

أو:

```php
'delete'
```

أو أي Ability تقوم application بتعريفها.

كما يمكن تمرير arguments مختلفة:

```php
$post
```

أو:

```php
[$post, $category]
```

أو غيرها بحسب الـGate / Policy المستخدمة.

مثال:

```php
$authorizationManager->allows(
    'update',
    $post
);
```

ويتم تمرير arguments إلى Laravel Gate بدون إعادة تفسيرها داخل الحزمة.

هذا يحافظ على مرونة Laravel Authorization.

---

# 17. Dependency Injection

`AuthorizationManager` يعتمد على Laravel Gate Contract:

```php
Illuminate\Contracts\Auth\Access\Gate
```

ويتم حقنه في constructor:

```php
public function __construct(
    private readonly Gate $gate
) {
}
```

هذا أفضل من استخدام Facade مباشرة داخل الـManager لأنه يوفر:

```text
Explicit Dependencies
Testability
Loose Coupling
Clear Architecture
```

كما يسمح باستخدام Mock أثناء Unit Testing.

---

# 18. Why Gate Is Injected

بدل كتابة:

```php
Gate::allows(...)
```

داخل كل method، يعتمد الـManager على:

```php
Gate
```

المحقون في constructor.

وبذلك تصبح العلاقة:

```text
AuthorizationManager
        │
        ▼
Gate Contract
        │
        ▼
Laravel Authorization
```

بدل:

```text
AuthorizationManager
        │
        ▼
Static Facade
```

وهذا يجعل implementation أكثر قابلية للاختبار والتغيير.

---

# 19. Laravel Delegation

`core-auth` لا يعيد تنفيذ authorization logic.

المسؤوليات الأساسية تبقى لدى Laravel:

```text
Gate
Policies
Gates
Ability Resolution
User Context
Authorization Response
Authorization Rules
```

بينما `core-auth` يوفر abstraction layer.

بالتالي:

```text
Application
      │
      ▼
CoreAuth AuthorizationManager
      │
      ▼
Laravel Gate
      │
      ├── Gates
      ├── Policies
      └── Authorization Rules
```

هذا التصميم يمنع duplication لمنطق Laravel داخل الحزمة.

---

# 20. Separation of Responsibilities

تم الحفاظ على فصل واضح بين المسؤوليات.

## CoreAuth مسؤول عن:

```text
Contract
Manager API
Exception Boundary
Dependency Injection
Container Binding
Package-level abstraction
```

## Laravel مسؤول عن:

```text
Authorization Logic
Policies
Gates
Ability Resolution
User Context Resolution
Authorization Response
```

هذا الفصل مهم للحفاظ على الحزمة صغيرة وقابلة للتوسع.

---

# 21. Container Binding

يتم تسجيل:

```php
AuthorizationManagerInterface::class
```

في Service Container وربطه بـ:

```php
AuthorizationManager::class
```

التدفق:

```text
AuthorizationManagerInterface
          │
          ▼
AuthorizationManager
```

وبالتالي يستطيع التطبيق طلب الـContract من Laravel Container.

مثال:

```php
app(
    AuthorizationManagerInterface::class
);
```

أو من خلال Dependency Injection:

```php
public function __construct(
    AuthorizationManagerInterface $authorization
) {
    $this->authorization = $authorization;
}
```

---

# 22. Singleton Binding

يتم تسجيل Authorization Manager كـSingleton.

أي أن Laravel Container يعيد نفس instance عند طلب الـContract أكثر من مرة خلال نفس application container lifecycle.

تم اختبار ذلك في:

```text
CoreAuthServiceProviderTest
```

من خلال إنشاء instance أولى وثانية والتأكد من أنها نفس الـinstance.

---

# 23. Service Provider Integration

الملف المسؤول عن التسجيل:

```text
src/CoreAuthServiceProvider.php
```

يقوم بربط:

```text
AuthorizationManagerInterface
        ↓
AuthorizationManager
```

وهذا يجعل Authorization Manager جزءًا رسميًا من package container integration.

---

# 24. Unit Testing

تمت إضافة Unit Tests لـ:

```text
AuthorizationManager
```

والاختبارات تستخدم Mockery لعزل Laravel Gate.

الهدف هو اختبار الـManager نفسه وليس إعادة اختبار Laravel.

---

# 25. Contract Test

يتم التأكد من أن:

```php
AuthorizationManager
```

يطبق:

```php
AuthorizationManagerInterface
```

مثال الاختبار:

```php
$this->assertInstanceOf(
    AuthorizationManagerInterface::class,
    $manager
);
```

هذا يحمي الـContract ويضمن أن implementation يظل متوافقًا معه.

---

# 26. allows() Tests

تم اختبار الحالات الأساسية:

```text
✓ Gate allows ability
✓ Gate denies ability
```

مثال:

```php
$gate
    ->shouldReceive('allows')
    ->once()
    ->with('update', [])
    ->andReturnTrue();
```

ثم:

```php
$this->assertTrue(
    $manager->allows('update')
);
```

كما تم اختبار تمرير arguments:

```php
$post = new \stdClass();

$manager->allows(
    'update',
    $post
);
```

للتأكد من أن arguments تصل إلى Gate بدون تغيير.

---

# 27. denies() Tests

تم اختبار:

```text
✓ Gate denies ability
✓ Gate allows ability
```

بحيث تعكس نتيجة Laravel Gate بشكل مباشر.

---

# 28. authorize() Tests

تم اختبار نجاح:

```php
$manager->authorize(
    'update'
);
```

عندما يسمح Gate بالعملية.

كما تم اختبار exception handling عندما يرمي Gate استثناءً.

مثال الاستثناء الأصلي:

```php
$originalException = new RuntimeException(
    'Authorization service failed.',
    500
);
```

ثم يتم التأكد من أن:

```php
AuthorizationException
```

تحتوي على:

```text
Original Message
Original Code
Previous Exception
```

وبذلك يتم الحفاظ على السبب الأصلي للخطأ.

---

# 29. Advanced Authorization Tests

تمت إضافة اختبارات للعمليات المتقدمة:

```text
check()
any()
none()
inspect()
```

ويتم في كل حالة التأكد من أن الـManager يقوم بتفويض العملية إلى Gate بالشكل الصحيح.

---

# 30. User Context Tests

تم اختبار دعم user context من خلال:

```php
forUser()
```

والتأكد من أن المستخدم يتم تمريره إلى:

```php
Gate::forUser()
```

بدل تنفيذ منطق user context داخل الحزمة.

---

# 31. Service Provider Tests

تمت إضافة اختبارات للتأكد من أن:

```text
AuthorizationManagerInterface
```

يمكن حله من Container.

كما تم اختبار أن النتيجة هي:

```php
AuthorizationManager
```

وتم اختبار Singleton behavior.

---

# 32. Test Isolation

Unit Tests لا تعتمد على application authorization rules الفعلية.

بدلًا من ذلك يتم استخدام:

```text
Mockery
```

لعزل:

```text
Laravel Gate
```

وبالتالي يمكن اختبار:

```text
AuthorizationManager
```

بشكل مستقل.

---

# 33. Why Mock Gate?

الهدف من Unit Test ليس اختبار Laravel نفسه.

Laravel مسؤول عن:

```text
Gate Logic
Policy Logic
Authorization Resolution
```

بينما اختبار الحزمة يجب أن يركز على:

```text
هل يستدعي AuthorizationManager الـGate الصحيح؟
هل يمرر arguments بشكل صحيح؟
هل يحافظ على النتائج؟
هل يحول exceptions بالشكل الصحيح؟
```

لذلك يتم استخدام Mock لـGate.

---

# 34. Public API

الـPublic API الحالي لـAuthorization هو:

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

وهذه هي الواجهة التي يجب أن يعتمد عليها التطبيق بدل الاعتماد على implementation مباشرة.

---

# 35. Example Usage

يمكن حقن الـContract داخل application service:

```php
use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;

final class PostService
{
    public function __construct(
        private readonly AuthorizationManagerInterface $authorization
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

---

# 36. Example: Boolean Check

عندما يحتاج التطبيق فقط إلى معرفة ما إذا كان المستخدم يستطيع تنفيذ العملية:

```php
if ($authorization->allows(
    'update',
    $post
)) {
    // Allowed
}
```

ولا يتم استخدام:

```php
authorize()
```

إذا كان المطلوب مجرد Boolean check.

---

# 37. Example: Multiple Abilities

يمكن استخدام:

```php
if ($authorization->any(
    [
        'update',
        'delete',
    ],
    $post
)) {
    // At least one ability is allowed
}
```

وبالمقابل:

```php
if ($authorization->none(
    [
        'update',
        'delete',
    ],
    $post
)) {
    // None of the abilities are allowed
}
```

---

# 38. Example: Inspect

عندما يحتاج التطبيق إلى Authorization Response:

```php
$response = $authorization->inspect(
    'update',
    $post
);
```

والـResponse type هو:

```php
Illuminate\Auth\Access\Response
```

---

# 39. Example: Specific User

عندما يحتاج التطبيق إلى تنفيذ authorization لمستخدم محدد:

```php
$userAuthorization = $authorization->forUser(
    $user
);

$userAuthorization->allows(
    'update',
    $post
);
```

بهذا يتم فصل user context عن الـManager الأساسي.

---

# 40. What AuthorizationManager Does Not Do

الـAuthorization Manager لا يقوم حاليًا بإعادة تنفيذ أو استبدال:

```text
Policies
Gates
Middleware
Controllers
Authorization Rules
Laravel Gate Internals
```

كما لا يقوم ببناء نظام permissions خاص به.

الـManager هو abstraction layer فوق Laravel Authorization وليس authorization engine مستقلًا.

---

# 41. No Custom Permission System

لم يتم إنشاء:

```text
Roles
Permissions Tables
Permission Models
Role Models
ACL Engine
Custom Policy Engine
```

داخل `core-auth`.

السبب هو أن هذه المسؤوليات تتطلب requirements واضحة قبل إنشاء abstraction خاصة بها.

في المرحلة الحالية تعتمد الحزمة على Laravel Authorization infrastructure.

---

# 42. Architecture

البنية الحالية:

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
Illuminate\Contracts\Auth\Access\Gate
      │
      ▼
Laravel Gate
      │
      ├── Gates
      ├── Policies
      └── Authorization Rules
```

أما exceptions:

```text
AuthorizationManager
        │
        ▼
AuthorizationException
        │
        ▼
CoreAuthException
```

أما container:

```text
Laravel Container
        │
        ▼
AuthorizationManagerInterface
        │
        ▼
AuthorizationManager
```

---

# 43. Design Decisions

## 43.1 Contract First

تم إنشاء:

```text
AuthorizationManagerInterface
```

قبل الاعتماد على implementation داخل application.

الهدف:

```text
Stable API
Loose Coupling
Testability
Future Replacement
```

---

## 43.2 Dependency Injection

يعتمد `AuthorizationManager` على Gate من خلال constructor injection.

لا يتم استخدام static dependency داخل implementation.

---

## 43.3 Laravel Delegation

لا تتم إعادة كتابة authorization logic.

يتم تفويض العمليات إلى Laravel Gate.

---

## 43.4 Explicit Exception Boundary

تم وضع exception boundary حول `authorize()` حتى تكون الأخطاء الصادرة من طبقة authorization ممثلة داخل package-specific exception.

---

## 43.5 Small Abstraction

لا يحتوي Authorization Manager على منطق إضافي غير ضروري.

كل method يقوم بمهمة واضحة ويعتمد على Laravel infrastructure.

---

# 44. Security Philosophy

الأمان في authorization يعتمد بشكل أساسي على قواعد التطبيق التي يتم تعريفها في Laravel Gates وPolicies.

`core-auth` لا يدعي أنه يستبدل تلك القواعد.

دور الحزمة هو توفير abstraction للوصول إليها بشكل منظم.

بالتالي:

```text
Security Rules
      ↓
Laravel Gates / Policies
      ↓
AuthorizationManager
      ↓
Application
```

وليس:

```text
Application
      ↓
Custom Authorization Engine
```

هذا يقلل من duplication ويجعل الحزمة تستفيد من authorization infrastructure الموجودة أصلًا في Laravel.

---

# 45. Error Handling Philosophy

هناك فرق بين:

```text
Authorization Result
```

و:

```text
Unexpected Authorization Failure
```

عمليات Boolean مثل:

```text
allows()
denies()
check()
any()
none()
```

تتعامل مع authorization result كـBoolean.

أما:

```text
authorize()
```

فهي عملية enforcement وتتعامل مع الاستثناءات من خلال:

```text
AuthorizationException
```

وهذا يحافظ على وضوح الـAPI.

---

# 46. Testing Philosophy

يتم اختبار كل طبقة بشكل منفصل:

```text
Contract
   ↓
Unit Tests
   ↓
Service Provider
   ↓
Container
   ↓
Full Test Suite
```

ولا يتم الاعتماد على Unit Tests وحدها.

---

# 47. Verification Workflow

قبل Commit أو Pull Request يجب تنفيذ:

```bash
git status
git diff
git diff --check
vendor/bin/phpunit
```

ولمراجعة حجم التغييرات:

```bash
git diff --stat
git diff
```

بعد staging:

```bash
git diff --cached
git diff --cached --check
```

وبعد Commit:

```bash
git status
```

ويجب أن تكون النتيجة:

```text
working tree clean
```

---

# 48. Git Development Workflow

تم تطوير Authorization باستخدام Feature Branch مستقلة.

النمط المستخدم في المشروع:

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

بعد الدمج والتأكد من استقرار `develop` يتم حذف Feature Branch المنتهية محليًا ومن GitHub.

---

# 49. Development Methodology

المنهجية المستخدمة في تطوير Authorization هي:

```text
Requirement
      ↓
Architecture Decision
      ↓
Contract
      ↓
Implementation
      ↓
Unit Tests
      ↓
Container Integration
      ↓
Full Test Suite
      ↓
Documentation
```

هذه الطريقة تجعل كل Feature قابلة للمراجعة والتطوير بشكل مستقل.

---

# 50. Files Added / Modified

المكونات الرئيسية الخاصة بالـAuthorization:

```text
src/
├── Contracts/
│   └── AuthorizationManagerInterface.php
│
├── Exceptions/
│   └── AuthorizationException.php
│
├── Services/
│   └── AuthorizationManager.php
│
└── CoreAuthServiceProvider.php
```

والاختبارات:

```text
tests/
└── Unit/
    ├── AuthorizationManagerTest.php
    └── CoreAuthServiceProviderTest.php
```

---

# 51. Current Authorization Architecture

الحالة الحالية للـAuthorization:

```text
AuthorizationManagerInterface     ✓
AuthorizationManager              ✓
AuthorizationException            ✓
allows()                          ✓
denies()                          ✓
authorize()                       ✓
check()                           ✓
any()                             ✓
none()                            ✓
inspect()                         ✓
forUser()                         ✓
Gate Integration                  ✓
Dependency Injection              ✓
Exception Boundary                ✓
Container Binding                 ✓
Singleton Binding                 ✓
Unit Tests                        ✓
Service Provider Tests            ✓
```

---

# 52. Integration with CoreAuth

أصبحت الحزمة تحتوي على عدة مسؤوليات مستقلة:

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
└── Authorization
    └── AuthorizationManager
```

كل مسؤولية لها:

```text
Contract
Implementation
Exceptions where required
Tests
Container Integration
Documentation
```

وهذا يحافظ على modular architecture.

---

# 53. Why Authorization Is a Separate Manager

لم يتم وضع authorization methods داخل:

```text
AuthManager
```

لأن Authentication وAuthorization مسؤوليتان مختلفتان.

Authentication يهتم بـ:

```text
Who is the user?
Is the user authenticated?
Login
Logout
Session
Remember Me
User Retrieval
```

بينما Authorization يهتم بـ:

```text
Can this user perform this action?
```

لذلك:

```text
Authentication
      ↓
AuthManager

Authorization
      ↓
AuthorizationManager
```

هذا الفصل يجعل الـarchitecture أوضح وأسهل في التوسع.

---

# 54. Extensibility

التصميم الحالي يسمح بإضافة وظائف مستقبلية عند وجود requirement حقيقي.

يمكن مستقبلًا إضافة abstractions مرتبطة بـAuthorization إذا احتاجت الحزمة إليها، لكن لا يتم إضافة API لمجرد توقع الحاجة.

المبدأ:

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
```

وليس:

```text
Possible Future Requirement
      ↓
Premature Abstraction
```

---

# 55. Future Extension Strategy

الميزات المستقبلية المحتملة يجب تقييمها بشكل مستقل.

مثلًا يمكن في المستقبل دراسة:

```text
Advanced Authorization APIs
Role / Permission Abstractions
Authorization Helpers
Additional Policy Integrations
Application-level Authorization Services
```

لكن هذه ليست جزءًا من الـAuthorization API الحالي إلا بعد تنفيذها واختبارها وتوثيقها.

---

# 56. Important Design Principle

`core-auth` لا يحاول بناء Framework جديد فوق Laravel.

الهدف هو:

```text
Laravel
   +
CoreAuth Abstractions
   +
Application
```

وليس:

```text
Laravel
   +
Another Authorization Framework
```

لذلك يتم الاحتفاظ بمنطق Laravel الأساسي داخل Laravel، بينما تضيف الحزمة abstraction مناسبة للمشاريع التي تحتاج إلى architecture منظمة وقابلة لإعادة الاستخدام.

---

# 57. Summary

تمت إضافة Authorization إلى `core-auth` كطبقة مستقلة فوق Laravel Gate.

الدعم الحالي يشمل:

```text
✓ AuthorizationManagerInterface
✓ AuthorizationManager
✓ AuthorizationException
✓ allows()
✓ denies()
✓ authorize()
✓ check()
✓ any()
✓ none()
✓ inspect()
✓ forUser()
✓ Laravel Gate Integration
✓ User Context
✓ Dependency Injection
✓ Exception Boundary
✓ Container Binding
✓ Singleton Binding
✓ Contract Testing
✓ Unit Testing
✓ Service Provider Testing
✓ Laravel Testbench Integration
✓ Documentation
```

ويستخدم التطبيق الـContract:

```php
AuthorizationManagerInterface
```

بدل الاعتماد المباشر على:

```php
AuthorizationManager
```

ويتم تفويض authorization إلى Laravel Gate بدل إعادة تنفيذ authorization engine داخل الحزمة.

---

# 58. Final Architecture

البنية النهائية الحالية:

```text
                         Application
                              │
             ┌────────────────┼────────────────┐
             │                │                │
             ▼                ▼                ▼
       AuthManager     PasswordResetManager   AuthorizationManager
             │                │                │
             ▼                ▼                ▼
        Laravel Auth    PasswordBroker       Laravel Gate
             │                │                │
             ▼                ▼                ▼
       Authentication     Password Reset    Authorization
```

وتبقى كل مسؤولية مستقلة عن الأخرى.

هذا التصميم يوفر:

```text
Separation of Concerns
Contract-based APIs
Dependency Injection
Testability
Laravel Delegation
Clear Exception Boundaries
Container Integration
Future Extensibility
```

وبذلك أصبحت Authorization جزءًا رسميًا من architecture الخاصة بـ`core-auth`.
