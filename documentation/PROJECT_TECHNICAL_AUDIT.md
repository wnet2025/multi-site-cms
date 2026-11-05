# Comprehensive Technical Audit Report: Multi-Site CMS

**Project Name:** Multi-Site CMS  
**Project Path:** `D:\Projects\WEB\Multi-Site-CMS`  
**Report File:** `documentation/PROJECT_TECHNICAL_AUDIT.md`  
**Audit Date:** November 4, 2026  
**Auditor:** Antigravity (Google DeepMind)  
**Task Scope:** Architecture, Implementation, Database Isolation, Infrastructure, and Roadmap Audit  
**Operating Mode:** Strictly Non-Destructive Read-Only Verification  

---

## 1. Executive Summary

This document establishes a complete, evidence-based architectural and operational audit of the Multi-Site CMS codebase. The platform is designed as a centralized, high-efficiency engine allowing a single administrator (the system owner) to build, manage, and serve multiple independent tenant websites from one unified codebase and database.

### Core Discoveries:
1. **Business Scope & Philosophy:** The project explicitly excludes SaaS features, public registrations, multi-user tenant dashboards, client billing, and subscription logic. It is strictly an internal operational tool for the system owner to deploy client websites without coding each site from scratch.
2. **Current Development Baseline:**
   - **Phase 1 (Database Design & Relational Architecture):** Fully documented in `documentation/database/` with complete ERD, data dictionary, indexing, and traceability matrices.
   - **Phase 2 (Docker & Infrastructure):** Fully operational. Three Docker containers (`multi-site-cms-app-1`, `multi-site-cms-web-1`, `multi-site-cms-db-1`) are running, healthy, and isolated on an internal bridge network (`cms_internal`).
   - **Phase 3 (Database Migrations & Seeders):** Fully implemented in PHP source code (12 migration scripts, `MigrationRunner`, and `TemplateSeeder`), but **pending execution**. The 12 migrations have not been applied to the live database; only the `schema_migrations` tracking table exists in MySQL with 0 rows executed.
   - **Phase 4 (Core Backend Foundation):** Fully implemented in PHP 8.3 (`src/` and `public/index.php`). Exact-path routing, PSR-free lightweight Request/Response objects, PDO database wrappers, domain normalization, domain resolution, admin authentication via environment variables, CSRF token validation, and information-hiding exception handling are implemented. All 4 unit test suites in `tests/run.php` pass. However, live HTTP domain resolution through Nginx currently returns HTTP 500 because the database tables from Phase 3 are pending migration.
3. **Multi-Tenant Isolation Architecture:** Strong tenant isolation is designed at the schema level using composite unique constraints (`website_id, id`) and composite foreign keys (`(website_id, parent_id) REFERENCES parent_table(website_id, id)`). Tenant context is derived strictly from normalized host headers; client-supplied `website_id` values are never trusted or accepted.
4. **Git Repository Status:** The repository is on branch `master` with **zero commits**. All files are currently untracked working-tree files.

---

## 2. Project Vision and Business Model

### 2.1 Original Purpose and Objectives
The Multi-Site CMS was conceived to eliminate the redundant development overhead of building bespoke websites for every client. The system allows an administrator to enter company identity, contact data, visual branding, pages, content sections, and media into a centralized control panel, select an independent visual template, and immediately publish the site to a designated custom domain.

### 2.2 User Personas and Access Model
- **Central System Owner (Administrator):** The sole authenticated persona. Has complete administrative privileges over all tenant websites, domain assignments, visual templates, and publication states.
- **Site Visitors (Anonymous Public):** Consume published website content via dedicated domains. Visitors have read-only access and cannot interact with the administration API or view unpublished/draft websites.
- **Explicitly Excluded Personas:** Client accounts, company sub-administrators, self-service subscribers, and customer billing managers are strictly out of scope for Version 1.0.

### 2.3 Website Creation and Management Workflow
1. **Creation:** Administrator defines a new website record (business name, slug, timezone, default language, text direction).
2. **Domain Mapping:** Administrator assigns one or more domains to the site, designating exactly one as the primary domain.
3. **Branding & Global Settings:** Visual identity attributes (colors, logos, contact info, social links) are stored in key/value JSON format in `website_settings`.
4. **Structure & Content:** Pages, navigation hierarchies, and modular content blocks (Hero, About, Services, Contact, etc.) are populated.
5. **Template Selection:** A visual template is assigned from the template library without modifying the core system or altering stored content.
6. **Responsive Preview:** Administrator previews the rendered site across mobile, tablet, and desktop viewports using default/placeholder media if needed.
7. **Publishing:** Website status is changed to `published`, making it accessible via its mapped domains.

### 2.4 Relationship Between Central Platform and Tenant Sites
```text
                  Internet / Incoming Requests
                               │
                       DNS A/CNAME Records
                               │
                 Nginx Web Proxy (Port 80/8081)
                               │
            Domain Normalization & Host Header Lookup
                               │
                       DomainResolver
                               │
               Website Context (Tenant Boundary)
                               │
                 ┌─────────────┴─────────────┐
                 ▼                           ▼
          Website Content             Template Engine
     (Pages, Sections, Settings)     (HTML, Tailwind, JS)
                 │                           │
                 └─────────────┬─────────────┘
                               ▼
                        Rendered Website
```
All tenant sites share a single web server, application container, and MySQL database. The database does not create separate databases or schemas per tenant; all tenant data is housed in shared tables segregated by `website_id`.

### 2.5 Documentation Discrepancies and Integrity
- **Primary Specification:** `Plans/Multi-Site-CMS-Project-Plan.md` (1,180 lines, version 1.0, dated Nov 3, 2026) is the authoritative, comprehensive roadmap.
- **Corrupted Specification:** The file `Plans/ 袟...md` contains corrupted Arabic characters (`0xEF 0xBF 0xBD`), indicating an encoding or export error.
- **Database Specifications:** `documentation/database/` contains 7 markdown and Mermaid files detailing the exact schema, indexing, architectural decisions, and requirement mappings. These documents completely align with the Phase 3 migration code.
- **Backend Specification:** `documentation/backend/core-backend.md` accurately describes the Phase 4 implementation.
- **Directory Inconsistency:** Requirements mention `docs/`, whereas the existing project tree utilizes `documentation/`.

