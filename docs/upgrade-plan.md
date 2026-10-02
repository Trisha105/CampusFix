# CampusFix upgrade plan

Status on 2 October 2026: M1–M4 code was merged to `master` in PR #3 (merge commit `97e1000`) and the Render Docker deploy succeeded. Production TiDB migrations applied. Cloudinary transfer and authenticated production workflows still need live checks. M5–M13 are incomplete; M14 release review is partial. The original prompt numbers Google login and browser push together; this plan splits them into M11 and M12 so the final review is M14.

## Current architecture and baseline

- Render builds `Dockerfile` (`php:8.4-apache-bookworm`) and runs `database/bootstrap.php` before Apache. `render.yaml` deploys commits on `master` and names the database, Cloudinary and first-admin environment variables. The repository's local `database/campusfix.sql` and legacy admin setup endpoint are excluded from the image.
- PHP route files render Bootstrap HTML directly. `includes/auth.php` uses sessions and student/admin role checks. `includes/functions.php` supplies CSRF tokens, escaping, taxonomies and flash messages. Student complaint queries are scoped to `user_id`; administrators can view and update any complaint.
- `config/database.php` connects PDO to a MySQL-compatible database using environment variables. The baseline production schema contains `users` and `complaints`, linked by `complaints.user_id -> users.id`. The startup bootstrap applies additive versioned migrations and conditionally creates the first administrator.
- The public sample gallery was removed from the current production `master` branch. The upgrade merge preserves that removal; operational complaint rows remain unchanged.
- Current complaint status remains on `complaints`; `003_activity` adds new events, comments and notifications without inventing history for older rows. `002_images` adds Cloudinary asset references and cleanup jobs. `schema_migrations` records three applied versions. Two focused media tests exist; there is no full end-to-end suite.

## Invariants for every milestone

Preserve existing user and complaint rows, IDs, tracking codes, ownership rules and role checks. Never import local demo credentials into production. Keep secrets in environment variables and out of logs, Git and screenshots. Each change needs PHP lint, focused automated tests, an isolated database test where data changes, and manual verification where accounts/providers are required. Record checks that could not run; a source review is not a passed runtime test.

## Milestones and acceptance criteria

### M1 — audit and plan

Dependencies: current repository only.

Acceptance: document the actual architecture, data model, upgrade order, security boundaries, test strategy and provider dependencies. Create `feature/campusfix-upgrade`. **Status: implemented and committed.** The branch was later merged to `master`.

### M2 — migration foundation

Dependencies: M1.

Implement a versioned migration directory and a CLI runner with a `schema_migrations` ledger. Baseline migration must create the existing schema on a fresh database, recognize an existing production schema without dropping or rewriting rows, and reject an incompatible partial schema. Add a safe additive migration pattern for later milestones. Startup runs pending migrations before serving requests. Document a backup command, restore procedure and the fact that MySQL/TiDB DDL is not one transaction with ledger insertion.

Acceptance: fresh install has both tables; upgrading a populated copy retains user/complaint counts and key values; rerunning is a no-op; a bad/partially applied migration fails visibly without serving the app. Verify on local MySQL/MariaDB and, when authorized, on a disposable TiDB database. Do not use production data for tests.

**Status:** implemented, merged and deployed. Local MariaDB populated, fresh, repeat and partial-schema checks passed. Render built the Docker image and applied `001_baseline`, `002_images` and `003_activity` on the existing TiDB `test` database; its `users` and `complaints` tables remain. A TiDB pre-upgrade branch is Active. A restore exercise and isolated TiDB migration rehearsal were not run.

### M3 — protected complaint and profile images

Dependencies: M2, Cloudinary account and Render environment variables provided by the owner. Verify provider APIs against current official Cloudinary documentation before implementation.

Add up to three complaint images (each at most 5 MB) with server-side MIME/content checks, preview, removal and enlarged view; distinguish `before` and `after` evidence. Add optional profile pictures. Store only asset identifiers/metadata in new tables/columns. Use Cloudinary authenticated/private assets and an application authorization gate for every view; an unlisted public URL does not count as protection. Only the complaint owner and authorized staff may access complaint evidence. Plan for upload success/database failure and database success/delete failure with compensating cleanup or a retry record. Add required PHP dependencies to Docker and document environment variables.

Acceptance: valid images upload and display to authorized users, invalid/oversize images fail, fourth image fails, cross-account and guest access fail, removed images become unavailable, and provider failures leave no falsely referenced DB row. Automated tests use a fake provider; live tests require the configured account.

**Status:** code, migration, SDK packaging and guarded image proxy implemented and deployed. Render has `CLOUDINARY_URL`; its Docker build succeeded. Local validation, fake-provider cleanup and guest/cross-account authorization checks passed. A real Cloudinary upload/download/delete and authenticated production browser flow have **not** been verified.

