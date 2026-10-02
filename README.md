# CampusFix

### Campus Complaint Tracking System

CampusFix helps university students report campus facility problems and helps administrators review complaints, communicate with students, and record resolutions. It combines complaint tracking, protected photo evidence, a history of status and priority changes, and private in-app notifications in a responsive PHP application.

**Core stack:** HTML5 · CSS3 · JavaScript · PHP · PDO · MySQL-compatible database  
**Interface:** Bootstrap 5 and Bootstrap Icons  
**Image integration:** Cloudinary PHP SDK  
**Deployment configuration:** Docker on Render with an external database

## Contents

- [Overview](#overview)
- [Implemented features](#implemented-features)
- [Complaint workflow](#complaint-workflow)
- [Technology and architecture](#technology-and-architecture)
- [Repository guide](#repository-guide)
- [Database design](#database-design)
- [Local installation](#local-installation)
- [Environment configuration](#environment-configuration)
- [Render deployment](#render-deployment)
- [Protected images and maintenance](#protected-images-and-maintenance)
- [Security controls](#security-controls)
- [Verification and troubleshooting](#verification-and-troubleshooting)
- [Project demonstration](#project-demonstration)
- [Documentation and viva notes](#documentation-and-viva-notes)

## Overview

Verbal reports and informal messages make maintenance issues difficult to track. CampusFix keeps the report, current status, administrative response, supporting evidence, and conversation together in one complaint record.

The current application has two roles: **student** and **administrator**. Students access their own complaints; administrators manage the campus-wide queue. The features below describe the code currently available on `master`. Provider configuration and live verification are documented separately.

## Implemented features

### Student workspace

| Feature | Current behavior |
| --- | --- |
| Registration and login | Register with full name, student ID, email, and password; sign in using session authentication. |
| Personal dashboard | Total, Pending, In Progress, and Resolved counts, plus the five most recent complaints. |
| Complaint submission | Submit a title, category, campus location, priority, and description; receive a tracking code such as `CMP-0001`. |
| Search and filtering | Search title, tracking code, location, and description; combine category, priority, and status filters. |
| Complaint management | Edit or delete an owned complaint while its status is **Pending**. |
| Resolution review | View the current status and administrator's resolution note. |
| Photo evidence | Add and remove before-repair images on an owned Pending complaint when image storage is configured. |
| Conversation | Post comments and one-level replies to administrators on an owned complaint. |
| Activity timeline | View the recorded actor, time, and old/new status or priority values. |
| Notifications | See an unread bell count and a notification list; mark individual entries or all entries as read. |
| Profile | Update full name and student ID; email is read-only. Upload, replace, or remove an optional protected profile picture. |

### Administrator workspace

| Feature | Current behavior |
| --- | --- |
| Campus dashboard | Total, Pending, In Progress, Resolved, and High Priority counts, plus five recent reports. |
| Complaint queue | View complaints across all students, search records, and filter by category, priority, or status. |
| Status and priority management | Change the complaint's status and priority; a resolution note is required when marking it **Resolved**. |
| Evidence management | Add after-repair images and remove complaint evidence. The three-image limit applies to the whole complaint. |
| Student communication | Read and respond to complaint comments and one-level replies. |
| Activity and notifications | Review recorded changes and receive notifications for new complaints, student comments, and student priority changes. |
| User directory | View registered students and administrators in a read-only directory. |

### Shared infrastructure

- **Versioned migrations:** numbered SQL files, SHA-256 checksums, and a `schema_migrations` ledger support fresh installations and upgrades of the existing baseline schema.
- **Optional image storage:** core complaint, profile-text, and activity features remain usable when Cloudinary is unconfigured.
- **Protected delivery:** application routes check the session and ownership/role before retrieving authenticated Cloudinary assets server-side.
- **Cleanup retries:** failed provider deletions can be recorded in `media_cleanup_jobs` and retried through a CLI command.
- **Responsive interface:** Bootstrap layouts, status/priority badges, validation feedback, image previews, and flash messages support desktop and mobile use.

Notifications are stored in the application database and displayed during page requests. They are in-app notifications; the current implementation does not send email, SMS, or browser push messages.

## Complaint workflow

| Stage | Action |
| --- | --- |
| Report | A student submits a complaint. The server assigns **Pending**, creates the tracking code and creation event, and notifies administrators. |
| Add context | The student opens the detail page to add optional evidence or comments. Student edits and evidence removal are limited to Pending complaints. |
| Review | An administrator reviews the complaint, adjusts priority/status, and communicates through comments. |
| Resolve | The administrator records a resolution note and marks the complaint **Resolved**. After-repair evidence can be added when storage is configured. |
| Follow up | The student reviews the status, note, evidence, conversation, timeline, and notifications. |

**Status values:** `Pending`, `In Progress`, `Resolved`.  
**Priority values:** `Low`, `Medium`, `High`.

This describes the usual workflow. Administrators select from the allowed status values; the application does not enforce a one-way transition sequence. Students cannot change their complaint's status or resolution note.

**Supported categories:** Wi-Fi / Internet, Electrical, Classroom, Lab Equipment, Cleanliness, Water Supply, Furniture, Washroom, Security, and Other.

## Technology and architecture

| Layer | Implementation |
| --- | --- |
| Presentation | HTML5, CSS3, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, and vanilla JavaScript. Bootstrap assets are loaded from a CDN. |
| Application | PHP route files, shared helpers, session authentication, role/ownership checks, validation, and PDO queries. |
| Data | MySQL-compatible database with InnoDB tables, `utf8mb4`, foreign keys, and transactional complaint activity updates. Local verification is recorded for MariaDB; TiDB Cloud connection settings are provided for deployment. |
| Media | Cloudinary PHP SDK installed through Composer, authenticated asset storage, and guarded PHP image-delivery routes. |
| Runtime | Local Apache/XAMPP; the Docker image uses PHP 8.4 with Apache, PDO MySQL, cURL, mbstring, and fileinfo. |

Browser requests reach PHP through Apache. PHP reads/writes application records through PDO. For protected image requests, PHP authorizes the current account, retrieves the Cloudinary image, and returns its bytes with private, no-store cache headers. MySQL stores image references and metadata rather than image binary content.

## Repository guide

| Location | Responsibility |
| --- | --- |
| [index.php](index.php), [register.php](register.php), [login.php](login.php), [logout.php](logout.php) | Landing page and account access. |
| [dashboard.php](dashboard.php), [complaints.php](complaints.php), [profile.php](profile.php) | Student dashboard, complaint list, and profile. |
| [complaint_create.php](complaint_create.php), [complaint_view.php](complaint_view.php), [complaint_edit.php](complaint_edit.php), [complaint_delete.php](complaint_delete.php) | Student complaint lifecycle routes. |
| [admin/](admin/) | Administrator dashboard, global queue, complaint management, and user directory. |
| [complaint_comment.php](complaint_comment.php), [notifications.php](notifications.php), [notification_read.php](notification_read.php) | Conversation and private notification actions. |
| [complaint_image.php](complaint_image.php), [complaint_image_upload.php](complaint_image_upload.php), [complaint_image_delete.php](complaint_image_delete.php) | Authorized complaint evidence delivery, upload, and removal. |
| [profile_image.php](profile_image.php), [profile_image_upload.php](profile_image_upload.php), [profile_image_delete.php](profile_image_delete.php) | Protected profile-picture routes. |
| [config/database.php](config/database.php) | Environment-based PDO connection configuration. |
| [includes/](includes/) | Authentication, CSRF/escaping helpers, shared layout, media adapter, and activity helpers. |
| [assets/](assets/) | Application stylesheet and browser interactions. |
| [database/migrations/](database/migrations/), [database/migrations.php](database/migrations.php) | Versioned schema files and migration runner. |
| [database/bootstrap.php](database/bootstrap.php), [database/migrate.php](database/migrate.php) | CLI startup/bootstrap and manual migration entry points. |
| [database/media_cleanup.php](database/media_cleanup.php) | CLI retry of queued provider deletions. |
| [Dockerfile](Dockerfile), [docker/start.sh](docker/start.sh), [render.yaml](render.yaml) | Container build, startup, and Render Blueprint configuration. |
| [tests/](tests/), [docs/](docs/) | Focused checks and operational/verification documentation. |

## Database design

The current migrated installation contains nine tables:

| Table | Purpose and relationships |
| --- | --- |
| `users` | Account details, password hashes, and student/admin roles. One user can submit many complaints. |
| `complaints` | Tracking code, owner, category, location, priority, description, current status, and resolution note. `user_id` references `users`. |
| `complaint_images` | Before/after asset references and metadata. Links to a complaint and an optional uploader. |
| `profile_images` | One optional image reference per user; `user_id` is the primary key. |
| `complaint_events` | Recorded complaint changes, actor, old/new status and priority, and timestamp. |
| `complaint_comments` | Complaint messages, authors, and optional `parent_id` for one-level replies. |
| `notifications` | Recipient-specific entries, optional actor/complaint references, and `read_at`. |
| `media_cleanup_jobs` | Queued Cloudinary deletion retries, attempt counts, and last error. |
| `schema_migrations` | Applied migration version, checksum, and application timestamp. |

The migration sequence is:

1. [001_baseline.sql](database/migrations/001_baseline.sql): users and complaints.
2. [002_images.sql](database/migrations/002_images.sql): complaint/profile images and cleanup jobs.
3. [003_activity.sql](database/migrations/003_activity.sql): events, comments, and notifications.

Complaint deletion cascades to its image-reference and activity rows. Provider asset cleanup is handled by application code and the retry queue. Nullable actor/uploader references preserve associated records when an account reference is removed.

Existing complaints have no fabricated historical events: their timeline begins with changes recorded after the activity migration. [database/schema.sql](database/schema.sql) contains the older two-table schema; use the migration runner for the complete current installation.

## Local installation

### Prerequisites

- PHP 8.2+ with PDO MySQL, mbstring, cURL, and fileinfo enabled.
- Apache with `.htaccess` overrides, `mod_rewrite`, and `mod_headers`; XAMPP is a local development option.
- A running MySQL/MariaDB database server and Composer.
- A Cloudinary account only if testing image actions. The core application can run without it.

### Clean installation

1. Clone the repository into Apache's document root. For XAMPP, use `C:\xampp\htdocs\CampusFix`.

   ```sh
   git clone https://github.com/Trisha105/CampusFix.git
   cd CampusFix
   composer install
   ```

2. Create an empty database in phpMyAdmin or a MySQL client:

   ```sql
   CREATE DATABASE campusfix
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Configure the PHP process's database environment variables if they differ from the local defaults below, then run:

   ```sh
   php database/migrate.php
   ```

4. Provision the first administrator using the CLI bootstrap. Replace the example email/password with your own values. Example for PowerShell:

   ```powershell
   $env:ADMIN_EMAIL = 'admin@example.edu'
   $env:ADMIN_PASSWORD = 'replace-with-a-unique-password'
   php database/bootstrap.php
   Remove-Item Env:ADMIN_EMAIL, Env:ADMIN_PASSWORD
   ```

   The password must contain at least 12 characters. Bootstrap creates a missing administrator; it does not reset an existing administrator's password. PHP/XAMPP users may need the full executable path, such as `C:\xampp\php\php.exe`.

5. Start Apache and the database service, then open `http://localhost/CampusFix/`. Register a student and sign in with the administrator configured above.

`APP_BASE_URL` is normally detected for the XAMPP subdirectory. Set it to `/CampusFix/` if an explicit override is needed. The application reads environment variables with `getenv()`; a `.env` file is not automatically loaded. Terminal environment changes apply to that process and its children, not an already running Apache service.

### Optional local demo data

For a disposable, offline/local demonstration, import [database/campusfix.sql](database/campusfix.sql) instead of creating the clean baseline, then run `php database/migrate.php` to add the current tables. The published local demo accounts include:

| Role | Email | Local demo password |
| --- | --- | --- |
| Administrator | `admin@campusfix.edu` | `admin123` |
| Student | `demo@campus.edu` | `password123` |

These credentials belong to the local demo dataset only. Public deployments use the clean migrations and a unique first-administrator password. The Docker image excludes both the demo SQL and the legacy [database/create_admin.php](database/create_admin.php) utility; use `database/bootstrap.php` for the current setup.

## Environment configuration

| Variable | Local/default value | Purpose |
| --- | --- | --- |
| `DB_HOST` | `127.0.0.1` | Database hostname; use the provider value in production. |
| `DB_PORT` | `3306` | Database port; use the provider value in production. |
| `DB_NAME` | `campusfix` | Existing database to connect to. |
| `DB_USER` | `root` | Database user; use the provider credentials in production. |
| `DB_PASS` | Empty locally | Database password. |
| `DB_SSL` | Disabled unless exactly `true` | Enables the cloud database TLS configuration. Render Blueprint sets `true`; the Docker image includes the system CA bundle. |
| `APP_BASE_URL` | Detected locally; `/` in Docker | Application URL path prefix. |
| `PORT` | `10000` in Docker | Apache listen port configured by the startup script. |
| `CLOUDINARY_URL` | Unconfigured | Optional Cloudinary SDK connection URL for image actions. |
| `ADMIN_EMAIL` | Unconfigured | First administrator email for CLI bootstrap. |
| `ADMIN_PASSWORD` | Unconfigured | First administrator password, minimum 12 characters. |

Set database credentials, Cloudinary configuration, and bootstrap credentials in the deployment environment. `ADMIN_EMAIL` and `ADMIN_PASSWORD` must be configured as a pair; remove both after confirming the first administrator login. Bootstrap requires an existing database and runs pending migrations before creating the administrator.

## Render deployment

1. Create an external MySQL-compatible database and obtain its connection values. For TiDB Cloud, configure `DB_SSL=true`.
2. Create or configure a Render web service using this repository, the **Docker** runtime, and the **master** branch.
3. Leave **Root Directory** empty, use **Dockerfile Path** `./Dockerfile`, and leave **Docker Command** empty so the image's startup command runs.
4. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL`, and `APP_BASE_URL=/` in Environment. Configure the first-administrator pair if needed; configure `CLOUDINARY_URL` for images.
5. For an existing database, follow the [backup and migration procedure](docs/migrations.md) before deployment. Run one migrator at a time.
6. Select **Manual Deploy → Deploy latest commit**, or use the commit-triggered deployment configured for the service.
7. Review build/startup logs, verify both roles and a complaint workflow, then remove both bootstrap administrator variables after successful first login. Verify an authorized image upload/view/removal separately.

[render.yaml](render.yaml) provides a Blueprint alternative for new services. Entries marked `sync: false` require values supplied through Render; existing services should have their Environment settings checked directly.

The Docker build installs locked Composer dependencies, checks required PHP extensions, enables Apache rules, sets upload limits, and lints application PHP files. Startup binds Apache to `0.0.0.0:$PORT`, runs migrations/bootstrap, and starts Apache only when initialization succeeds. Application data remains in the external database; image assets remain in Cloudinary.

## Protected images and maintenance

| Rule | Implementation |
| --- | --- |
| Accepted formats | JPEG, PNG, and WebP; MIME type and image dimensions are checked server-side. |
| Size | Maximum 5 MiB per image; width and height must each be between 1 and 8000 pixels. |
| Complaint limit | Three images total per complaint, including both before and after evidence. |
| Student actions | Before-repair upload/removal on the student's own Pending complaint. Evidence is added from the complaint detail page after submission. |
| Administrator actions | After-repair upload and removal of complaint evidence. |
| Profile images | One optional image per student, with replace/remove actions. Delivery allows the owner or an administrator. |
| Delivery | Authenticated Cloudinary assets fetched through session/permission-checked PHP routes with private, no-store headers. |

The container sets `upload_max_filesize=5M` and `post_max_size=18M`. Configure equivalent PHP limits for local Apache if testing uploads. Without Cloudinary configuration, image actions are unavailable while other workflows remain usable.

Failed provider deletions are queued for retry. From a trusted CLI with the deployment's database and Cloudinary configuration, run:

```sh
php database/media_cleanup.php
```

Schedule that command externally if automatic retries are desired; the repository does not include a scheduler service. See [image setup and failure handling](docs/images.md). Simultaneous provider/database failures can require manual reconciliation.

## Security controls

- Passwords are stored with `password_hash(..., PASSWORD_DEFAULT)` and checked with `password_verify()`.
- Login regenerates the session ID; authentication and role checks use PHP sessions.
- Student complaint reads/writes enforce ownership on the server; administrative routes require the admin role.
- User-supplied query values are bound through PDO prepared statements; emulated prepares are disabled.
- Shared HTML escaping is used for user-visible content, including comments and resolution notes.
- Registration, login, complaint/profile forms, and protected write endpoints validate session CSRF tokens; deletion, comment, image, and notification actions require POST.
- Related complaint updates, events, and notifications commit together in database transactions.
- Image file content, count, size, and permissions are checked server-side; signed provider URLs are not embedded in the UI.
- Apache rules restrict directory listing and access to application internals and sensitive file types. CLI bootstrap/migration/cleanup entry points reject browser execution.

## Verification and troubleshooting

### Repository verification record

The following results are recorded in the repository documentation dated **2 October 2026**. They describe local checks, not a certification of the live deployment.

| Area | Recorded verification |
| --- | --- |
| Migrations | Populated/fresh MariaDB installations, reruns, partial-baseline rejection, and preservation of existing user/complaint rows. |
| Image validation | Valid PNG accepted; disguised text and oversized content rejected. |
| Cleanup retries | Fake-provider deletion failure queued; failed retry counted; successful retry removed the job. |
| Activity and access | Local browser checks for owner/admin access, cross-account denial, comments/replies, status/priority events, notification ownership, and invalid input. |
| Syntax and layout | PHP lint and a mobile review of the complaint detail page. |
| Pending live checks | Actual Cloudinary upload/download/delete, TiDB staging, Docker build, and production browser verification. |

Available focused commands, from the repository root:

```sh
php tests/media_validation.php
php tests/media_cleanup.php
```

The cleanup test must use a **dedicated, migrated test database** with test `DB_*` variables. It processes queued jobs through a fake provider and must not run against operational data. The validation test does not require a database or live provider. Both require the relevant PHP extensions.

For a shell with `find` and `xargs`, application syntax checks can be run with:

```sh
find . -path ./vendor -prune -o -name '*.php' -print0 | xargs -0 -n 1 php -l
```

See [migration checks](docs/migrations.md), [image checks](docs/images.md), and [activity checks](docs/activity.md) for their exact scope and outstanding verification.

### Troubleshooting

| Symptom | Check |
| --- | --- |
| Dockerfile not found | Selected commit/branch, empty Root Directory, and `./Dockerfile` path. |
| Database 503 or bootstrap failure | Database already exists; host, port, credentials, TLS settings, and provider network access are correct. |
| Missing activity/image tables | Run the current migrations; importing only `database/schema.sql` does not install the upgraded schema. |
| Migration checksum or partial-schema error | Inspect the logs/schema and follow [recovery instructions](docs/migrations.md); add a reviewed migration rather than editing an applied file. |
| Image storage unavailable | `CLOUDINARY_URL`, installed Composer dependencies, required PHP extensions, and redeployment after environment configuration. |
| Upload rejected | Supported file content, 5 MiB size limit, three-image total, PHP request limits, ownership, and complaint status. |
| Incorrect local links | Apache document root and `APP_BASE_URL`; restart Apache after changing its environment. |
| Bootstrap credential validation fails | Valid email and a password of at least 12 characters; configure or remove both administrator variables together. |

## Project demonstration

1. Register a student and show the personal dashboard.
2. Submit a complaint with category, location, priority, and description; show its tracking code and Pending state.
3. Open the detail page, add before-repair evidence if Cloudinary is configured, and post a comment.
4. Demonstrate search/filtering and a Pending-only edit. Use separate test accounts to demonstrate ownership boundaries.
5. Sign in as an administrator, review the campus queue and notification, and reply to the student.
6. Change the status to In Progress, add after-repair evidence when configured, then resolve with a note.
7. Return as the student to show the status/priority timeline, conversation, evidence, resolution note, and mark-as-read action.
8. Show profile editing, optional profile-picture management, and the administrator's read-only user directory.

Useful report screenshots include the landing page, registration/login, both dashboards, filtered complaint list, complaint detail with timeline/comments/evidence, admin resolution form, notifications, profile, and current migrated database relationships. Use synthetic records and omit real credentials and private student information from shared screenshots.

## Documentation and viva notes

| Document | Contents |
| --- | --- |
| [docs/migrations.md](docs/migrations.md) | Installation upgrades, backup/recovery procedure, and recorded database checks. |
| [docs/images.md](docs/images.md) | Cloudinary configuration, protected delivery, limits, cleanup, and verification. |
| [docs/activity.md](docs/activity.md) | Events, comments/replies, notifications, transactions, and access checks. |
| [docs/upgrade-plan.md](docs/upgrade-plan.md) | Implementation milestones, verification status, and proposed later work. |

<details>
<summary>Viva preparation: key implementation questions</summary>

| Question | Answer |
| --- | --- |
| Why PHP and PDO? | PHP runs the request handlers; PDO provides parameterized access to the MySQL-compatible database. Composer installs the Cloudinary dependency. |
| Where is CRUD used? | Students create/read complaints and update/delete owned Pending records; administrators manage status, priority, and resolution notes. |
| How are passwords protected? | `password_hash()` stores a salted hash and `password_verify()` checks it at login. The algorithm is selected by `PASSWORD_DEFAULT`. |
| How is complaint ownership enforced? | Server-side checks compare the complaint owner with the authenticated user; restricted student queries include the user's ID. |
| What is RBAC here? | The session role selects student/admin access guards and navigation. The current release has those two roles. |
| Why validate on the server? | Browser checks improve feedback, but PHP checks enforce permitted values, ownership, status, file content, and limits. |
| What is the main database relationship? | One user can submit many complaints; foreign keys also connect complaints to evidence, comments, events, and notifications. |
| What do sessions store? | Authenticated user identity and role persist across requests through a server-side session identified by a browser cookie. |
| How is CSRF addressed? | POST actions validate a random token from the current session using `hash_equals()`. |
| Why use transactions? | Related state, history, and notification changes commit together or roll back together. Cloudinary operations use separate cleanup handling. |
| How are images protected? | Cloudinary assets use authenticated delivery; PHP checks the account before downloading and returning the bytes. |
| Why migrations? | Numbered schema changes and checksums make fresh installation and later upgrades trackable while preserving baseline records. |

</details>
