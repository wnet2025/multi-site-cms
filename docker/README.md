# بيئة Docker للمرحلة 2

تتكون البيئة من ثلاث خدمات:

- `app`: PHP 8.3-FPM مع `pdo_mysql`.
- `web`: Nginx، والمنفذ المحلي التطويري `8081` فقط.
- `db`: MySQL 8.4، على شبكة Docker داخلية دون نشر منفذ للمضيف.

صورة `app` مبنية على `serversideup/php:8.3-fpm-alpine` لتوفير PHP-FPM وإضافة `pdo_mysql` الجاهزة دون إضافة إطار عمل أو طبقة ORM.

يقرأ Compose الإعدادات من `.env`. يجب إنشاء `.env` محلياً وعدم رفعه إلى Git. المستخدم `cms_app` ينشأه MySQL بصلاحيات قاعدة `multisite_cms` فقط، بينما `MYSQL_ROOT_PASSWORD` منفصل للإدارة.

## التشغيل

```text
Copy-Item .env.example .env
docker compose build
docker compose up -d
docker compose ps
```

يفتح التطبيق عبر `http://localhost:8081`. لا توجد جداول أو Migrations في هذه المرحلة؛ ملف `public/index.php` اختبار runtime واتصال PDO فقط.

## الإيقاف والاستمرارية

```text
docker compose down
docker compose up -d
```

تستمر بيانات MySQL في Volume باسم `mysql_data`. لا يستخدم `docker compose down -v` إلا عند الرغبة في حذف بيانات البيئة صراحةً.

