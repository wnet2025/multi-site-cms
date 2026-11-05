# قاموس البيانات

جميع الجداول InnoDB، وجميع النصوص `utf8mb4`. `id` معرف `BIGINT UNSIGNED` auto-increment ما لم يذكر خلاف ذلك. التواريخ `DATETIME(6)` بتوقيت UTC.

## websites

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| name | VARCHAR(160) | لا | اسم الموقع |
| slug | VARCHAR(160) | لا | معرف داخلي؛ UNIQUE اختياري على مستوى المواقع |
| description | TEXT | نعم | وصف الموقع |
| status | ENUM('draft','published','suspended') | لا / draft | حالة النشر |
| default_locale | VARCHAR(10) | لا / ar | اللغة الافتراضية |
| text_direction | ENUM('rtl','ltr') | لا / rtl | اتجاه العرض |
| timezone | VARCHAR(64) | لا / UTC | المنطقة الزمنية |
| template_id | VARCHAR(100) | لا | معرف القالب النشط، FK إلى templates.template_id |
| template_version | VARCHAR(40) | لا | إصدار القالب النشط |
| created_at, updated_at | DATETIME(6) | لا | timestamps |
| deleted_at | DATETIME(6) | نعم | أرشفة منطقية |

## domains

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | FK مباشر إلى websites(id) |
| domain | VARCHAR(253) | لا | hostname مطبع؛ UNIQUE عالمي |
| is_primary | BOOLEAN | لا / 0 | دومين أساسي |
| primary_website_id | BIGINT UNSIGNED GENERATED ALWAYS AS (...) STORED | مولد | التعبير `CASE WHEN is_primary = 1 THEN website_id ELSE NULL END`; `UNIQUE` يمنع أكثر من دومين أساسي، ولا يفرض وجوده |
| created_at, updated_at | DATETIME(6) | لا | timestamps |

قيد إضافي: `UNIQUE(website_id,id)` متاح عند الحاجة إلى مرجع مركب من جدول مملوك للموقع. يتحقق `WebsiteService` من وجود دومين أساسي قبل تفعيل الموقع، ويمنع إزالة الدومين الأساسي من موقع مفعّل إذا لم يوجد بديل ضمن Transaction.

## templates

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| template_id | VARCHAR(100) | لا | UNIQUE، معرف ثابت |
| name | VARCHAR(160) | لا | الاسم |
| version | VARCHAR(40) | لا | الإصدار |
| directory | VARCHAR(160) | لا | مجلد filesystem آمن |
| description | TEXT | نعم | الوصف |
| status | ENUM('active','inactive') | لا / active | حالة التسجيل |
| created_at, updated_at | DATETIME(6) | لا | timestamps |

## website_template_settings

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | FK مباشر إلى websites(id) |
| template_id | VARCHAR(100) | لا | FK templates.template_id |
| template_version | VARCHAR(40) | لا | إصدار الإعدادات |
| settings | JSON | لا | JSON صالح لكائن إعدادات |
| created_at, updated_at | DATETIME(6) | لا | timestamps |
| deleted_at | DATETIME(6) | نعم | حفظ تاريخي |
| active_template_id | VARCHAR(100) GENERATED ALWAYS AS (...) STORED | مولد | التعبير `CASE WHEN deleted_at IS NULL THEN template_id ELSE NULL END` |

القيد: `UNIQUE(website_id, active_template_id)`. يسمح بتعدد السجلات التاريخية ذات القيمة NULL ويمنع تعدد السجلات النشطة للموقع والقالب نفسه.

## pages

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | FK مباشر إلى websites(id)، مع `UNIQUE(website_id,id)` للـ Composite FKs |
| title | VARCHAR(200) | لا | عنوان الصفحة |
| slug | VARCHAR(160) | لا | مطبع؛ UNIQUE(website_id, slug) |
| status | ENUM('draft','published','archived') | لا / draft | حالة الصفحة |
| seo_title | VARCHAR(255) | نعم | عنوان SEO |
| seo_description | VARCHAR(320) | نعم | وصف SEO |
| published_at | DATETIME(6) | نعم | وقت النشر |
| created_at, updated_at | DATETIME(6) | لا | timestamps |
| deleted_at | DATETIME(6) | نعم | أرشفة |

