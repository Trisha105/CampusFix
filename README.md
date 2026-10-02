# CampusFix

CampusFix is a PHP campus complaint tracker for students and administrators. The live site is [campusfix-3ue5.onrender.com](https://campusfix-3ue5.onrender.com/). The production application runs on Render with a TiDB Cloud MySQL-compatible database. The public demo complaint gallery was removed from `master`; operational complaints remain in the database.

## Implemented features

- Students can register, sign in/out, submit a complaint, search and filter their own list, and edit or delete an owned complaint while it is Pending.
- Administrators can review all complaints, filter the queue, change status and priority, add a resolution note, and view a read-only user directory. Resolved status requires a note.
- Complaint detail pages show new activity events and owner/admin comments with one-level replies. The notification bell shows unread counts, a private list, and a mark-as-read action. Earlier status changes are not backfilled.
- Up to three complaint images (5 MiB each) can be uploaded with before/after labels; profile pictures are optional. JPEG, PNG and WebP content is checked on the server. The image feature requires Cloudinary. Image bytes pass through session/ownership checks in PHP.
- Versioned, additive migrations support a fresh database and an existing two-table installation without replacing existing users or complaints.

There is **no technician role, department assignment, deadline/reopen/rating flow, email verification or reset, advanced analytics/export, language or dark-mode switch, structured map/QR flow, Google login, browser push, or AI service**. The administrator dashboard has basic current-state counts, not the planned analytics module. See [the milestone audit](docs/upgrade-plan.md).

## Stack and data

HTML5, CSS3, Bootstrap 5.3.3, Bootstrap Icons, vanilla JavaScript, Apache/PHP 8.4 in Docker, PDO MySQL, TiDB Cloud and the Cloudinary PHP SDK from `composer.lock`. Local development can use a compatible PHP/Apache and MySQL/MariaDB installation.

`database/migrations/` contains:

| Version | Tables |
| --- | --- |
| `001_baseline` | `users`, `complaints` |
| `002_images` | `complaint_images`, `profile_images`, `media_cleanup_jobs` |
| `003_activity` | `complaint_events`, `complaint_comments`, `notifications` |

`schema_migrations` tracks applied versions and checksums. The main relationship is `complaints.user_id → users.id`. Image, event, comment and notification tables reference the relevant user/complaint rows. The demo SQL in `database/campusfix.sql` contains published local credentials and is **excluded from the production Docker image**.

## Environment variables

Set these in **Render → campusfix → Environment** or in a private local environment. Never commit values.

| Variable | Use |
| --- | --- |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Required production database connection |
| `DB_SSL=true` | Required for the TiDB Cloud connection |
| `APP_BASE_URL=/` | Root deployment URL prefix |
| `PORT=10000` | Apache listening port in the Render Blueprint |
| `CLOUDINARY_URL` | Required for complaint/profile image actions; core complaint routes work without it |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Optional initial admin provisioning pair; password must be at least 12 characters |

Keep the two `ADMIN_*` values together during first provisioning. Once the admin account has been verified, remove both from Render Environment; the bootstrap does not reset an existing admin password. The current production service has `CLOUDINARY_URL` configured, but a real provider upload/download/delete has not yet been recorded as passing.

## Local setup

1. Install PHP with PDO MySQL, cURL, mbstring and fileinfo, plus Apache and MySQL/MariaDB. Install Composer dependencies with `composer install --no-dev`.
2. Create an empty database or use a copy of an existing CampusFix database. Set the `DB_*` variables above; use `DB_SSL=false` for a local non-TLS database.
3. Run `php database/migrate.php`. Run `php database/bootstrap.php` only from the CLI if initial admin provisioning is needed.
4. Serve the repository through Apache with `.htaccess` enabled. Do not expose `database/`, `docs/`, `tests/` or `vendor/` as public routes outside the provided Docker allowlist.

For local classroom demo data only, the legacy `database/campusfix.sql` can populate the local database before migration. Its credentials must never be used on the public site.

## Exact Render deployment procedure

The existing `campusfix` service is Blueprint-managed, uses **Docker**, tracks **`master`**, and auto-deploys commits. The Dockerfile is `./Dockerfile`, its context is repository root, and its startup command runs migrations before Apache.

1. In TiDB Cloud, create a current-point-in-time branch or verified export of the **production database**. Check it is Active before deployment; record user and complaint counts.
2. Review and merge a PR into `master`. Render should start an automatic deploy for the merge commit. If it does not, use **Render → campusfix → Manual Deploy → Deploy latest commit**. Do not deploy the feature branch directly to this service.
3. Confirm **Environment** has the required `DB_*`, `DB_SSL=true`, `APP_BASE_URL=/`, `PORT=10000`, and `CLOUDINARY_URL` for image actions. Do not paste secrets into logs or chat.
4. In **Render → Deploys**, wait for build and startup to succeed. Startup failure stops the new container; inspect its log before retrying. The image compiles PDO MySQL, cURL and mbstring and checks PHP syntax during build.
5. Open the [live site](https://campusfix-3ue5.onrender.com/) and check home/login, then verify `schema_migrations` and unchanged operational row counts in TiDB. Exercise student/admin pages with authorized accounts and an image upload/view/delete before declaring those flows accepted.
6. If startup fails, roll back the Render application to the prior deployed commit. Database DDL is not transactionally reversible; use the backup only after reviewing any writes made since it was taken. See [migration and recovery instructions](docs/migrations.md).

On **2 October 2026**, merge commit `97e1000` built and deployed successfully on Render; home returned HTTP 200. TiDB `test` showed all three migration versions, nine tables, two users and one complaint after deployment. The pre-upgrade branch `pre-upgrade-2026-10-02` is Active. These checks do not establish successful Cloudinary transfer or authenticated production workflows.

## Checks and limitations

Run `php -l` for application files, `php tests/media_validation.php`, and `php tests/media_cleanup.php` against an **isolated test database**. The latter intentionally creates and removes one fake-provider cleanup job. Local MariaDB migration tests covered populated, fresh, repeated and rejected partial schemas. See the dated evidence in [migration](docs/migrations.md), [image](docs/images.md), and [activity](docs/activity.md) notes.

The application uses password hashes, PDO prepared statements, server-side validation, CSRF tokens, role and ownership checks, and HTML escaping in the implemented routes. It has no completed security audit, rate limiting or automated end-to-end suite. Cloudinary cleanup retries require an external schedule for `php database/media_cleanup.php`. The database TLS configuration currently falls back to unverified TLS if the system CA bundle is absent; the Render image installs a CA bundle.
