# إدارة المستخدمين — User Manager

## 1. مقدمة

تمثل إدارة المستخدمين (User Management) مسؤولية مستقلة داخل حزمة `Core Auth`.

الهدف من هذه المرحلة هو توفير طبقة واضحة وقابلة لإعادة الاستخدام للتعامل مع المستخدمين، مع الحفاظ على الفصل بين:

* المصادقة Authentication
* التفويض Authorization
* استعادة كلمة المرور Password Reset
* التحقق من البريد الإلكتروني Email Verification
* إدارة المستخدمين User Management

لا تهدف هذه الطبقة إلى استبدال Laravel Eloquent أو بناء نظام ORM جديد، وإنما توفر واجهة موحدة للتعامل مع العمليات الأساسية المطلوبة حاليًا لإدارة المستخدمين.

تستخدم الحزمة Laravel وEloquent لتنفيذ العمليات الفعلية، بينما توفر `UserManager` طبقة abstraction مستقلة يمكن تطويرها مستقبلًا دون تغيير الكود الذي يعتمد على الـ Contract.

---

# 2. أهداف User Manager

تم تصميم User Manager لتحقيق الأهداف التالية:

1. توفير Contract واضح لإدارة المستخدمين.
2. فصل مسؤولية إدارة المستخدمين عن Authentication.
3. توفير طريقة موحدة للعثور على المستخدم.
4. دعم البحث باستخدام المعرف الأساسي.
5. دعم البحث باستخدام مجموعة من الخصائص.
6. توفير عملية إنشاء مستخدم.
7. توفير عملية تحديث مستخدم موجود.
8. دعم أي Eloquent Model يطبق `Authenticatable`.
9. استخدام Dependency Injection وService Container.
10. تسجيل User Manager كـ Singleton داخل الحزمة.
11. توفير Exceptions واضحة عند وجود إعداد غير صالح.
12. الحفاظ على إمكانية توسيع النظام مستقبلًا دون إدخال abstraction غير ضروري حاليًا.

---

# 3. مكان User Manager في Architecture

الهيكل العام الحالي للحزمة:

```text
Core Auth
│
├── Authentication
│
├── Password Reset
│
├── Email Verification
│
├── Authorization
│
└── User Management
```

ويعمل User Manager بشكل مستقل عن Authentication:

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
Eloquent Model
     │
     ▼
Database
```

أما Authentication فيتعامل مع:

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
Laravel Authentication
```

وبذلك لا يصبح `AuthManager` مسؤولًا عن إنشاء المستخدمين أو البحث عنهم أو تحديثهم، ولا يصبح `UserManager` مسؤولًا عن تسجيل الدخول أو تسجيل الخروج.

---

# 4. لماذا User Manager مسؤولية مستقلة؟

إدارة المستخدمين والمصادقة مسؤوليتان مختلفتان.

فعلى سبيل المثال:

```php
$auth->login($credentials);
```

تتعلق بالمصادقة.

بينما:

```php
$userManager->find($id);
```

تتعلق بإدارة المستخدم.

وكذلك:

```php
$userManager->update($user, $attributes);
```

تتعلق بتحديث بيانات المستخدم.

الفصل بين المسؤوليتين يمنع تضخم `AuthManager` ويجعل كل Manager مسؤولًا عن نطاق واضح.

وهذا ينسجم مع مبدأ:

> كل مكون يجب أن يمتلك مسؤولية واضحة ومحددة.

---

# 5. الملفات المتعلقة بـ User Manager

الملفات الحالية المتعلقة بهذه المرحلة:

```text
src/
├── Contracts/
│   └── UserManagerInterface.php
│
├── Exceptions/
│   └── UserException.php
│
└── Services/
    └── UserManager.php

config/
└── core-auth.php

tests/
├── Fixtures/
│   └── User.php
│
└── Unit/
    ├── UserManagerTest.php
    └── CoreAuthServiceProviderTest.php
```

---

# 6. UserManagerInterface

المسؤولية الأساسية للـ Contract هي تحديد الـ API العامة التي يوفرها User Manager.

الملف:

```text
src/Contracts/UserManagerInterface.php
```

التنفيذ الحالي:

```php
<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserManagerInterface
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function find(
        int|string $id
    ): ?Authenticatable;

    /**
     * Retrieve a user by the given attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function findBy(
        array $attributes
    ): ?Authenticatable;

    /**
     * Create a new user using the given attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(
        array $attributes
    ): Authenticatable;

    /**
     * Update an existing user using the given attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(
        Authenticatable $user,
        array $attributes
    ): Authenticatable;
}
```

---

# 7. مبدأ Contract First

تم تصميم User Manager باستخدام Contract أولًا.

أي أن التطبيق يعتمد على:

```php
UserManagerInterface
```

وليس على:

```php
UserManager
```

مباشرة.

على سبيل المثال:

```php
public function __construct(
    UserManagerInterface $users
) {
    $this->users = $users;
}
```

وهذا يسمح مستقبلًا بتغيير implementation دون الحاجة إلى تغيير الكود الذي يعتمد على الـ Contract.

---