---

## 3. Complete Project Structure

```text
D:\Projects\WEB\Multi-Site-CMS\
│
├── .env                                # Local environment secrets (ignored by Git)
├── .env.example                        # Documented environment template with placeholders
├── .gitignore                          # Git ignore rules (.env, vendor, node_modules, storage, *.log)
├── docker-compose.yml                  # Multi-container Compose definition (app, web, db)
│
├── bin/
│   └── console                         # CLI executable for migrations and template seeding
│
├── config/
│   └── bootstrap.php                   # Bootstrapper, manual autoloader, environment validation, DI
│
├── database/
│   ├── migrations/                     # 12 sequential PHP DDL migration scripts
│   │   ├── 202611040001_create_schema_migrations.php
│   │   ├── 202611040002_create_templates.php
│   │   ├── 202611040003_create_websites.php
│   │   ├── 202611040004_create_domains.php
│   │   ├── 202611040005_create_pages.php
│   │   ├── 202611040006_create_website_template_settings.php
│   │   ├── 202611040007_create_media.php
│   │   ├── 202611040008_create_sections.php
│   │   ├── 202611040009_create_navigation_menus.php
│   │   ├── 202611040010_create_navigation_items.php
│   │   ├── 202611040011_create_website_settings.php
│   │   └── 202611040012_add_integrity_checks.php
│   └── seeders/
│       └── TemplateSeeder.php          # Discovers and upserts template.json metadata into MySQL
│
├── docker/
│   ├── README.md                       # Phase 2 operational documentation
│   ├── nginx/
│   │   └── default.conf                # Virtual host configuration for Nginx
│   └── php/
│       └── Dockerfile                  # Custom PHP-FPM container image definition
│
├── documentation/
│   ├── backend/
│   │   └── core-backend.md             # Core backend specification and API inventory
│   └── database/
│       ├── data-dictionary.md          # Complete column and constraint dictionary
│       ├── database-decisions.md       # Rationale for tenant keys, generated columns, soft delete
│       ├── database-design.md          # Architectural principles and relational rules
│       ├── database-indexes.md         # Index catalog and query patterns
│       ├── erd.mmd                     # Mermaid ERD diagram
│       ├── migrations-plan.md          # Ordered migration execution plan
│       └── requirements-traceability.md# Traceability matrix mapping plan to schema
│
├── Plans/
│   ├── Multi-Site-CMS-Project-Plan.md  # Master 12-phase project plan
│   └──  袟...md                       # Corrupted encoding secondary plan file
│
├── public/
│   └── index.php                       # HTTP front controller and exception safety boundary
│
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php          # Handles /admin/api/login, logout, and status
│   │   └── PublicResolutionController.php # Handles domain resolution on GET /
│   ├── Database/
│   │   ├── Connection.php              # PDO connection factory
│   │   └── MigrationRunner.php         # Custom DDL migration runner and checksum validator
│   ├── Exceptions/
│   │   ├── AuthenticationException.php # HTTP 401 Unauthorized
│   │   ├── DomainNotFoundException.php # HTTP 404 Domain Not Found
│   │   ├── HttpException.php           # Base HTTP exception with status code
│   │   └── WebsiteUnavailableException.php # HTTP 404 Website Unpublished or Archived
│   ├── Http/
│   │   ├── JsonResponse.php            # JSON response wrapper with strict encoding
│   │   ├── Request.php                 # Immutable HTTP request representation
│   │   └── Response.php                # Base HTTP response with headers and status
│   ├── Repositories/
│   │   ├── DomainRepositoryInterface.php   # Domain lookup contract
│   │   ├── PdoDomainRepository.php         # PDO implementation of domain lookup
│   │   ├── PdoWebsiteRepository.php        # PDO implementation of website eligibility lookup
│   │   └── WebsiteRepositoryInterface.php  # Website eligibility contract
│   ├── Routing/
│   │   └── Router.php                  # Exact-path router with 404/405 detection
│   ├── Services/
│   │   ├── AdminAuthenticationService.php  # Environment-based auth and session security
│   │   ├── CsrfService.php                 # Cryptographic token generator and validator
│   │   ├── DomainNormalizer.php            # Hostname canonicalization and RFC validation
│   │   └── DomainResolver.php              # Domain-to-website resolution service
│   └── Support/
│       └── Configuration.php           # Environment variable validation and hash checking
│
├── templates/                          # Template discovery directory (currently empty)
└── tests/
    └── run.php                         # Custom test runner for unit and security verification
```

### Architectural Responsibilities of Major Components:
- **`public/index.php`:** Serves as the single HTTP entry point. Captures global state, initializes the DI container via `config/bootstrap.php`, dispatches the request through `Router`, and wraps execution in a catch-all block that returns sanitized JSON errors while logging full stack traces internally.
- **`config/bootstrap.php`:** Explicitly requires all class files (eliminating runtime file resolution overhead and autoloader magic), validates essential environment variables, opens a PDO connection, and injects dependencies into controllers and services.
- **`src/Services/DomainNormalizer.php` & `src/Services/DomainResolver.php`:** The core tenant resolution boundary. Sanitizes the HTTP `Host` header against injection attacks and resolves the corresponding active website record.
- **`src/Services/AdminAuthenticationService.php`:** Centralizes authentication logic without database reliance, reading credentials from environment variables, validating password hashes, configuring strict session cookie parameters, and preventing session fixation.
- **`src/Database/MigrationRunner.php`:** Manages deterministic execution of database migrations with SHA-256-style checksum verification and timestamp logging in `schema_migrations`.
- **`bin/console`:** CLI entry point providing `db:migrate`, `db:migrate:status`, and `db:seed` commands for safe out-of-band operational execution.

---

## 4. Technical Stack and Dependencies

### 4.1 Identified Stack Components and Runtime Measurements