### M4 — history, comments and in-app notifications

Dependencies: M2; M3 is completed first in this requested sequence so image events can use the same timeline.

Add tables for immutable complaint events, comments and notifications. Wrap complaint updates plus history/notification inserts in one database transaction. Record actor, timestamp, old/new status and priority; define a policy for preserving existing complaints with no backfilled event history. Permit owner/admin comments, with authorization on read and write. Add notification bell, unread count, list and CSRF-protected mark-as-read action. Escape all user text.

Acceptance: owner/admin see the same ordered history; student B cannot read or comment on student A's complaint; a failed update makes no history/notification entry; notifications are visible only to recipients and mark-as-read changes only their own rows. Test empty, long and malicious-looking comment inputs.

**Status:** migration `003_activity`, owner/admin conversation, transactionally recorded changes, bell/list/read actions implemented and deployed. Local MariaDB and synthetic-account browser checks passed, including cross-account denial, status/history consistency, invalid and HTML-like comments, and rollback cases; details are in `docs/activity.md`. TiDB schema and migration ledger are verified, while authenticated production flows remain untested. Existing complaints have no fabricated historical events.

### M5 — departments and technicians

Dependencies: M4. Add departments, technician role, assignment and explicit server-side status transitions. Test every role boundary and reassignment case.

**Status: incomplete.** `users.role` allows only student/admin; no department or assignment table, technician route, configurable category routing or transition policy exists. The admin dashboard's prose mentions departments, but its queries only use current complaint fields. No M5 tests exist.

### M6 — deadlines, reopen and rating

Dependencies: M5. Add priority targets, overdue indicators, owner-only reopen with a reason, notifications and rating/feedback rules across reopen cycles. Test lifecycle edges and reminder scheduling.

**Status: incomplete.** No deadline/target, reopen reason, feedback/rating schema, routes or reminder command exists. The current status enum and admin update route permit direct status selection; that is not the requested lifecycle policy. No M6 tests exist.

### M7 — email and account recovery

Dependencies: M4; Resend account, sender domain and Render secret configuration by the owner. Queue mail with retry, hashed expiring single-use tokens and rate limits; test with a fake transport first.

**Status: incomplete and provider-dependent.** There is no Resend SDK/API call, outgoing-mail queue, verification/reset token table, password recovery or change route. The only notification channel is in-app. A Resend account, verified sender/domain and secret would be needed for live email acceptance; no fake-mail or live tests exist.

### M8 — analytics and exports

Dependencies: M5–M6. Define metrics from operational rows, filter by date/department/status, and export authorized PDF/CSV. Test known fixtures, empty sets and spreadsheet-safe CSV.

**Status: partial existing UI, milestone incomplete.** `admin/dashboard.php` shows current Total/Pending/In Progress/Resolved/High Priority counts from `complaints`. It has no monthly trend, category chart, overdue/resolution-time/workload metrics, date/department filter, PDF/CSV route or fixture calculation tests.

### M9 — design, localization and dark mode

Dependencies: stable UI from earlier milestones and authentic campus photos supplied by the owner. Keep labels translated, user-entered text unchanged, and verify mobile/keyboard/theme behaviour.

**Status: partial existing UI, milestone incomplete.** Bootstrap/custom CSS provide responsive layouts and the Home page has reporting steps. There is no supplied campus photo, bilingual label catalog, language switch or saved dark theme. One 390 px local detail-page review was recorded, not a full mobile/keyboard/theme audit. Authentic photos remain an owner asset dependency.

### M10 — location QR, map and duplicate review

Dependencies: M5 and structured locations. Add validated building/floor/room IDs, QR destinations, Leaflet map and privacy-safe duplicate suggestions.

**Status: incomplete.** `complaints.location` is free text. No building/floor/room tables or selectors, QR generation, Leaflet assets, duplicate suggestions or merge-decision history exist. No M10 tests exist.

### M11 — Google sign-in

Dependencies: account model and Google OAuth configuration. Verify identity server-side, link accounts safely and collect missing student fields.

**Status: incomplete and provider-dependent.** Authentication is email/password only. No Google client ID, callback, identity verification or account-linking code/tests exist.

### M12 — browser push

Dependencies: M4 and Firebase Cloud Messaging configuration. Request browser permission, manage per-user subscriptions and cleanup on unsubscribe/logout, and keep previews free of complaint details.

**Status: incomplete and provider-dependent.** The in-app bell is not browser push. No service worker, Firebase SDK/configuration, subscription table, push sender or browser compatibility test exists.

### M13 — optional AI assistance