# 8. find()

توفر الدالة `find()` إمكانية العثور على مستخدم باستخدام المعرف الخاص به.

التوقيع:

```php
public function find(
    int|string $id
): ?Authenticatable;
```

مثال:

```php
$user = $userManager->find(1);
```

أو:

```php
$user = $userManager->find('1');
```

إذا كان المستخدم موجودًا، يتم إرجاعه.

إذا لم يكن موجودًا:

```php
null
```

يتم إرجاعه.

---

# 9. لماذا يدعم find() int|string؟

المعرفات في التطبيقات قد تستخدم أنواعًا مختلفة.

مثل:

```text
1
2
3
```

أو معرفات نصية.

لذلك يستخدم الـ API:

```php
int|string
```

بدل فرض نوع واحد فقط.

ويظل تنفيذ عملية البحث مسؤولية Eloquent.

---

# 10. findBy()

توفر `findBy()` إمكانية العثور على مستخدم باستخدام مجموعة من الخصائص.

التوقيع:

```php
public function findBy(
    array $attributes
): ?Authenticatable;
```

مثال:

```php
$user = $userManager->findBy([
    'email' => 'user@example.com',
]);
```

ويمكن استخدام أكثر من خاصية:

```php
$user = $userManager->findBy([
    'email' => 'user@example.com',
    'status' => 'active',
]);
```

ويتم تمرير الخصائص إلى Eloquent باستخدام:

```php
where($attributes)
```

ثم:

```php
first()
```

---

# 11. سلوك findBy()

إذا تم العثور على مستخدم:

```php
Authenticatable
```

يتم إرجاعه.

إذا لم يتم العثور على مستخدم:

```php
null
```

يتم إرجاعه.

لا يقوم User Manager حاليًا بتحويل عدم العثور على المستخدم إلى Exception.

---

# 12. create()

توفر `create()` إمكانية إنشاء مستخدم جديد.

التوقيع:

```php
public function create(
    array $attributes
): Authenticatable;
```

مثال:

```php
$user = $userManager->create([
    'name' => 'Ahmed',
    'email' => 'ahmed@example.com',
    'password' => 'password',
]);
```

تقوم العملية بتمرير الخصائص إلى Eloquent:

```php
$this->modelClass::query()->create($attributes);
```

ثم تعيد المستخدم الذي تم إنشاؤه.

---

# 13. update()

توفر `update()` إمكانية تحديث مستخدم موجود باستخدام مجموعة من الخصائص.

التوقيع:

```php
public function update(
    Authenticatable $user,
    array $attributes
): Authenticatable;
```

مثال:

```php
$user = $userManager->find(1);

$user = $userManager->update($user, [
    'name' => 'Ahmed Salah',
]);
```

تقوم العملية بتمرير الخصائص إلى المستخدم باستخدام Eloquent:

```php
$user->update($attributes);
```

ثم يتم تحديث نسخة المستخدم المعادة من خلال:

```php
$user->refresh();
```

وبالتالي تعيد `update()` المستخدم بعد تطبيق التغييرات وقراءة حالته الحالية من قاعدة البيانات.

---

# 14. سلوك update()

تقبل `update()` مستخدمًا موجودًا من النوع:

```php
Authenticatable
```

ثم مجموعة من الخصائص:

```php
array $attributes
```

ولا تشترط العملية تحديث جميع خصائص المستخدم.

على سبيل المثال، يمكن تحديث الاسم فقط:

```php
$userManager->update($user, [
    'name' => 'New Name',
]);
```

مع بقاء بقية البيانات دون تغيير.

بعد تنفيذ التحديث، يتم استدعاء:

```php
refresh()
```

حتى تكون القيمة المعادة ممثلة للحالة الحالية للمستخدم في قاعدة البيانات.

---

# 15. لماذا يستخدم update() refresh()؟

تنفيذ:

```php
$user->update($attributes);
```

يقوم بتحديث النموذج.

بعد ذلك يستخدم User Manager:

```php
$user->refresh();
```

والهدف هو إعادة تحميل النموذج من قاعدة البيانات بعد التحديث.

وبذلك تكون القيمة التي يعيدها:

```php
update()
```

هي المستخدم بعد تحديث حالته من المصدر الفعلي للبيانات.

---

# 16. مسؤولية تشفير كلمة المرور

User Manager الحالي لا يقوم بتشفير كلمات المرور بنفسه.

أي أن:

```php
create()
```

و:

```php
update()
```

لا يحتويان حاليًا على منطق مخصص لتشفير كلمة المرور.

مسؤولية تجهيز قيمة كلمة المرور قبل تمريرها إلى عمليات User Manager تعتمد على التطبيق المستخدم للحزمة.

لا ينبغي إضافة منطق إضافي إلى User Manager لمجرد افتراض حاجة مستقبلية غير موجودة في التصميم الحالي.

---

# 17. User Model Configuration

يحتاج User Manager إلى معرفة Eloquent Model الذي يمثل المستخدم.

تمت إضافة الإعداد التالي إلى:

```text
config/core-auth.php
```

```php
return [
    'user' => [
        'model' => null,
    ],
];
```

