# Core Backend — المرحلة 4

## البنية

يمر كل طلب عبر `public/index.php`، الذي يحمّل الإعدادات، ينشئ اتصال PDO، يبني repositories وservices، ثم يمرر الطلب إلى `Router`. لا توجد عمليات migrations تلقائية أثناء HTTP أو startup.

الـ repositories مسؤولة عن استعلامات PDO فقط، والـ services عن التطبيع والتحقق ومنطق المصادقة، والـ controllers عن استقبال الطلب وإرجاع JSON.

## المسارات

| Method | Path | الوظيفة |
|---|---|---|
| POST | `/admin/api/login` | تسجيل دخول المسؤول المركزي |
| GET | `/admin/api/auth/status` | فحص حالة جلسة المسؤول |
| POST | `/admin/api/logout` | إنهاء الجلسة مع CSRF |

لا تتضمن هذه المرحلة لوحة إدارة أو CRUD للمواقع أو الدومينات أو المحتوى.

## Domain Resolver

يطبع `Host` إلى lowercase، يزيل النقطة النهائية و`www.`، ويرفض schemes وpaths وqueries وports والقيم غير الصالحة. يبحث عن domain المطبع، ثم يتحقق من أن الموقع `published` وغير مؤرشف. لا يقبل `website_id` من العميل.

## المصادقة والجلسات

تقرأ المصادقة `ADMIN_USERNAME` و`ADMIN_PASSWORD_HASH` من متغيرات البيئة وتتحقق من كلمة المرور عبر `password_verify()`. تستخدم الجلسات Cookies محمية بـHttpOnly وSameSite، وSecure عند HTTPS، وتجدد Session ID بعد الدخول. يستخدم logout CSRF token، وترفض الطلبات state-changing التي لا تحتوي token صالحاً.

## المتغيرات المطلوبة

`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `ADMIN_USERNAME`, `ADMIN_PASSWORD_HASH`, و`SESSION_LIFETIME`.

## الأخطاء

تعاد الأخطاء بصيغة JSON:

```json
{"error":{"message":"Invalid credentials."}}
```

لا تعرض الاستجابة SQL أو المسارات الداخلية أو الأسرار أو stack traces؛ تسجل التفاصيل الداخلية في سجل التطبيق.