## sections

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | عزل الموقع، مع `UNIQUE(website_id,id)` |
| page_id | BIGINT UNSIGNED | لا | FK مركب pages(website_id,id) |
| section_key | VARCHAR(100) | لا | معرف القسم داخل الصفحة |
| section_type | VARCHAR(80) | لا | مثل Hero وServices |
| display_order | INT UNSIGNED | لا / 0 | ترتيب العرض |
| visibility | ENUM('visible','hidden') | لا / visible | الظهور |
| content | JSON | لا | محتوى القسم |
| configuration | JSON | لا | إعدادات العرض |
| created_at, updated_at | DATETIME(6) | لا | timestamps |
| deleted_at | DATETIME(6) | نعم | أرشفة |

قيد `UNIQUE(website_id,page_id,section_key,deleted_at)`، وJSON يجب أن يكون object صالحاً ويُتحقق من schema ومراجع media في Service.

## media

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | FK مباشر إلى websites(id)، مع `UNIQUE(website_id,id)` |
| original_filename | VARCHAR(255) | لا | اسم المستخدم الأصلي |
| stored_filename | VARCHAR(255) | لا | اسم آمن وفريد |
| storage_key | VARCHAR(500) | لا | مسار داخل مساحة الموقع |
| mime_type | VARCHAR(100) | لا | نوع MIME مُتحقق منه |
| extension | VARCHAR(20) | لا | امتداد آمن |
| file_size | BIGINT UNSIGNED | لا | حجم بالبايت |
| width, height | INT UNSIGNED | نعم | أبعاد الصور |
| alt_text | VARCHAR(255) | نعم | النص البديل |
| created_at | DATETIME(6) | لا | وقت الإنشاء |
| deleted_at | DATETIME(6) | نعم | أرشفة |

## navigation_menus

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | FK مباشر إلى websites(id) |
| name | VARCHAR(120) | لا | اسم القائمة |
| menu_key | VARCHAR(100) | لا | معرف القائمة |
| location | VARCHAR(80) | نعم | موضع القالب |
| status | ENUM('active','inactive') | لا / active | الحالة |
| created_at, updated_at | DATETIME(6) | لا | timestamps |

قيد `UNIQUE(website_id,menu_key)` و`UNIQUE(website_id,id)`.

## navigation_items

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | عزل الموقع، مع `UNIQUE(website_id,id)` |
| menu_id | BIGINT UNSIGNED | لا | FK مركب إلى navigation_menus |
| parent_id | BIGINT UNSIGNED | نعم | FK مركب ذاتي إلى (website_id,id) |
| title | VARCHAR(160) | لا | النص الظاهر |
| link_type | ENUM('page','external','none') | لا | نوع الرابط |
| page_id | BIGINT UNSIGNED | نعم | FK مركب pages عند link_type=page |
| external_url | VARCHAR(2048) | نعم | URL عند external |
| display_order | INT UNSIGNED | لا / 0 | الترتيب |
| visibility | ENUM('visible','hidden') | لا / visible | الظهور |
| target | ENUM('_self','_blank') | لا / _self | سلوك الفتح |
| created_at, updated_at | DATETIME(6) | لا | timestamps |

تتحقق Service من اتساق link_type، ومنع parent من الإشارة إلى نفسه، ومنع الدورات، مع FK مركب يضمن أن page_id والموقع متطابقان.

## website_settings

| المفتاح | النوع | القيمة | التحقق |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| website_id | BIGINT UNSIGNED | لا | FK مباشر إلى websites(id) |
| setting_key | VARCHAR(100) | لا | مفتاح الإعداد مثل `logo_media_id` أو `brand_colors` |
| setting_value | JSON | لا | قيمة الإعداد؛ تتحقق Service من schema وملكية media |
| created_at, updated_at | DATETIME(6) | لا | timestamps |

القيد `UNIQUE(website_id,setting_key)`. لا توجد أعمدة `logo_media_id` أو `logo_dark_media_id` أو `favicon_media_id` مستقلة؛ جميعها مفاتيح داخل key/value، ومراجع media داخل JSON تتحقق منها Service Layer.

## schema_migrations

| العمود | النوع | Null/Default | القيود والوصف |
|---|---|---|---|
| id | BIGINT UNSIGNED | لا / auto | PK |
| migration_name | VARCHAR(255) | لا | UNIQUE |
| version | BIGINT UNSIGNED | لا | ترتيب التنفيذ؛ UNIQUE |
| checksum | CHAR(64) | لا | سلامة الملف |
| executed_at | DATETIME(6) | لا | وقت النجاح |