القيمة الافتراضية هي:

```php
null
```

والتطبيق المستهلك للحزمة يقوم بتحديد الـ Model الخاص به.

مثال:

```php
'user' => [
    'model' => App\Models\User::class,
],
```

---

# 18. لماذا يتم استخدام Configuration؟

بدل ربط User Manager بـ Model معين داخل الحزمة، يتم تحديد الـ Model من التطبيق الذي يستخدم `Core Auth`.

وبذلك لا تفترض الحزمة وجود:

```php
App\Models\User
```

أو أي Model محدد.

هذا يجعل الحزمة مستقلة عن بنية التطبيق المستهلك.

---

# 19. نوع الـ User Model

يتوقع User Manager أن يكون الـ Model المحدد:

1. Eloquent Model.
2. ويطبق `Illuminate\Contracts\Auth\Authenticatable`.

ولهذا يتم التحقق من النوع عند إنشاء User Manager.

النوع المنطقي المتوقع:

```php
class-string<Model&Authenticatable>
```

مع السماح بـ:

```php
null
```

في constructor لأن Configuration قد لا تكون مضبوطة.

---

# 20. التحقق من User Model

يحتوي `UserManager` على تحقق من الـ Model المحدد.

التحقق يتأكد من:

```php
is_a($modelClass, Model::class, true)
```

ثم:

```php
is_a($modelClass, Authenticatable::class, true)
```

وبذلك لا يمكن للحزمة استخدام class غير مناسب باعتباره User Model.

---

# 21. لماذا يتم التحقق عند الإنشاء؟

يتم التحقق من الـ Model في constructor بدل الانتظار حتى استدعاء:

```php
find()
```

أو:

```php
findBy()
```

أو:

```php
create()
```

أو:

```php
update()
```

والسبب هو اكتشاف configuration غير صحيحة في أقرب نقطة ممكنة.

إذا كانت إعدادات User Manager غير صحيحة، فمن الأفضل أن يفشل الـ Manager مبكرًا بدل إنشاء حالة غير صالحة تستمر داخل التطبيق.

---

# 22. UserException

تم إنشاء Exception مخصص لهذه المسؤولية:

```text
src/Exceptions/UserException.php
```

التنفيذ:

```php
<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Exceptions;

final class UserException extends CoreAuthException
{
}
```

وبالتالي:

```text
UserException
      │
      ▼
CoreAuthException
```

---

# 23. متى يتم استخدام UserException؟

يستخدم حاليًا عند وجود configuration غير صالحة لـ User Model.

مثل:

```php
null
```

أو class لا يمثل Eloquent Model صالحًا.

أو class لا يطبق:

```php
Authenticatable
```

في هذه الحالات يتم إطلاق:

```php
UserException
```

برسالة توضح المشكلة.

الرسالة الحالية:

```text
The configured user model must be an Eloquent model that implements Authenticatable.
```

---

# 24. لماذا لا نستخدم TypeError؟

قد يبدو للوهلة الأولى أنه يمكن تعريف constructor هكذا:

```php
public function __construct(
    private readonly string $modelClass
)
```

لكن Configuration الافتراضية هي:

```php
null
```

لذلك سيتم الحصول على `TypeError` قبل أن يتمكن User Manager من تنفيذ التحقق الخاص به.

لهذا يستخدم التنفيذ الحالي:

```php
private readonly ?string $modelClass
```

ثم يتم إجراء validation يدوي واضح.

وهذا يسمح بإرجاع:

```php
UserException
```

بدل الاعتماد على خطأ TypeError الناتج من PHP.

---

# 25. UserManager Implementation

الملف:

```text
src/Services/UserManager.php
```

التنفيذ الحالي:

```php
<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\UserException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class UserManager implements UserManagerInterface
{
    /**
     * @param class-string<Model&Authenticatable>|null $modelClass
     *
     * @throws UserException
     */
    public function __construct(
        private readonly ?string $modelClass
    ) {
        if (
            $modelClass === null ||
            ! is_a($modelClass, Model::class, true) ||
            ! is_a($modelClass, Authenticatable::class, true)
        ) {
            throw new UserException(
                'The configured user model must be an Eloquent model that implements Authenticatable.'
            );
        }
    }

    public function find(
        int|string $id
    ): ?Authenticatable {
        return $this->modelClass::query()->find($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function findBy(
        array $attributes
    ): ?Authenticatable {
        return $this->modelClass::query()
            ->where($attributes)
            ->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(
        array $attributes
    ): Authenticatable {
        /** @var Model&Authenticatable $user */
        $user = $this->modelClass::query()->create($attributes);

        return $user;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(
        Authenticatable $user,
        array $attributes
    ): Authenticatable {
        /** @var Model&Authenticatable $user */
        $user->update($attributes);

        return $user->refresh();
    }
}
```

---

# 26. لماذا يتم حقن Model Class في Constructor؟

يتم تمرير الـ Model إلى User Manager عند إنشائه:

```php
new UserManager(
    $app['config']->get('core-auth.user.model')
)
```

بدل قراءة Configuration في كل method.