| Component | Documented Target | Measured Runtime Version | Container / Environment | Status |
|---|---|---|---|---|
| **PHP Runtime** | PHP 8.3+ | PHP 8.3.36 (cli/fpm) (built Oct 21 2026) | `multi-site-cms-app-1` | Confirmed / Active |
| **MySQL Server** | MySQL 8.4 LTS | MySQL 8.4.13 Community Server | `multi-site-cms-db-1` | Confirmed / Active |
| **Nginx Web Server** | Nginx 1.27-alpine | Nginx 1.27.5 | `multi-site-cms-web-1` | Confirmed / Active |
| **Composer** | Composer 2.x | Composer 2.11.1 (inside image) | `multi-site-cms-app-1` | Present in image, unused in code |
| **Node.js / npm** | Node / npm | **Not Installed** | None | Absent |
| **Testing Tool** | PHPUnit / Playwright | Custom Pure-PHP Runner | `tests/run.php` | Operational |

### 4.2 Dependency and Package Analysis
- **PHP External Dependencies:** **Zero.** The repository contains no `composer.json`, `composer.lock`, or `vendor/` directory. All application features utilize native PHP 8.3 standard library modules.
- **PHP Modules Verified:** `Core`, `ctype`, `curl`, `date`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `mysqlnd`, `openssl`, `pcntl`, `pcre`, `PDO`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `Phar`, `posix`, `random`, `readline`, `redis`, `Reflection`, `session`, `SimpleXML`, `sodium`, `SPL`, `sqlite3`, `standard`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `Zend OPcache`, `zip`, `zlib`.
- **Frontend Dependencies:** Currently none. The plan specifies pure HTML5, modern vanilla JavaScript, and compiled Tailwind CSS. The compilation pipeline (Node/npm/Tailwind CLI) has not yet been introduced.

---

## 5. Architecture Review

### 5.1 Application Entry Point & Request Lifecycle
```mermaid
sequenceDiagram
    autonumber
    actor Client as Web Client / Browser
    participant Nginx as Nginx (web:8081)
    participant FPM as PHP-FPM (app:9000)
    participant Index as public/index.php
    participant Bootstrap as config/bootstrap.php
    participant Router as Router
    participant Controller as Auth / Resolution Controller
    participant Service as Business Services
    participant Repo as PdoRepositories
    participant DB as MySQL (db:3306)

    Client->>Nginx: HTTP Request (e.g. GET / with Host: good.test)
    alt Healthcheck Endpoint
        Nginx-->>Client: 200 "ok" (handled directly at Nginx layer)
    else Dynamic Route
        Nginx->>FPM: FastCGI pass (SCRIPT_FILENAME=/var/www/html/public/index.php)
        FPM->>Index: Execute public/index.php
        Index->>Bootstrap: require config/bootstrap.php
        Bootstrap->>DB: Instantiate PDO Connection (Connection::fromEnvironment)
        Bootstrap-->>Index: Initialized services & controllers
        Index->>Router: dispatch(Request::fromGlobals())
        Router->>Controller: Route match -> invoke Controller action
        Controller->>Service: Call Service business method
        Service->>Repo: Query via prepared statement
        Repo->>DB: Execute SQL query
        DB-->>Repo: Result row / null
        Repo-->>Service: Return entity / null
        Service-->>Controller: Return validated result or throw HttpException
        Controller-->>Index: Return JsonResponse
        Index->>Client: Send HTTP status, headers, and JSON body
    end
```

### 5.2 Routing System
- **Registration:** Handled imperatively in `public/index.php`:
  ```php
  $router->add('GET', '/', [$publicController, 'resolve']);
  $router->add('POST', '/admin/api/login', [$authController, 'login']);
  $router->add('POST', '/admin/api/logout', [$authController, 'logout']);
  $router->add('GET', '/admin/api/auth/status', [$authController, 'status']);
  ```
- **Matching Mechanics:** Evaluates `$path === $request->path()`.
- **Method Checking:** If the path matches but the HTTP verb does not, flags `$pathMatch = true` and throws `HttpException('Method not allowed.', 405)`. Verified via runtime probe returning 405.
- **404 Handling:** If no route path matches, throws `HttpException('Not found.', 404)`. Verified via runtime probe returning 404.
- **Architectural Limitations:** `Router` supports only literal string equality. It possesses no parameter extraction (`/websites/{id}`) or regex pattern matching.

### 5.3 Application Layers and Separation of Concerns
- **Controllers (`src/Controllers/`):** Pure HTTP coordination. They do not contain business validation, database queries, or raw SQL. They extract request bodies and headers, invoke services, and wrap results in `JsonResponse`.
- **Services (`src/Services/`):** House all business logic:
  - `DomainNormalizer`: Validates and sanitizes hostnames.
  - `DomainResolver`: Coordinates domain and website repositories.
  - `AdminAuthenticationService`: Enforces login credentials, hash matching, and cookie parameters.
  - `CsrfService`: Issues and verifies cryptographic tokens.
- **Repositories (`src/Repositories/`):** Pure data access. Implement interfaces (`DomainRepositoryInterface`, `WebsiteRepositoryInterface`). Execute strict PDO prepared statements.
- **Models/Entities:** Not implemented as active-record classes; entities are passed as associative arrays representing database records.
- **Dependency Injection:** Configured cleanly in `config/bootstrap.php` with constructor injection.

### 5.4 Error Handling and Information Disclosure
- **Catch-All Handler:** In `public/index.php`, all execution is wrapped in `try { ... } catch (Throwable $exception)`.
- **Information Masking:** For any exception with status code >= 500 (or non-`HttpException` such as `PDOException`), the client receives:
  ```json
  {"error":{"message":"Internal server error."}}
  ```
- **Error Logging:** Full internal details, file paths, line numbers, and SQL state traces are sent to `error_log()` (standard error / Docker container logs).
- **Client Error Verification:** Verified via live HTTP probing. When `GET /` triggers a missing table `PDOException`, the client receives HTTP 500 with the sanitized error message above, and zero database details leak to the client.

---

## 6. Database Architecture

### 6.1 Schema Design and Entity Overview

The schema is defined across 12 sequential migration files in `database/migrations/`:

```mermaid
erDiagram
    websites ||--o{ domains : "has many (owns)"
    websites ||--o{ pages : "has many (owns)"
    websites ||--o{ media : "has many (owns)"
    websites ||--o{ navigation_menus : "has many (owns)"
    websites ||--o{ navigation_items : "has many (owns)"
    websites ||--o{ website_template_settings : "has many"
    websites ||--o{ website_settings : "has many"
    templates ||--o{ websites : "assigned to"
    templates ||--o{ website_template_settings : "configured in"
    pages ||--o{ sections : "contains"
    navigation_menus ||--o{ navigation_items : "contains"
    pages ||--o{ navigation_items : "linked by"
    navigation_items ||--o{ navigation_items : "parent of"

    websites {
        BIGINT id PK
        VARCHAR name
        VARCHAR slug
        TEXT description
        ENUM status "draft, published, suspended"
        VARCHAR default_locale
        ENUM text_direction "rtl, ltr"
        VARCHAR timezone
        VARCHAR template_id FK
        VARCHAR template_version
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at
    }

    domains {
        BIGINT id PK
        BIGINT website_id FK
        VARCHAR domain UK "global unique"
        BOOLEAN is_primary
        BIGINT primary_website_id UK "generated: case when is_primary=1 then website_id end"
        DATETIME created_at
        DATETIME updated_at
    }

    templates {
        BIGINT id PK
        VARCHAR template_id UK
        VARCHAR name
        VARCHAR version
        VARCHAR directory
        TEXT description
        ENUM status "active, inactive"
        DATETIME created_at
        DATETIME updated_at
    }

    pages {
        BIGINT id PK
        BIGINT website_id FK
        VARCHAR title
        VARCHAR slug "UK: (website_id, slug)"
        ENUM status "draft, published, archived"
        VARCHAR seo_title
        VARCHAR seo_description
        DATETIME published_at
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at
    }

    sections {
        BIGINT id PK
        BIGINT website_id FK
        BIGINT page_id FK "Composite FK: (website_id, page_id) -> pages(website_id, id)"
        VARCHAR section_key
        VARCHAR section_type
        INT display_order
        ENUM visibility "visible, hidden"
        JSON content
        JSON configuration
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at
    }

    navigation_menus {
        BIGINT id PK
        BIGINT website_id FK
        VARCHAR name
        VARCHAR menu_key "UK: (website_id, menu_key)"
        VARCHAR location
        ENUM status "active, inactive"
        DATETIME created_at
        DATETIME updated_at
    }

    navigation_items {
        BIGINT id PK
        BIGINT website_id FK
        BIGINT menu_id FK "Composite FK: (website_id, menu_id) -> navigation_menus(website_id, id)"
        BIGINT parent_id FK "Composite FK: (website_id, parent_id) -> navigation_items(website_id, id)"
        VARCHAR title
        ENUM link_type "page, external, none"
        BIGINT page_id FK "Composite FK: (website_id, page_id) -> pages(website_id, id)"
        VARCHAR external_url
        INT display_order
        ENUM visibility "visible, hidden"
        ENUM target "_self, _blank"
        DATETIME created_at
        DATETIME updated_at
    }

    website_template_settings {
        BIGINT id PK
        BIGINT website_id FK
        VARCHAR template_id FK
        VARCHAR template_version
        JSON settings
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at
        VARCHAR active_template_id UK "generated: case when deleted_at is null then template_id end"
    }

    website_settings {
        BIGINT id PK
        BIGINT website_id FK
        VARCHAR setting_key "UK: (website_id, setting_key)"
        JSON setting_value
        DATETIME created_at
        DATETIME updated_at
    }

    media {
        BIGINT id PK
        BIGINT website_id FK
        VARCHAR original_filename
        VARCHAR stored_filename
        VARCHAR storage_key
        VARCHAR mime_type
        VARCHAR extension
        BIGINT file_size
        INT width
        INT height
        VARCHAR alt_text
        DATETIME created_at
        DATETIME deleted_at
    }

    schema_migrations {
        BIGINT id PK
        VARCHAR migration_name UK
        BIGINT version UK
        CHAR checksum
        DATETIME executed_at
    }
```

### 6.2 Multi-Tenant Database Isolation Analysis
1. **Root Hierarchy:** The `websites` table is the tenant root. All tenant-scoped entities contain a direct `website_id` column.
2. **Composite Foreign Keys:** Prevent cross-tenant data corruption:
   - `sections`: `FOREIGN KEY(website_id, page_id) REFERENCES pages(website_id, id) ON UPDATE CASCADE ON DELETE RESTRICT`. A section cannot point to a page belonging to a different website.
   - `navigation_items`:
     - `FOREIGN KEY(website_id, menu_id) REFERENCES navigation_menus(website_id, id)`
     - `FOREIGN KEY(website_id, parent_id) REFERENCES navigation_items(website_id, id)`
     - `FOREIGN KEY(website_id, page_id) REFERENCES pages(website_id, id)`
     All relationships guarantee that menu trees, parent-child items, and linked target pages reside within the exact same tenant boundary.
3. **Tenant-Scoped Uniqueness:**
   - `pages`: `UNIQUE KEY uq_pages_website_slug (website_id, slug)` allows independent websites to both use `about` or `contact` without collisions.
   - `navigation_menus`: `UNIQUE KEY uq_menus_key (website_id, menu_key)`
   - `website_settings`: `UNIQUE KEY uq_settings_key (website_id, setting_key)`
4. **Single Primary Domain Enforcement:**
   - In `domains`, column `primary_website_id` is defined as:
     ```sql
     primary_website_id BIGINT UNSIGNED GENERATED ALWAYS AS (
         CASE WHEN is_primary = 1 THEN website_id ELSE NULL END
     ) STORED,
     UNIQUE KEY uq_domains_primary_website (primary_website_id)
     ```
     Because MySQL allows multiple `NULL` values in a unique index, any number of alias domains (`is_primary = 0`) can exist per website, but exactly one domain per website can have `is_primary = 1`.
