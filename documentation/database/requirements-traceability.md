# مصفوفة تتبع المتطلبات

| Requirement | Source section | Database table/constraint | Implementation responsibility | Validation |
|---|---|---|---|---|
| دومين موحد ومطبع | الخطة 5، Phase 1 §6.2 | `domains.domain`, `UNIQUE(domain)` | Domain Service + Repository | حالات uppercase وwww والنقطة النهائية |
| دومين أساسي وبدائل | الخطة 5، القرار المعتمد | `domains.is_primary`, generated `primary_website_id`, `UNIQUE(primary_website_id)` | Schema + `WebsiteService` + Database Transaction | القاعدة تمنع التعدد؛ Service يفرض الوجود قبل التفعيل ويمنع ترك موقع مفعّل بلا أساسي |
| عزل المواقع | الخطة 6، Phase 1 §7، القرار المعتمد | `website_id` و`UNIQUE(website_id,id)` وComposite FKs | Schema + Services | رفض cross-website references |
| slug داخل الموقع | الخطة 6، Phase 1 §6.5 | `UNIQUE(website_id,slug)` | Page Service | about في موقعين، وتكرار داخل موقع |
| هوية وحالة الموقع | الخطة 6 | `websites` | Website Repository | draft/published/suspended |
| branding وSEO | الخطة 6، القرار المعتمد | `website_settings(setting_key,setting_value JSON)`, `websites.description` | Settings Service | JSON schema وملكية media |
| الصفحات والأقسام | الخطة 6 | `pages`, `sections` | Content Service | ترتيب وJSON صالح |
| وسائط مستقلة | الخطة 6، Phase 1 §6.7 | `media.website_id` | Media Service | نسخ default-media لكل موقع |
| قوائم التنقل | الخطة 6 | `navigation_menus/items` | Navigation Service | FK مركب ومنع الدورات |
| القالب النشط وإصداره | الخطة 7، Phase 1 §6.1/6.4 | `websites.template_id/version` | Website/Template Service | قالب مسجل وصالح |
| حفظ إعدادات القوالب | الخطة 8، القرار المعتمد | `website_template_settings`, generated `active_template_id`, `UNIQUE(website_id,active_template_id)` | Template Service + Schema | سجل نشط واحد وتاريخ محذوف محفوظ |
| ملفات القالب خارج DB | الخطة 7 | `templates.directory` | Template Registry | رفض ملفات غير صالحة |
| Migrations قابلة للتتبع | الخطة 14، Phase 1 §12 | `schema_migrations` | Migration CLI | ترتيب وفشل واستعادة |
| عدم وجود admin_users | الخطة 6/23، Phase 1 §5 | لا جدول مصادقة | Auth configuration | env وpassword_verify |
| InnoDB وutf8mb4 | الخطة 3، Phase 1 §4 | إعداد schema | Migration | فحص metadata |
| الحذف والاحتفاظ | الخطة 15، Phase 1 §10 | `deleted_at` وRESTRICT | Services + DB | منع فقد المراجع |
| الفهارس | الخطة 15، Phase 1 §11 | `database-indexes.md` | Migration | EXPLAIN واستعلامات القبول |