وهذا يعني أن User Manager يحصل على الـ dependency المطلوبة مرة واحدة.

كما أن ذلك يجعل الكلاس أكثر وضوحًا وأسهل للاختبار.

---

# 27. Service Container

تم تسجيل User Manager داخل:

```text
src/CoreAuthServiceProvider.php
```

باستخدام:

```php
$this->app->singleton(
    UserManagerInterface::class,
    fn ($app) => new UserManager(
        $app['config']->get('core-auth.user.model')
    )
);
```

وبالتالي يمكن للتطبيق الاعتماد على:

```php
UserManagerInterface
```

ويقوم Laravel Container بتوفير implementation المناسب.

---

# 28. لماذا Singleton؟

تم تسجيل User Manager كـ Singleton لأن الكلاس لا يحتفظ بحالة متغيرة مرتبطة بمستخدم معين.

الـ Model Class يتم تحديده عند إنشاء الـ Manager، وبعد ذلك يتم استخدامه لتنفيذ العمليات.

لذلك لا توجد حاجة لإنشاء instance جديد من User Manager لكل عملية.

---

# 29. Dependency Injection

يمكن استخدام الـ Contract من خلال Dependency Injection:

```php
use AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface;

public function __construct(
    UserManagerInterface $users
) {
    $this->users = $users;
}
```

ولا يحتاج التطبيق إلى إنشاء:

```php
new UserManager(...)
```

يدويًا.

---

# 30. Fixture المستخدم في الاختبارات

للاختبارات، تم إنشاء Model مستقل:

```text
tests/Fixtures/User.php
```

ويطبق:

```php
Authenticatable
```

باستخدام Laravel trait:

```php
AuthenticatableTrait
```

كما أنه يرث من:

```php
Model
```

ويستخدم جدول:

```text
users
```

---

# 31. إعداد Fixture

الـ Fixture المستخدم في الاختبارات يحتوي على:

```php
final class User extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
```

تم تعطيل timestamps لأن schema الخاص بالاختبارات لا يحتوي على:

```text
created_at
updated_at
```

ولا تحتاج اختبارات User Manager الحالية إلى هذه الأعمدة.

---

# 32. قاعدة بيانات الاختبارات

يتم استخدام SQLite in-memory في اختبارات User Manager.

وبذلك يمكن اختبار عمليات Eloquent الحقيقية دون الاعتماد على قاعدة بيانات خارجية.

الاختبارات تقوم بإنشاء جدول المستخدمين واختبار العمليات عليه.

هذا مناسب لأن User Manager يعتمد فعليًا على Eloquent وعمليات قاعدة البيانات.

---

# 33. اختبارات User Manager

الملف:

```text
tests/Unit/UserManagerTest.php
```

تمت تغطية السلوكيات الأساسية التالية:

* العثور على مستخدم باستخدام ID.
* التعامل مع ID غير موجود.
* العثور على مستخدم باستخدام attributes.
* التعامل مع attributes لا تطابق أي مستخدم.
* إنشاء مستخدم جديد.
* تحديث مستخدم موجود.
* تحديث جزء من بيانات المستخدم مع الحفاظ على البيانات الأخرى.
* التعامل مع User Model غير مضبوط.
* التعامل مع User Model غير صالح.

---

# 34. اختبار find()

يتم اختبار أن:

```php
find($id)
```

يعيد المستخدم الصحيح عند وجوده.

كما يتم اختبار حالة عدم وجود المستخدم والتأكد من إرجاع:

```php
null
```

---

# 35. اختبار findBy()

يتم اختبار البحث باستخدام attributes:

```php
[
    'email' => '...',
]
```

كما يتم اختبار عدم العثور على مستخدم.

الهدف هو التأكد من أن User Manager لا يضيف behavior خاصًا فوق behavior الذي يوفره Eloquent.

---

# 36. اختبار create()

يتم اختبار إنشاء مستخدم باستخدام:

```php
create(array $attributes)
```

ثم التحقق من أن المستخدم تم إنشاؤه وإرجاعه بشكل صحيح.

كما يتم الاعتماد على SQLite للتحقق من عملية الحفظ الفعلية.

---

# 37. اختبار update()

يتم اختبار تحديث مستخدم موجود باستخدام:

```php
update(
    Authenticatable $user,
    array $attributes
)
```

ويتم التحقق من أن التغييرات تم تطبيقها على قاعدة البيانات.

كما يتم اختبار التحديث الجزئي، بحيث يتم تغيير خاصية محددة مع التأكد من بقاء الخصائص الأخرى دون تغيير.

ويتم أيضًا التحقق من أن القيمة المعادة تمثل المستخدم بعد التحديث.

---

# 38. اختبار Configuration

يتم اختبار الحالة التي يكون فيها:

```php
core-auth.user.model
```

غير مضبوط.

ويجب أن ينتج عنها:

```php
UserException
```

بدل إنشاء User Manager غير صالح.

---

# 39. اختبار Model غير صالح

يتم أيضًا اختبار Model لا يحقق المتطلبات المطلوبة.

أي أنه ليس:

```php
Eloquent Model
```

أو لا يطبق:

```php
Authenticatable
```

ويجب أن يؤدي ذلك إلى:

```php
UserException
```

---

# 40. اختبارات Service Provider

تم تحديث:

```text
tests/Unit/CoreAuthServiceProviderTest.php
```

للتأكد من تسجيل User Manager داخل Laravel Container.

يتم أولًا ضبط:

```php
config()->set(
    'core-auth.user.model',
    \Tests\Fixtures\User::class
);
```

ثم يتم التأكد من إمكانية resolve للـ Contract.

---

# 41. اختبار Singleton

يتم أيضًا التأكد من أن:

```php
UserManagerInterface::class
```

مسجل كـ Singleton.

أي أن:

```php
$app->make(UserManagerInterface::class)
```

يعيد نفس instance عند طلبه أكثر من مرة ضمن نفس container.

---

# 42. حدود مسؤولية User Manager

المسؤوليات الحالية لـ User Manager هي:

```text
User Management
│
├── Find by ID
├── Find by attributes
├── Create user
└── Update user
```

ولا تشمل حاليًا:

```text
Authentication
Authorization
Password Reset
Email Verification
Roles
Permissions
Repositories
Factories
Events
```

كل مسؤولية من هذه المسؤوليات لها مكانها الخاص في Architecture أو قد تتم إضافتها مستقبلًا إذا ظهرت حاجة حقيقية لها.

---

# 43. ما الذي لا يفعله User Manager؟

User Manager لا يقوم حاليًا بـ:

### تسجيل الدخول

يتم ذلك من خلال:

```text
AuthManager
```

### تسجيل الخروج

يتم ذلك من خلال:

```text
AuthManager
```

### التحقق من الصلاحيات

يتم ذلك من خلال:

```text
AuthorizationManager
```

### إرسال رابط إعادة تعيين كلمة المرور

يتم ذلك من خلال:

```text
PasswordResetManager
```

### التحقق من البريد الإلكتروني

يتم ذلك من خلال:

```text
EmailVerificationManager
```

---

# 44. عدم إضافة Repository حاليًا

لم تتم إضافة Repository abstraction إلى User Manager.

مثلًا لا توجد حاليًا طبقة:

```text
UserRepositoryInterface
UserRepository
```

وهذا قرار مقصود.

User Manager يحتاج حاليًا إلى عمليات بسيطة ومباشرة:

```text
find
findBy
create
update
```

وEloquent يوفر abstraction كافية لهذه العمليات.

إضافة Repository في هذه المرحلة ستضيف طبقة إضافية دون وجود حاجة معمارية واضحة تبررها.

إذا ظهرت متطلبات حقيقية مستقبلًا، يمكن إعادة تقييم القرار.

---

# 45. عدم إضافة Factory حاليًا

كذلك لا توجد User Factory داخل Core Auth.

السبب هو أن User Manager الحالي لا يحتاج إلى abstraction إضافية لإنشاء المستخدم.

عملية:

```php
$model::query()->create($attributes)
```

كافية لتنفيذ مسؤولية الإنشاء الحالية.

---

# 46. التعامل مع أخطاء قاعدة البيانات

User Manager الحالي لا يقوم بتغليف جميع استثناءات قاعدة البيانات داخل:

```php
UserException
```

مثلًا إذا حدث خطأ SQL أثناء:

```php
create()
```

أو:

```php
update()
```

فإن الاستثناء الصادر من Laravel/Eloquent يمكن أن ينتقل إلى التطبيق.

هذا قرار مقصود في المرحلة الحالية، لأن `UserException` مخصص حاليًا لمشكلة configuration المتعلقة بـ User Model.

لا ينبغي توسيع exception boundary دون حاجة واضحة.

---

# 47. Eloquent هو المسؤول عن عمليات ORM

User Manager لا يعيد تنفيذ وظائف Eloquent.

مثلًا:

```php
$model::query()->find($id);
```

يتم تنفيذها بواسطة Eloquent.

وكذلك:

```php
$model::query()
    ->where($attributes)
    ->first();
```

و:

```php
$model::query()->create($attributes);
```

و:

```php
$user->update($attributes);
```

وبذلك تبقى مسؤولية User Manager هي توفير abstraction على مستوى الحزمة، وليس إعادة بناء Eloquent.

---

# 48. Laravel هو المسؤول عن Model Lifecycle

يبقى Laravel/Eloquent مسؤولًا عن:

* Model lifecycle.
* Query Builder.
* Persistence.
* Relationships.
* Casting.
* Events الخاصة بـ Eloquent.
* Mass assignment.
* Database interaction.

User Manager لا يحاول نقل هذه المسؤوليات إلى داخل الحزمة.

---

# 49. Mass Assignment

تعتمد عمليتا:

```php
create()
```

و:

```php
update()
```

على آليات Eloquent.

لذلك فإن قواعد Mass Assignment الخاصة بالـ Model المستخدم تظل مطبقة.

على سبيل المثال:

```php
$fillable
```

أو:

```php
$guarded
```

تبقى مسؤولية الـ Model.