5. **Active Template Configuration Enforcement:**
   - In `website_template_settings`, column `active_template_id` is defined as:
     ```sql
     active_template_id VARCHAR(100) GENERATED ALWAYS AS (
         CASE WHEN deleted_at IS NULL THEN template_id ELSE NULL END
     ) STORED,
     UNIQUE KEY uq_wts_active_template (website_id, active_template_id)
     ```
     Guarantees that each website has at most one active configuration per template, while preserving historical soft-deleted configurations for instant rollback.
6. **Data Leakage Risk in Media:** `media` has `KEY ix_media_website_created (website_id, created_at)` and `FOREIGN KEY (website_id) REFERENCES websites(id)`, but does **not** declare `UNIQUE KEY (website_id, id)`. Media references placed inside JSON fields (`sections.content` or `website_settings.setting_value`) cannot be verified via foreign keys; the application layer (`SectionService`) must explicitly validate media ownership prior to saving.

### 6.3 Database Safety, Configuration, and Migration Tracking
- **Configuration Loading:** Loaded via environment variables (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) in `src/Database/Connection.php`.
- **Migration Execution:** Executed out-of-band via CLI: `php bin/console db:migrate`. There are no automated migration calls on application startup or during HTTP requests.
- **Tracking & Integrity:** `schema_migrations` records `migration_name`, numeric `version`, 64-character `checksum`, and `executed_at`. If a file's checksum differs from its recorded execution, `MigrationRunner` aborts execution.
- **Seeder Safety:** `TemplateSeeder` uses `ON DUPLICATE KEY UPDATE` keyed on `template_id`. It is idempotent and can be safely re-run without duplicating records or wiping site content.

### 6.4 Current Database State
- **Configured Database:** `multisite_cms` (MySQL 8.4 LTS).
- **Migration Status:** Running `php bin/console db:migrate:status` inside the container reports all 12 migrations as `pending`.
- **Live Tables:** Only `schema_migrations` exists in the database (created when `db:migrate:status` initialized the tracking table); it currently contains 0 rows. Business tables (`websites`, `domains`, `templates`, etc.) do not yet exist in MySQL.

---

## 7. Multi-Site and Domain Resolution

### 7.1 Detailed 10-Step Resolution Flow
1. **Host Header Extraction:** `Request::fromGlobals()` reads `$_SERVER['HTTP_HOST']`.
2. **Input Sanitization:** `DomainNormalizer::normalize()` trims leading and trailing whitespace.
3. **Malicious Input Rejection:** Rejects empty values, path separators (`/`), query indicators (`?`), hash fragments (`#`), port separators (`:`), and explicit URI schemes (`http://`, `https://`) with `HttpException('Invalid host.', 400)`.
4. **Lowercasing:** Converts all characters to lowercase (`strtolower`).
5. **DNS Trailing Dot Removal:** Strips root domain trailing dots via `rtrim($host, '.')`.
6. **Subdomain Canonicalization:** Strips leading `www.` prefix via `substr($host, 4)`.
7. **RFC Hostname Validation:** Enforces length limit (253 characters) and validates against RFC syntax:
   ```regex
   /^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/
   ```
8. **Domain Record Lookup:** `PdoDomainRepository::findByNormalizedDomain()` executes:
   ```sql
   SELECT id, website_id, domain, is_primary FROM domains WHERE domain = :domain LIMIT 1
   ```
   If no record matches, throws `DomainNotFoundException` (HTTP 404).
9. **Website Eligibility Verification:** `PdoWebsiteRepository::findEligibleById()` executes:
   ```sql
   SELECT id, name, status, default_locale, text_direction, timezone, template_id, template_version 
   FROM websites 
   WHERE id = :id AND deleted_at IS NULL AND status = 'published' 
   LIMIT 1
   ```
   If the website record is missing, soft-deleted (`deleted_at IS NOT NULL`), or in `draft`/`suspended` status, throws `WebsiteUnavailableException` (HTTP 404).
10. **Context Exposure:** `DomainResolver` returns `['domain' => $domain, 'website' => $website]`, which `PublicResolutionController` returns as JSON.

### 7.2 Security Verification
- **Host Header Poisoning:** Mitigated by strict regex validation and mandatory database matching against pre-registered domains.
- **Untrusted Client Inputs:** The resolution flow accepts no user-supplied `website_id`. The tenant context is derived entirely from the validated domain.
- **Host Port Edge Case:** Because `DomainNormalizer` rejects `:` with HTTP 400, requests containing port numbers (e.g., `localhost:8081` in local testing) will be rejected unless Nginx or `DomainNormalizer` strips the port prior to normalization.

---

## 8. Authentication and Authorization

### 8.1 Authentication Architecture and Session Lifecycle
- **Centralized Admin Model:** No user tables exist in MySQL. The system owner's credentials exist strictly in environment variables:
  - `ADMIN_USERNAME`: Admin login name.
  - `ADMIN_PASSWORD_HASH`: Pre-computed password hash. `Configuration::fromEnvironment()` verifies at startup that the hash is valid (`password_get_info()['algo'] !== 0`).
- **Login Flow (`AuthController::login` -> `AdminAuthenticationService::login`):**
  1. Compares incoming username using timing-attack-safe `hash_equals()`.
  2. Verifies incoming password against stored hash using `password_verify()`.
  3. On mismatch, throws `AuthenticationException` (HTTP 401).
  4. On success, regenerates session ID via `session_regenerate_id(true)` to eliminate session fixation vulnerabilities.
  5. Stores authentication state: `$_SESSION['admin_authenticated'] = true` and `$_SESSION['authenticated_at'] = time()`.
  6. Returns HTTP 200 with JSON payload containing authenticated status and an active CSRF token.
- **Session Configuration (`AdminAuthenticationService::configureSession`):**
  - `session.cookie_httponly = 1`: Prevents JavaScript access to the session cookie.
  - `session.cookie_samesite = 'Lax'`: Mitigates Cross-Site Request Forgery.
  - `session.cookie_secure`: Evaluated dynamically; sets `1` when `$_SERVER['HTTPS']` is active, `0` otherwise.
  - `session.gc_maxlifetime`: Initialized from `SESSION_LIFETIME` (default: 3600 seconds, minimum: 300 seconds).
