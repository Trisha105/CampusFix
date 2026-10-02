# CampusFix upgrade plan

Status: M1–M4 implemented on the local feature branch as of 2 October 2026. Provider and production verification remain open; later milestones are proposals.

## Current architecture and baseline

- Render builds `Dockerfile` (`php:8.4-apache-bookworm`) and runs `database/bootstrap.php` before Apache. `render.yaml` deploys commits on `master` and names the database, Cloudinary and first-admin environment variables. The repository's local `database/campusfix.sql` and legacy admin setup endpoint are excluded from the image.
- PHP route files render Bootstrap HTML directly. `includes/auth.php` uses sessions and student/admin role checks. `includes/functions.php` supplies CSRF tokens, escaping, taxonomies and flash messages. Student complaint queries are scoped to `user_id`; administrators can view and update any complaint.
- `config/database.php` connects PDO to a MySQL-compatible database using environment variables. The baseline production schema contains `users` and `complaints`, linked by `complaints.user_id -> users.id`. The startup bootstrap applies additive versioned migrations and conditionally creates the first administrator.
- The public sample gallery was removed from the current production `master` branch. The upgrade merge preserves that removal; operational complaint rows remain unchanged.
- Existing complaint status is a single field. There are no persisted status events, comments, notifications, uploads, or profile images. No automated test suite or migration history is present.

## Invariants for every milestone

Preserve existing user and complaint rows, IDs, tracking codes, ownership rules and role checks. Never import local demo credentials into production. Keep secrets in environment variables and out of logs, Git and screenshots. Each change needs PHP lint, focused automated tests, an isolated database test where data changes, and manual verification where accounts/providers are required. Record checks that could not run; a source review is not a passed runtime test.

## Milestones and acceptance criteria

### M1 — audit and plan

Dependencies: current repository only.

Acceptance: document the actual architecture, data model, upgrade order, security boundaries, test strategy and provider dependencies. Create `feature/campusfix-upgrade`; do not publish unfinished changes to production. **Status: complete locally.**

### M2 — migration foundation

Dependencies: M1.

Implement a versioned migration directory and a CLI runner with a `schema_migrations` ledger. Baseline migration must create the existing schema on a fresh database, recognize an existing production schema without dropping or rewriting rows, and reject an incompatible partial schema. Add a safe additive migration pattern for later milestones. Startup runs pending migrations before serving requests. Document a backup command, restore procedure and the fact that MySQL/TiDB DDL is not one transaction with ledger insertion.

Acceptance: fresh install has both tables; upgrading a populated copy retains user/complaint counts and key values; rerunning is a no-op; a bad/partially applied migration fails visibly without serving the app. Verify on local MySQL/MariaDB and, when authorized, on a disposable TiDB database. Do not use production data for tests.

**Status:** implemented locally; populated, fresh and partial-schema MariaDB checks passed on 2 October 2026. Docker build and disposable TiDB run remain pending.

### M3 — protected complaint and profile images

Dependencies: M2, Cloudinary account and Render environment variables provided by the owner. Verify provider APIs against current official Cloudinary documentation before implementation.

Add up to three complaint images (each at most 5 MB) with server-side MIME/content checks, preview, removal and enlarged view; distinguish `before` and `after` evidence. Add optional profile pictures. Store only asset identifiers/metadata in new tables/columns. Use Cloudinary authenticated/private assets and an application authorization gate for every view; an unlisted public URL does not count as protection. Only the complaint owner and authorized staff may access complaint evidence. Plan for upload success/database failure and database success/delete failure with compensating cleanup or a retry record. Add required PHP dependencies to Docker and document environment variables.

Acceptance: valid images upload and display to authorized users, invalid/oversize images fail, fourth image fails, cross-account and guest access fail, removed images become unavailable, and provider failures leave no falsely referenced DB row. Automated tests use a fake provider; live tests require the configured account.

**Status:** PHP routes, guarded image proxy, migration, SDK packaging and local validation/authorization checks implemented. A Cloudinary free account has been created and `CLOUDINARY_URL` saved in Render Environment without redeploying. Live Cloudinary upload/download/delete and Docker build verification remain pending.