User Manager لا يتجاوز هذه الحماية.

---

# 50. Public API الحالي

واجهة User Manager الحالية:

```php
find(
    int|string $id
): ?Authenticatable;
```

```php
findBy(
    array $attributes
): ?Authenticatable;
```

```php
create(
    array $attributes
): Authenticatable;
```

```php
update(
    Authenticatable $user,
    array $attributes
): Authenticatable;
```

وهذه هي الـ API العامة المعتمدة حاليًا لهذه المرحلة.

---

# 51. أمثلة الاستخدام

## العثور على مستخدم

```php
$user = $userManager->find(1);
```

---

## العثور باستخدام البريد الإلكتروني

```php
$user = $userManager->findBy([
    'email' => 'user@example.com',
]);
```

---

## العثور باستخدام أكثر من attribute

```php
$user = $userManager->findBy([
    'email' => 'user@example.com',
    'status' => 'active',
]);
```

---

## إنشاء مستخدم

```php
$user = $userManager->create([
    'name' => 'Ahmed',
    'email' => 'ahmed@example.com',
]);
```

---

## تحديث مستخدم

```php
$user = $userManager->update($user, [
    'name' => 'Ahmed Salah',
]);
```

---

# 52. العلاقة مع Authentication

يمكن أن تتعاون المكونات معًا دون دمج مسؤولياتها.

مثال:

```text
UserManager
     │
     │ creates / updates
     ▼
   User
     │
     │ authenticated by
     ▼
 AuthManager
```

لكن User Manager لا يقوم بعملية login بنفسه.

هذا يحافظ على استقلال المسؤوليات.

---

# 53. العلاقة مع Authorization

بعد الحصول على مستخدم:

```php
$user = $userManager->find($id);
```

يمكن استخدامه في نظام Authorization.

مثلًا:

```text
UserManager
     │
     ▼
   User
     │
     ▼
AuthorizationManager
```

لكن User Manager لا يعرف تفاصيل:

* Policies
* Abilities
* Gate
* Permissions

هذه مسؤولية `AuthorizationManager`.

---

# 54. التصميم الحالي

التصميم الحالي يمكن تمثيله كالتالي:

```text
                    Core Auth
                        │
       ┌────────────────┼────────────────┐
       │                │                │
       ▼                ▼                ▼
 Authentication    Authorization    User Management
       │                │                │
       ▼                ▼                ▼
 AuthManager       Authorization     UserManager
                       Manager            │
                                          ▼
                                    Eloquent Model
```

وتوجد كذلك مكونات مستقلة لـ:

```text
Password Reset
Email Verification
```

---

# 55. لماذا UserManager ليس CRUD كاملًا؟

تم اختيار API محددة في المرحلة الحالية:

```text
find
findBy
create
update
```

بدل إضافة:

```text
delete
paginate
restore
forceDelete
```

دون وجود متطلبات فعلية.

إضافة `update()` جاءت كمتطلب فعلي لتوفير عملية تحديث المستخدم ضمن مسؤولية User Management، بينما لا يعني ذلك تحويل User Manager إلى طبقة CRUD كاملة تغطي جميع إمكانيات Eloquent.

هذا يحافظ على API بسيطة ويمنع تضخم الـ Manager مبكرًا.

إضافة عمليات أخرى يجب أن تكون نتيجة متطلبات واضحة، وليس مجرد محاولة جعل User Manager يغطي كل عمليات Eloquent.

---

# 56. قابلية التوسع المستقبلية

تم تصميم الـ Contract بحيث يمكن توسيعه مستقبلًا إذا ظهرت متطلبات حقيقية.

قد تظهر لاحقًا احتياجات مثل:

```text
delete user
list users
pagination
user status
```

لكن هذه العمليات ليست جزءًا من الـ API الحالية.

سيتم اتخاذ قرار إضافتها بناءً على احتياجات المشروع الفعلية.

---

# 57. مبدأ عدم الإفراط في التجريد

من المبادئ المهمة في المشروع:

> لا نضيف abstraction لمجرد أن المشروع يمكن أن يحتوي عليه.

لذلك تم الاكتفاء حاليًا بـ:

```text
UserManagerInterface
        │
        ▼
UserManager
        │
        ▼
Eloquent Model
```

بدل:

```text
UserManagerInterface
        │
        ▼
UserManager
        │
        ▼
UserRepositoryInterface
        │
        ▼
UserRepository
        │
        ▼
Eloquent
```

إلا إذا ظهرت حاجة حقيقية تجعل هذه الطبقة الإضافية مفيدة.

---

# 58. الاختبارات الحالية

تمت إضافة اختبارات User Manager بنجاح، وتم توسيعها لتغطية عملية تحديث المستخدم بالإضافة إلى العمليات السابقة.

تشمل اختبارات User Manager الحالية:

```text
find()
findBy()
create()
update()
Model Validation
Configuration Validation
```

كما تمت إضافة اختبارات تسجيل User Manager في Service Provider.

الحالة الحالية الكاملة للمشروع بعد دمج تحديث User Manager:

```text
121 tests
239 assertions
```

والنتيجة:

```text
OK
```

---

# 59. استراتيجية الاختبار

تعتمد هذه المرحلة على اختبار السلوك الفعلي بدل اختبار تفاصيل التنفيذ الداخلية.

يتم اختبار:

```text
find()
```

من خلال قاعدة بيانات SQLite حقيقية داخل الذاكرة.

ويتم اختبار:

```text
findBy()
```

من خلال بيانات فعلية.

ويتم اختبار:

```text
create()
```

من خلال Eloquent وSQLite.

ويتم اختبار:

```text
update()
```

من خلال Eloquent وSQLite، مع التحقق من تطبيق التغيير والمحافظة على البيانات الأخرى عند التحديث الجزئي.

وهذا يجعل الاختبارات أكثر ارتباطًا بالسلوك الذي يهم المستخدم النهائي للحزمة.

---

# 60. Service Provider والـ Configuration

يقوم:

```text
CoreAuthServiceProvider
```

بمسؤوليتين أساسيتين لهذه المرحلة:

1. تحميل Configuration الخاصة بالحزمة.
2. تسجيل User Manager في Container.

يتم تحميل:

```text
config/core-auth.php
```

باستخدام:

```php
$this->mergeConfigFrom(
    __DIR__ . '/../config/core-auth.php',
    'core-auth'
);
```

ثم يتم الوصول إلى:

```text
core-auth.user.model
```

عند إنشاء User Manager.

---

# 61. القرار المعماري الحالي

القرارات الأساسية لهذه المرحلة:

| القرار                  | الحالة           |
| ----------------------- | ---------------- |
| Contract مستقل          | نعم              |
| Manager مستقل           | نعم              |
| User Model configurable | نعم              |
| Eloquent Model          | مطلوب            |
| Authenticatable         | مطلوب            |
| Service Container       | مستخدم           |
| Singleton               | مستخدم           |
| Custom UserException    | نعم              |
| Repository              | غير موجود حاليًا |
| Factory                 | غير موجود حاليًا |
| CRUD كامل               | غير موجود        |
| Custom ORM              | غير موجود        |

---

# 62. مسؤولية User Manager في المشروع

User Manager مسؤول عن توفير abstraction بسيطة لإدارة المستخدمين على مستوى الحزمة.

يمكن تلخيص مسؤوليته الحالية في:

```text
User Manager
│
├── معرفة User Model من Configuration
│
├── التحقق من صلاحية User Model
│
├── find()
│
├── findBy()
│
├── create()
│
└── update()
```

ولا يتجاوز هذه الحدود حاليًا.

---

# 63. سبب استخدام Authenticatable في Return Type

يستخدم الـ Contract:

```php
?Authenticatable
```

و:

```php
Authenticatable
```

بدل ربط الـ API بـ Model معين.

وهذا يسمح للحزمة بالتعامل مع User Models مختلفة طالما أنها تحقق العقد المطلوب من Laravel.

وبالتالي لا يصبح الـ API مرتبطًا بـ:

```php
App\Models\User
```

أو أي implementation محدد.

---

# 64. استقلال الحزمة عن تطبيق المستخدم

من أهم أهداف هذه المرحلة أن الحزمة لا تفترض بنية معينة للتطبيق.

فالتطبيق هو الذي يحدد:

```php
'model' => App\Models\User::class,
```

بينما Core Auth لا يحتاج إلى معرفة اسم الـ Model أو مكانه مسبقًا.

وهذا ضروري لأن الحزمة مصممة للاستخدام داخل تطبيقات مختلفة.

---

# 65. ما تم إنجازه في هذه المرحلة

تم إنجاز المكونات التالية:

```text
✓ UserManagerInterface
✓ UserManager
✓ UserException
✓ User Model Configuration
✓ Service Provider Binding
✓ User Fixture
✓ User Manager Tests
✓ User Manager Update Tests
✓ Service Provider Tests
✓ SQLite Integration-style database testing
```

وأصبحت User Management جزءًا فعليًا من Architecture الخاصة بالحزمة.

---

# 66. Git Workflow

تم تطوير تحديث User Manager باستخدام Feature Branch مستقل:

```text
feature/user-management-update
```

ثم تم تنفيذ الاختبارات والتحقق من الكود.

تم إنشاء commit:

```text
ae7930c feat: add user manager update
```

ثم تم رفع الـ branch وإنشاء Pull Request.

بعد مراجعة التغييرات، تم دمج الـ Pull Request في:

```text
develop
```

ثم تم تحديث الفرع المحلي `develop` والتحقق من حالة المشروع.

---

# 67. الحالة الحالية للفروع

بعد دمج تحديث User Manager وتنظيف الفروع تصبح الفروع الأساسية:

```text
develop
main
origin/develop
origin/main
```

ولا توجد حاجة للاحتفاظ بفرع:

```text
feature/user-management-update
```

بعد اكتمال الدمج وحذفه من المحلي والبعيد.

---

# 68. التحقق النهائي

تم تشغيل:

```bash
vendor/bin/phpunit
```

والنتيجة الحالية:

```text
121 tests
239 assertions

OK
```