- **Logout Flow (`AuthController::logout` -> `AdminAuthenticationService::logout`):**
  1. Validates CSRF token via `CsrfService::validate()`.
  2. Clears session data: `$_SESSION = []`.
  3. Overwrites and expires the session cookie with a past timestamp (`time() - 42000`).
  4. Destroys the session via `session_destroy()`.

### 8.2 Authorization
- **Implementation State:** Binary administrator check only.
- **Mechanism:** Evaluated via `AdminAuthenticationService::isAuthenticated()` checking `($_SESSION['admin_authenticated'] ?? false) === true`.
- **Role-Based Access Control (RBAC):** Not implemented. There are no secondary roles, permissions, or multi-tenant user hierarchies.

---

## 9. Security Audit

| Security Domain | Mechanism | Implementation & Verification Evidence | Status |
|---|---|---|---|
| **SQL Injection** | PDO Prepared Statements | All queries in `PdoWebsiteRepository` and `PdoDomainRepository` use parameterized queries with named bindings. | **Implemented & Verified** |
| **Session Fixation** | ID Regeneration | `session_regenerate_id(true)` executed upon successful authentication. Verified in `tests/run.php`. | **Implemented & Verified** |
| **Session Cookie Security** | HttpOnly, SameSite, Secure | Enforced in `AdminAuthenticationService::configureSession()`. Verified via live HTTP response headers showing `HttpOnly; SameSite=Lax`. | **Implemented & Verified** |
| **CSRF Protection** | Cryptographic Tokens | 32-byte random hex tokens generated via `random_bytes(32)`. Validated using `hash_equals()`. Missing/invalid token returns HTTP 419. Verified via live HTTP probe. | **Implemented & Verified** |
| **Host Header Injection** | Domain Normalization | `DomainNormalizer` strips `www.` and trailing dots, rejects malicious characters, validates RFC regex, and matches against registered database domains. | **Implemented & Verified** |
| **Password Security** | Standard Hash Functions | Evaluated via `password_verify()`. Hashes validated at startup via `password_get_info()`. No plaintext passwords stored. | **Implemented & Verified** |
| **Error Disclosure** | Generic JSON Responses | Internal SQL/system exceptions masked behind `{"error":{"message":"Internal server error."}}` with status 500. Traces directed exclusively to logs. Verified via live HTTP probe. | **Implemented & Verified** |
| **Tenant Isolation** | Schema Constraints | Composite foreign keys and tenant-scoped unique constraints defined in migrations. | **Implemented in Code; Pending DB Execution** |
| **XSS Protection** | Output Escaping | All responses currently output as `application/json` with `JSON_UNESCAPED_UNICODE`. HTML template rendering engine is scheduled for Phase 7. | **Partially Implemented** |
| **Rate Limiting** | Brute Force Protection | No rate limiting middleware or IP throttling implemented on `/admin/api/login`. | **Not Implemented** |
| **File Upload Security** | MIME & Extension Checks | Upload handling scheduled for Phase 6 (`media` management). | **Not Implemented** |
| **Container Isolation** | Unprivileged Execution | PHP-FPM runs as unprivileged user `www-data`. MySQL port 3306 is not published to host. | **Implemented & Verified** |

---

## 10. API and HTTP Endpoints

| HTTP Method | Endpoint | Controller Action | Auth Required | CSRF Required | Purpose | Current Runtime Status |
|---|---|---|---|---|---|---|
| `GET` | `/healthz` | Nginx built-in | No | No | Nginx healthcheck | **HTTP 200 OK** ("ok\n") |
| `GET` | `/` | `PublicResolutionController@resolve` | No | No | Domain-to-website resolution | **HTTP 500** (DB tables pending) |
| `POST` | `/admin/api/login` | `AuthController@login` | No | No | Authenticate admin; issue CSRF | **HTTP 200 / 401** (Operational) |
| `GET` | `/admin/api/auth/status` | `AuthController@status` | No | No | Check session state & get CSRF | **HTTP 200 OK** (Operational) |
| `POST` | `/admin/api/logout` | `AuthController@logout` | No (requires session) | Yes (`X-CSRF-Token` or `_csrf`) | Destroy session & expire cookie | **HTTP 200 / 419** (Operational) |

### Request and Response Payload Structures:
- **`POST /admin/api/login` Request:**
  ```json
  {"username": "admin", "password": "secret-password"}
  ```
  **Success Response (HTTP 200):**
  ```json
  {"authenticated": true, "csrf_token": "a1b2c3d4..."}
  ```
  **Failure Response (HTTP 401):**
  ```json
  {"error": {"message": "Invalid credentials."}}
  ```
- **`GET /admin/api/auth/status` Response (HTTP 200):**
  ```json
  {"authenticated": false, "csrf_token": null}
  ```
- **`POST /admin/api/logout` Failure on Missing CSRF (HTTP 419):**
  ```json
  {"error": {"message": "Invalid CSRF token."}}
  ```

---

## 11. Docker and Infrastructure

### 11.1 Container Communication and Roles
```text
[Host Machine (Browser/Curl)]
       │
       │ Port 8081:80
       ▼
┌────────────────────────────────────────────────────────┐
│ web (multi-site-cms-web-1)                             │
│ Image: nginx:1.27-alpine                               │
│ Configuration: docker/nginx/default.conf               │
│ Role: Ingress, /healthz endpoint, static files, proxy  │
└──────────────────────────┬─────────────────────────────┘
                           │ FastCGI (port 9000)
                           │ Network: cms_internal
                           ▼
┌────────────────────────────────────────────────────────┐
│ app (multi-site-cms-app-1)                             │
│ Image: multi-site-cms-app (PHP 8.3-FPM Alpine)         │
│ User: www-data (UID 82)                                │
│ Healthcheck: php-fpm -t (every 10s)                    │
│ Mounts: Source code (:ro), app-storage (:rw)           │
└──────────────────────────┬─────────────────────────────┘
                           │ MySQL Protocol (port 3306)
                           │ Network: cms_internal
                           ▼
┌────────────────────────────────────────────────────────┐
│ db (multi-site-cms-db-1)                               │
│ Image: mysql:8.4 LTS                                   │
│ Healthcheck: mysqladmin ping (every 10s)               │
│ Storage: mysql_data volume -> /var/lib/mysql           │
│ Access: Internal only (port 3306 not bound to host)    │
└────────────────────────────────────────────────────────┘
```