### M4 — history, comments and in-app notifications

Dependencies: M2; M3 is completed first in this requested sequence so image events can use the same timeline.

Add tables for immutable complaint events, comments and notifications. Wrap complaint updates plus history/notification inserts in one database transaction. Record actor, timestamp, old/new status and priority; define a policy for preserving existing complaints with no backfilled event history. Permit owner/admin comments, with authorization on read and write. Add notification bell, unread count, list and CSRF-protected mark-as-read action. Escape all user text.

Acceptance: owner/admin see the same ordered history; student B cannot read or comment on student A's complaint; a failed update makes no history/notification entry; notifications are visible only to recipients and mark-as-read changes only their own rows. Test empty, long and malicious-looking comment inputs.

**Status:** migration `003_activity`, owner/admin conversation, transactionally recorded changes, bell/list/read actions implemented locally. MariaDB migration, browser cross-account and status/history checks passed. Invalid/HTML-like comment and rollback checks are recorded in `docs/activity.md`. Existing complaints intentionally have no fabricated historical events. Live deployment and TiDB staging remain pending.

### M5 — departments and technicians

Dependencies: M4. Add departments, technician role, assignment and explicit server-side status transitions. Test every role boundary and reassignment case.

### M6 — deadlines, reopen and rating

Dependencies: M5. Add priority targets, overdue indicators, owner-only reopen with a reason, notifications and rating/feedback rules across reopen cycles. Test lifecycle edges and reminder scheduling.

### M7 — email and account recovery

Dependencies: M4; Resend account, sender domain and Render secret configuration by the owner. Queue mail with retry, hashed expiring single-use tokens and rate limits; test with a fake transport first.

### M8 — analytics and exports

Dependencies: M5–M6. Define metrics from operational rows, filter by date/department/status, and export authorized PDF/CSV. Test known fixtures, empty sets and spreadsheet-safe CSV.

### M9 — design, localization and dark mode

Dependencies: stable UI from earlier milestones and authentic campus photos supplied by the owner. Keep labels translated, user-entered text unchanged, and verify mobile/keyboard/theme behaviour.

### M10 — location QR, map and duplicate review

Dependencies: M5 and structured locations. Add validated building/floor/room IDs, QR destinations, Leaflet map and privacy-safe duplicate suggestions.

### M11 — Google sign-in, then browser push

Dependencies: account model and provider setup. Implement and verify as separate integrations. Link accounts safely and clean up notification subscriptions on logout.

### M12 — optional AI assistance

Dependencies: consented provider settings and reviewed data minimization. Suggestions require human approval and a non-AI fallback.

### M13 — release review

Dependencies: requested feature milestones. Run syntax, automated, database, Docker and role-flow checks; update README, diagrams and deployment notes; prepare a reviewable PR. Promote only after staging checks and owner review.

## Test and release procedure

1. Use an isolated database with synthetic users and complaints; record counts and representative IDs before each migration.
2. Lint changed PHP files and run focused tests. Test migration from both empty and populated databases, including rerun and failure cases.
3. Test three roles and cross-account access in a browser. Use fake Cloudinary/mail transports in automated tests; record separately what was verified with live providers.
4. Review schema/data changes and a Render Docker build. Back up production before any production migration, deploy from a reviewed commit, and verify the live health and core complaint flow.
5. If deployment fails, restore the previous app version; if schema changes are backward-compatible, keep the migrated schema. Restore the database backup only when recovery requires it and after assessing newer writes.

## Open setup dependencies

- Cloudinary account and credentials must be configured by the owner in Render; do not send secrets in chat.
- The current local Git push of the earlier navigation change failed without diagnostic output. Confirm the remote branch/permissions before publishing any upgrade branch or relying on auto-deploy.
- Docker is not detected on this Windows host. Use the installed PHP and an isolated MariaDB for local checks; mark Docker build unavailable until a builder is available.
