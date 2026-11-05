# خطة Migrations للمرحلة 3

هذه خطة تنفيذ فقط؛ لا تحتوي على ملفات Migration فعلية ولا تنفذ أي أمر.

## القواعد

- الاسم: `YYYYMMDDNNNN_description.php`، ملف مستقل لكل تغيير.
- التنفيذ صريح من CLI بعد اتصال PDO؛ لا تنفيذ تلقائي في HTTP أو عند إقلاع التطبيق.
- يسجل `schema_migrations` الاسم والإصدار وchecksum ووقت النجاح فقط.
- يتوقف التنفيذ عند الخطأ، ولا يسجل الترحيل الفاشل كناجح.
- يجرى Backup قبل التغييرات المؤثرة، وتختبر الترحيلات على نسخة اختبار.
- لا يفترض التصميم أن كل DDL قابل للتراجع Transactionally في MySQL؛ أوامر DDL قد تسبب implicit commits، لذلك لا تضمن migrations rollback كاملاً. عند الفشل قد تبقى تغييرات جزئية في المخطط، وتوثق خطوات الاستعادة يدوياً عند الحاجة.

## الترتيب المقترح

1. `create_schema_migrations`.
2. `create_templates`.
3. `create_websites` مع `template_id` و`template_version` وFK إلى القالب.
4. `create_domains` مع `UNIQUE(domain)`، و`UNIQUE(website_id,id)`، والعمود `primary_website_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN website_id ELSE NULL END) STORED`، وقيد `UNIQUE(primary_website_id)`. يظل شرط وجود دومين أساسي قبل التفعيل والتحقق من عدم ترك موقع مفعّل بلا دومين أساسي مسؤولية `WebsiteService` داخل Database Transaction، وليس قيداً يمكن لهذا الجدول وحده فرضه.
5. `create_pages` مع `UNIQUE(website_id,slug)`.
6. `create_website_template_settings` مع العمود `active_template_id VARCHAR(100) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN template_id ELSE NULL END) STORED` وقيد `UNIQUE(website_id,active_template_id)`.
7. `create_media` مع `UNIQUE(website_id,id)` وFK مباشر إلى `websites(id)`.
8. `create_sections` مع `UNIQUE(website_id,id)` وFK المركب إلى `pages(website_id,id)`.
9. `create_navigation_menus`.
10. `create_navigation_items` مع `UNIQUE(website_id,id)` وFKs المركبة للأب والقائمة والصفحة.
11. `create_website_settings` كجدول Key/Value (`setting_key`, `setting_value JSON`) مع `UNIQUE(website_id,setting_key)`، دون أعمدة branding مستقلة.
12. `add_integrity_checks`.

## Seeders

يسجل Seeder القوالب المعتمدة فقط (`templates`) إذا كانت ملفاتها موجودة وصالحة. لا ينشئ مواقعاً وهمية. عند إنشاء موقع فعلي في مرحلة لاحقة، ينسخ التطبيق ملفات `default-media` إلى تخزين الموقع وينشئ سجلات media منفصلة، ثم ينشئ إعدادات ومحتوى افتراضياً قابلين للتعديل.

## الإنتاج والفشل

يراجع المسؤول الحالة، يأخذ نسخة احتياطية، يشغل أمر migration صريحاً، يراجع السجل، ثم يختبر القراءة. عند الفشل يوقف النشر ويستعيد النسخة أو ينفذ إجراء الإصلاح الموثق؛ لا يعاد تشغيل Migration ناجح ولا تعدل قاعدة الإنتاج يدوياً كحل اعتيادي.

## أوامر التنفيذ الحالية

من داخل حاوية PHP:

```text
php bin/console db:migrate:status
php bin/console db:migrate
php bin/console db:seed
```

لا تستدعي هذه الأوامر من HTTP أو من startup hook. Seeder المرحلة الحالية يسجل القوالب الموجودة فقط إذا احتوى مجلد القالب على `template.json` صالح؛ لا ينشئ مواقعاً أو بيانات أعمال افتراضية.

ملاحظة تنفيذية: تستخدم علاقات `domains.website_id` و`website_template_settings.template_id` سلوك `ON DELETE RESTRICT` دون `ON UPDATE CASCADE` لأن MySQL يرفض تحديثاً متسلسلاً لمفتاح يعتمد عليه generated column في الجدول نفسه. لا يغير ذلك ملكية السجلات أو قيود العزل أو سياسة الحذف المعتمدة.

