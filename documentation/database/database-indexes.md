# فهارس قاعدة البيانات

| الجدول | اسم الفهرس/القيد | الأعمدة | النوع | الاستعلام المدعوم |
|---|---|---|---|---|
| domains | uq_domains_domain | domain | Unique | حل الدومين عالمياً |
| domains | ix_domains_website | website_id | عادي | دومينات الموقع |
| domains | ix_domains_primary | website_id,is_primary | عادي | تحديد الأساسي؛ التحقق من الأساسي في Service |
| domains | uq_domains_website_id_id | website_id,id | Unique | مفتاح مركب لمراجع tenant |
| domains | uq_domains_primary_website | primary_website_id | Unique | يمنع أكثر من دومين أساسي لكل موقع؛ لا يفرض وجوده، وNULL لغير الأساسي |
| templates | uq_templates_template_id | template_id | Unique | تحميل القالب |
| websites | ix_websites_status | status,deleted_at | عادي | المواقع المنشورة |
| websites | ix_websites_template | template_id,template_version | عادي | القالب النشط |
| pages | uq_pages_website_slug | website_id,slug | Unique | صفحة الموقع بالـ slug |
| pages | uq_pages_website_id_id | website_id,id | Unique | مفتاح مركب لمراجع الصفحات |
| pages | ix_pages_published | website_id,status,published_at,deleted_at | عادي | الصفحات المنشورة |
| sections | uq_sections_key | website_id,page_id,section_key,deleted_at | Unique | منع تكرار القسم وترتيبه |
| sections | ix_sections_order | website_id,page_id,display_order | عادي | جلب الأقسام مرتبة |
| media | ix_media_website_created | website_id,created_at | عادي | مكتبة الوسائط |
| media | uq_media_website_id_id | website_id,id | Unique | مفتاح مركب لمراجع tenant |
| navigation_menus | uq_menus_key | website_id,menu_key | Unique | تحميل القائمة |
| navigation_menus | uq_menus_website_id_id | website_id,id | Unique | مفتاح مركب لمراجع القوائم |
| navigation_items | ix_items_menu_order | website_id,menu_id,display_order | عادي | عناصر القائمة مرتبة |
| navigation_items | ix_items_parent | website_id,menu_id,parent_id | عادي | الشجرة الفرعية |
| navigation_items | uq_items_website_id_id | website_id,id | Unique | مفتاح مركب للمرجع الذاتي |
| website_template_settings | uq_wts_active_template | website_id,active_template_id | Unique | إعداد نشط واحد للموقع والقالب؛ العمود مولد وNULL للتاريخي |
| website_settings | uq_settings_key | website_id,setting_key | Unique | جلب الإعدادات |
| schema_migrations | uq_migration_name | migration_name | Unique | منع تنفيذ متكرر |
| schema_migrations | uq_migration_version | version | Unique | ترتيب التنفيذ |

لا تضاف فهارس منفردة تكرر بداية فهرس مركب دون استعلام مستقل يبررها. قيود العزل المركبة تعد جزءاً من FKs وليست فهارس زائدة.