### 11.2 Live Container Inspection
- `multi-site-cms-app-1`: Status `Up (healthy)`, Ports: `9000/tcp`.
- `multi-site-cms-web-1`: Status `Up (healthy)`, Ports: `0.0.0.0:8081->80/tcp`.
- `multi-site-cms-db-1`: Status `Up (healthy)`, Ports: `3306/tcp, 33060/tcp` (internal).

### 11.3 Storage & Volumes
- `mysql_data`: Named Docker volume backing `/var/lib/mysql` for persistent database storage.
- `app-storage`: Named Docker volume backing `/var/www/html/storage` for persistent runtime files and uploaded media.
- Source directories (`public`, `config`, `src`, `database`, `bin`, `tests`, `templates`) are mounted read-only (`:ro`) into the `app` container, preventing web server compromise from modifying application code on disk.

---

## 12. Testing and Verification Inventory

| Test Area | Existing Test Implementation | Coverage & Scenarios | Execution Status | Missing Test Coverage |
|---|---|---|---|---|
| **Domain Normalization** | `tests/run.php` | Normalization of `WWW.Good.Test.` to `good.test`; rejection of schemes, paths, queries, ports, and invalid characters with HTTP 400 | **PASS** (Verified in container) | Full RFC 1035 / 1123 edge-case suite |
| **Domain Resolution & Tenant Context** | `tests/run.php` | Resolution of published site; 404 on unknown domain, unpublished site, or archived site; verifies client `website_id` is ignored | **PASS** (Verified in container) | Multi-domain alias mapping, primary domain switching |
| **CSRF Lifecycle** | `tests/run.php` | Token generation (64 chars); rejection of null and invalid tokens (419); acceptance of valid token | **PASS** (Verified in container) | Header vs body precedence, token expiration |
| **Authentication & Session** | `tests/run.php` | Cookie security flags (`HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS); session regeneration; logout invalidation; wrong password rejection (401) | **PASS** (Verified in container) | Session timeout expiration, concurrent session behavior |
| **Live HTTP Endpoints** | Manual runtime probe | Probed `/healthz`, `/admin/api/auth/status`, `/admin/api/login`, `/admin/api/logout`, `/not-found` | **VERIFIED** | Automated HTTP/API test suite |
| **Database Migrations** | None | Schema creation, rollback, foreign key integrity | **NOT VERIFIED** (0 migrations executed) | Automated migration test suite against MySQL |
| **End-to-End / Responsive**| None | Visual layout rendering across 320px–1920px viewports | **NOT IMPLEMENTED** | Playwright test suite |

### Verified Test Output (`docker exec multi-site-cms-app-1 php /var/www/html/tests/run.php`):
```text
PASS domain normalization
PASS domain resolution and tenant context
PASS csrf lifecycle
PASS authentication and session settings
RESULT passed=4 failed=0
```

---

## 13. Phase-by-Phase Implementation Audit

### Phase 1: Database Design & Relational Architecture
- **Planned:** Entities, relationships, constraints, indexes, ERD, data dictionary, traceability matrix.
- **Implemented:** Fully detailed in `documentation/database/` across 7 documents.
- **Verification Evidence:** ERD (`erd.mmd`), data dictionary, index specs, and decisions documents present and consistent.
- **Status:** **COMPLETE AND VERIFIED**

### Phase 2: Docker Environment & Infrastructure Setup
- **Planned:** `Dockerfile`, `docker-compose.yml`, PHP-FPM, Nginx, MySQL 8.4, volumes, healthchecks, documentation.
- **Implemented:** Configured in `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`, and `docker/README.md`.
- **Verification Evidence:** All 3 containers running and reporting healthy. HTTP `/healthz` returns 200 "ok".
- **Status:** **COMPLETE AND VERIFIED**

### Phase 3: Migrations & Database Implementation
- **Planned:** `schema_migrations` tracking, 12 DDL migrations, composite foreign keys, seeders, CLI commands.
- **Implemented:** 12 migration files in `database/migrations/`, `MigrationRunner`, `TemplateSeeder`, and `bin/console`.
- **Verification Evidence:** `php bin/console db:migrate:status` reports all 12 migrations as `pending`. No business tables exist in MySQL.
- **Status:** **IMPLEMENTED BUT NOT FULLY VERIFIED** (Code complete; awaiting authorized migration execution).

### Phase 4: Core Backend Foundation
- **Planned:** Routing, request lifecycle, PDO connection, controllers, domain resolution, auth, sessions, CSRF, error handling.
- **Implemented:** Full MVC foundation in `src/` and `public/index.php`.
- **Verification Evidence:** Unit test suite passes 4/4. Live HTTP endpoints respond correctly for auth and health. Public resolution endpoint throws 500 solely because Phase 3 migrations have not been applied.
- **Status:** **IMPLEMENTED BUT NOT FULLY VERIFIED** (Blocked by unexecuted Phase 3 migrations).

---

## 14. Original Roadmap and Future Phases

From `Plans/Multi-Site-CMS-Project-Plan.md`:

| Phase | Title | Objectives & Deliverables | Dependencies | Key Architectural Considerations |
|---|---|---|---|---|
| **Phase 5** | Basic Admin Panel | UI dashboard, Website CRUD, Domain management, Branding/identity forms, General settings | Phase 4 | Requires router expansion, admin API endpoints, HTML/CSS admin views |
| **Phase 6** | Content & Media Management | Page & section management, Media upload library, Default media seeding, JSON schema validation | Phase 5 | Disk storage isolation, image validation/resizing, MIME verification |
| **Phase 7** | Template Engine Construction | Template discovery, `template.json` schema validator, Unified data contract, HTML rendering | Phase 6 | Filesystem sandbox, preventing PHP execution in template files |
| **Phase 8** | Template Preview & Switching | Live preview across viewports, non-destructive template switching, settings preservation | Phase 7 | Safe fallback for unsupported sections, historical settings rollback |
| **Phase 9** | Initial Templates Creation | Build 3 production templates: Corporate, Industrial/Electrical, Technology | Phase 8 | Pure HTML5, compiled Tailwind CSS, vanilla JS, RTL/LTR support |
| **Phase 10** | Publishing & Domain Routing | Nginx multi-domain handling, SSL/TLS automation, Caching, 404/maintenance routing | Phase 9 | Wildcard/multi-cert TLS, cache invalidation on publish |
| **Phase 11** | Comprehensive E2E Testing | Playwright testing across screen sizes (320px–1920px), cross-site tenant boundary validation | Phase 10 | Headless browser automation, regression testing |
| **Phase 12** | Launch, Documentation, & Ops | Production hardening, Port 80/443 SSL renewal, Automated backups, Ops documentation | Phase 11 | Firewall rules, internalizing MySQL, disaster recovery verification |

---

## 15. Missing Information and Architectural Questions

1. **Missing Project Documentation:**
   - The secondary plan file `Plans/ 袟...md` has corrupted encoding. It should be restored or cleaned.
   - There is no root `README.md` providing development onboarding instructions.
2. **Package Management & Autoloading Strategy:**
   - The project currently relies on manual `require_once` chains in `config/bootstrap.php`. As controllers and services grow across Phases 5–12, should standard Composer PSR-4 autoloading be introduced, or does the architectural decision mandate zero external tooling?
3. **Frontend Build Pipeline:**
   - Node.js and npm are absent from the Docker containers. Because the master plan requires compiled Tailwind CSS (prohibiting the Tailwind CDN in production), where and when will asset compilation occur?
4. **Dynamic Routing Support:**
   - `Router` only matches exact strings. Phase 5 Website CRUD will require parametrized paths (e.g., `/admin/api/websites/{id}`). An approved routing design pattern is needed.
5. **Port in Host Header Handling:**
   - `DomainNormalizer` rejects `:` with HTTP 400. In local development or custom reverse proxy setups where the port is appended to the Host header, domain resolution fails. Should `DomainNormalizer` strip port numbers before hostname validation?
6. **Session Re-configuration Warning:**
   - In `AdminAuthenticationService::configureSession()`, `ini_set()` calls occur when a session may already be active, raising runtime PHP notices in Docker logs.
7. **Media Relational Integrity:**
   - `media` references inside JSON columns (`sections.content`, `website_settings.setting_value`) lack foreign key constraints. A formal validation service policy is needed to prevent orphaned or cross-tenant media access.

---

## 16. Git Repository and Change History

- **Current Branch:** `master`
- **Commit History:** **No commits yet** (`fatal: your current branch 'master' does not have any commits yet`).
- **Working Tree Status:** All project files are currently untracked:
  ```text
  Untracked files:
    .env.example
    .gitignore
    Plans/
    bin/
    config/
    database/
    docker-compose.yml
    docker/
    documentation/
    public/
    src/
    tests/
  ```
- **Uncommitted Changes:** The entire repository represents the initial uncommitted working tree.
- **Git Metadata:** Checkpoint objects exist under `.git/refs/codex/turn-diffs/checkpoints/`, but `refs/heads/master` is uncommitted.

---

## 17. Architectural Risks

1. **Host Header Port Rejection in Non-Standard Environments:** Any reverse proxy or client forwarding `Host: example.com:8081` will trigger an immediate HTTP 400.
2. **Lack of Dynamic Routing:** The absence of route parameter support will block RESTful API development for Phase 5 without a router refactor.
3. **Manual Autoloading Fragility:** Manually adding `require_once` for dozens of future entities, services, and controllers introduces high maintenance overhead and risk of circular inclusion errors.
4. **Tailwind Production Compilation Gap:** Lack of Node/Tailwind CLI in the container will prevent compiling production-ready CSS for visual templates in Phase 9.
5. **Single-Point-of-Failure Administrator:** Because credentials reside exclusively in `.env`, a compromised `.env` or lost environment configuration compromises the entire administrative plane without recovery options.

---

## 18. Recommended Verification Priorities

Once authorized to proceed beyond this read-only audit:
1. **Priority 1: Execute Phase 3 Migrations**
   - Run `docker compose exec app php bin/console db:migrate` and verify all 12 tables and constraints are created in MySQL.
2. **Priority 2: Re-test Live Domain Resolution**
   - Probe `http://127.0.0.1:8081/` with a valid seeded domain to verify HTTP 200 resolution without database errors.
3. **Priority 3: Adjust `DomainNormalizer` for Host Port Handling**
   - Allow optional port stripping prior to RFC hostname validation to facilitate smooth local development on port 8081.
4. **Priority 4: Establish Initial Git Baseline**
   - Create the initial repository commit on `master` to track changes cleanly across subsequent phases.
5. **Priority 5: Address Corrupted Plan File**
   - Restore or clean `Plans/ 袟...md` to maintain documentation clarity.

---

## 19. Final Project Readiness Assessment

| Evaluation Dimension | Readiness Status | Details |
|---|---|---|
| **Architecture Quality** | **EXCELLENT** | Clear boundaries, solid tenant isolation design, no bloat. |
| **Infrastructure Setup** | **READY** | Docker Compose with healthy PHP 8.3-FPM, Nginx, and MySQL 8.4 containers. |
| **Database Readiness** | **PENDING EXECUTION** | 12 migration files written and verified in code; pending live execution. |
| **Backend Core** | **READY** | Routing, Request/Response, Auth, CSRF, and Domain Resolution implemented and tested. |
| **Test Coverage** | **MINIMAL BUT PASSING** | 4 custom unit tests pass; missing automated integration and migration tests. |
| **Overall Phase 4 Readiness**| **BLOCKED BY PHASE 3 EXECUTION** | Phase 4 code is complete, but cannot be verified end-to-end via HTTP until Phase 3 migrations are applied. |

---

## 20. Auditor Attestation and Sign-Off

This audit was conducted strictly using read-only inspections, runtime metadata probing, and non-destructive test executions. No application source code, Docker configurations, environment variables, or database records were modified during this investigation.

**Auditor:** Antigravity (Google DeepMind)  
**Verification Status:** Audit Complete  
**Date:** November 4, 2026