وهذا يؤكد أن إضافة عملية تحديث المستخدم لم تكسر الاختبارات الموجودة مسبقًا، وأن كامل مجموعة الاختبارات تمر بنجاح.

---

# 69. الحالة الحالية لـ Core Auth

بعد اكتمال User Management أصبحت المسؤوليات الرئيسية المنفذة في الحزمة:

```text
Core Auth
│
├── Authentication
│   ├── Login
│   ├── Logout
│   ├── Guards
│   ├── Authentication Events
│   ├── Remember Me
│   └── Typed Authenticated User
│
├── Password Reset
│
├── Email Verification
│
├── Authorization
│   ├── allows
│   ├── denies
│   ├── authorize
│   ├── check
│   ├── any
│   ├── none
│   ├── inspect
│   └── forUser
│
└── User Management
    ├── find
    ├── findBy
    ├── create
    └── update
```

---

# 70. مبادئ التصميم المستخدمة

يعتمد User Manager على المبادئ التالية:

### Contract First

الاعتماد على Interface بدل implementation مباشر.

### Single Responsibility

User Manager مسؤول عن إدارة المستخدمين فقط.

### Dependency Injection

استخدام Laravel Container لتوفير الـ Manager.

### Configuration Driven

اختيار User Model من Configuration.

### Framework Integration

الاعتماد على Eloquent بدل إعادة تنفيذ وظائف ORM.

### Fail Fast

اكتشاف configuration غير الصحيحة عند إنشاء Manager.

### Minimal Abstraction

عدم إضافة Repository أو Factory دون حاجة فعلية.

### Testability

اختبار السلوك باستخدام Testbench وSQLite.

### Extensibility

الحفاظ على API مستقلة يمكن توسيعها مستقبلًا.

---

# 71. ما الذي لا يجب اعتباره جزءًا من المرحلة الحالية؟

يجب عدم اعتبار الميزات التالية جزءًا من User Manager الحالي:

```text
Roles
Permissions
ACL
Repository Pattern
Factory Pattern
User Events
Profile Management
Avatar Management
User Preferences
User Activation Workflow
User Suspension Workflow
Bulk User Operations
Advanced Search
Pagination API
```

وجود احتياج مستقبلي لأي منها لا يعني إضافته تلقائيًا إلى User Manager.

يجب أولًا تحديد المسؤولية المناسبة له ثم اتخاذ قرار معماري واضح.

---

# 72. العلاقة مع بقية Managers

الـ Managers الحالية تمثل مسؤوليات منفصلة:

```text
AuthManager
    → Authentication

PasswordResetManager
    → Password Reset

EmailVerificationManager
    → Email Verification

AuthorizationManager
    → Authorization

UserManager
    → User Management
```

وهذا الفصل هو أحد أهم عناصر التصميم الحالي للحزمة.

---

# 73. Public API النهائي لهذه المرحلة

```php
interface UserManagerInterface
{
    public function find(
        int|string $id
    ): ?Authenticatable;

    /**
     * @param array<string, mixed> $attributes
     */
    public function findBy(
        array $attributes
    ): ?Authenticatable;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(
        array $attributes
    ): Authenticatable;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(
        Authenticatable $user,
        array $attributes
    ): Authenticatable;
}
```

هذا هو الـ API المعتمد حاليًا.

أي تغيير مستقبلي في هذه الواجهة يجب أن يتم باعتباره تغييرًا في public contract للحزمة، وليس تعديلًا داخليًا بسيطًا.

---

# 74. الخلاصة

أضافت مرحلة User Management طبقة مستقلة لإدارة المستخدمين داخل `Core Auth`.

أصبحت الحزمة الآن قادرة على توفير:

```text
Authentication
Password Reset
Email Verification
Authorization
User Management
```

مع الحفاظ على الفصل بين المسؤوليات.

ويعتمد User Manager حاليًا على:

```text
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

والـ API الحالية محددة في:

```text
find()
findBy()
create()
update()
```

مع التحقق من صحة User Model واستخدام `UserException` عند وجود configuration غير صحيحة.

وتوفر `update()` تحديثًا لمستخدم موجود مع إعادة تحميل حالته بعد التحديث باستخدام Eloquent.

ولا توجد في هذه المرحلة إضافات غير ضرورية مثل Repository أو Factory أو CRUD كامل.

---

# 75. الحالة النهائية

**User Management: مكتمل**

```text
✓ Contract
✓ Implementation
✓ Configuration
✓ Exception
✓ Container Binding
✓ Model Validation
✓ Find by ID
✓ Find by Attributes
✓ Create User
✓ Update User
✓ Partial User Update
✓ Tests
✓ Service Provider Tests
✓ Documentation
✓ Git Integration
```

الحالة العامة للاختبارات:

```text
121 tests
239 assertions
OK
```

وبذلك أصبحت User Management موثقة كميزة مستقلة ضمن بنية `Core Auth`، مع دعم عمليات البحث والإنشاء والتحديث ضمن حدود مسؤولية واضحة ودون تحويلها إلى abstraction عامة تغطي كامل وظائف Eloquent.