Dependencies: consented provider settings and reviewed data minimization. Suggestions require human approval, timeouts, usage limits and a non-AI fallback.

**Status: incomplete and provider-dependent.** No AI call, provider configuration, suggestion UI, approval workflow or evaluation set exists. This milestone is optional.

### M14 — release review

Dependencies: requested feature milestones. Run syntax, automated, database, Docker and role-flow checks; update README, diagrams and deployment notes; prepare a reviewable PR. Promote only after staging checks and owner review.

**Status: partial.** Local PHP lint, focused media tests, MariaDB migration cases, Render Docker build/health and TiDB migration ledger have passed. Public home was opened after deployment. Technician, password-reset and export workflows cannot be checked because M5/M7/M8 are absent. Authenticated production image/activity checks and a full role-flow suite remain open.

## Verification classification for M5–M14

| Class | Milestones and evidence |
| --- | --- |
| Implemented foundations only | M8 has basic admin counts; M9 has responsive Bootstrap/custom CSS and reporting steps. Neither meets its milestone acceptance criteria. |
| Verified in this review | PHP syntax; `tests/media_validation.php`; `tests/media_cleanup.php` with fake provider and isolated MariaDB; migration rerun and partial-schema rejection; Render build/health; production TiDB table/ledger inspection. These primarily cover M2–M4 and part of M14. |
| Incomplete | M5, M6, M7, M8, M9, M10, M11, M12 and M13 as specified above. M14 remains partial. There are no passing tests for the absent features. |
| Provider/asset dependent | M3 real Cloudinary transfer; M7 Resend account/domain; M9 authentic campus photographs; M11 Google OAuth; M12 Firebase Cloud Messaging; M13 optional AI provider. Configuration or assets alone will not implement these features. |

**Source audit:** `database/migrations/001_baseline.sql` through `003_activity.sql` define only the existing nine application/ledger tables; `users.role` remains student/admin. Searches of PHP, JS and migration files found no later-milestone routes, provider clients, schema changes or tests. The only tracked tests are `tests/media_validation.php` and `tests/media_cleanup.php`; prior M4 browser checks are recorded in `docs/activity.md`.
## Test and release procedure

1. Use an isolated database with synthetic users and complaints; record counts and representative IDs before each migration.
2. Lint changed PHP files and run focused tests. Test migration from both empty and populated databases, including rerun and failure cases.
3. Test each implemented role and cross-account access in a browser. The technician role and email transport are future work. Use a fake Cloudinary transport in focused tests; record separately what was verified with the live provider.
4. Review schema/data changes and a Render Docker build. Back up production before any production migration, deploy from a reviewed commit, and verify the live health and core complaint flow.
5. If deployment fails, restore the previous app version; if schema changes are backward-compatible, keep the migrated schema. Restore the database backup only when recovery requires it and after assessing newer writes.

## Review and deployment record, 2 October 2026

- `origin/master` and the local feature branch had identical file contents after PR #3 merged; there were no completed but uncommitted or unpushed implementation changes at the start of this M5–M14 audit. This review updates the documentation and blocks direct HTTP access to `vendor/`, `tests/` and `docs/` in `.htaccess`.
- The production service tracks `master` and auto-deployed merge commit `97e1000`. Render built the PHP 8.4 Docker image with Composer dependencies, compiled its extensions, passed PHP syntax checks inside the image and reported a live service/HTTP 200.
- TiDB Cloud `campusfix-db` uses database `test`. A pre-upgrade branch named `pre-upgrade-2026-10-02` was Active before deployment. The production SQL editor showed nine tables and ledger versions `001_baseline`, `002_images`, `003_activity` after deployment; current counts were two users and one complaint. Pre-upgrade row counts were not recorded, so unchanged row counts in production are not claimed.
- On isolated MariaDB, `campusfix` had four users/eight complaints and `campusfix_upgrade_fresh` had zero/zero. Migration rerun said `No pending migrations`; the deliberately partial schema exited 1 with `Partial baseline schema found`. PHP lint passed, both tracked media tests passed, and Composer validation passed with a non-failing missing-license warning.
- The current Windows host has no Docker CLI; the Render build is the Docker verification. A live Cloudinary transfer, authenticated production workflows, backup restore, and later milestone tests remain open.

## Open setup dependencies

- Cloudinary account and `CLOUDINARY_URL` are configured in Render. A real upload/view/delete needs an authorized account for acceptance; do not send secrets in chat.
- Resend, Google, Firebase and optional AI provider setup are not configured or implemented. Authentic campus images have not been supplied.
- The pre-upgrade TiDB branch is a recovery point. Test a restore in an isolated environment before relying on it for destructive recovery.
