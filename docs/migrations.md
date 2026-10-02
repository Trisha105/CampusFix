# Database migrations and recovery

Run migrations with `php database/migrate.php`. The Render container runs the same migration function in `database/bootstrap.php` before Apache starts. Files in `database/migrations/` are ordered by their version prefix and recorded with SHA-256 checksums in `schema_migrations`. Do not edit an applied migration; add a new numbered file. Run one migrator process at a time.

`001_baseline.sql` creates `users` and `complaints` on a fresh database. For an existing installation, the runner checks that both tables and required columns exist, then records the baseline without rewriting existing rows. A partial or incompatible schema stops startup for manual review. Later migrations must be additive and safe to rerun: MySQL/TiDB DDL may commit even when a later statement or ledger insertion fails.

## Before deployment

1. Take a database backup. For a TiDB Cloud Starter cluster, a current-point-in-time branch is an isolated recovery point; verify that it becomes Active. If using an export, verify the file is non-empty and store it outside the web root.
2. Record `SELECT COUNT(*) FROM users` and `SELECT COUNT(*) FROM complaints`, plus a few known IDs/codes. Plan a maintenance window for migrations that alter large tables.
3. Deploy one application instance/migrator. `database/bootstrap.php` stops the container when migration fails, so review its logs before retrying.
4. After migration, check `SELECT version, applied_at FROM schema_migrations ORDER BY version` and repeat the row counts.

Example commands with a compatible MySQL client (enter the password at the client prompt; substitute your own host, port, user and database):

```sh
mysqldump --host=DB_HOST --port=DB_PORT --user=DB_USER --password --ssl-mode=REQUIRED --databases DB_NAME > campusfix-before-upgrade.sql
php database/migrate.php
mysql --host=DB_HOST --port=DB_PORT --user=DB_USER --password --ssl-mode=REQUIRED DB_NAME -e 'SELECT version, applied_at FROM schema_migrations ORDER BY version;'
```

Client TLS flags can differ by MySQL/MariaDB client version. Use the CA/TLS options required by the installed client and TiDB connection page. Do not put a real password in a shell command, repository file or screenshot.

## Failure and recovery

- If a migration fails, **do not delete its ledger row or blindly rerun a partially applied DDL file**. Inspect the schema and logs, reconcile partial changes in a new reviewed migration or restore a backup in an isolated environment first.
- Roll back the application container to the previous commit if an additive migration leaves the old application compatible. Do not assume database DDL can be rolled back with `ROLLBACK`.
- Restoring the backup discards writes made after that backup. Coordinate a maintenance window and preserve a copy of the current database before restoration. Example: `mysql --host=DB_HOST --port=DB_PORT --user=DB_USER --password --ssl-mode=REQUIRED < campusfix-before-upgrade.sql`.
- Test recovery in a disposable database before any production restore. On 2 October 2026, a TiDB branch named `pre-upgrade-2026-10-02` was created and became Active before deployment. No export-file backup or restore exercise was run.

## Checks recorded on 2 October 2026

Local MariaDB 10.4.32, not production TiDB:

- Populated copy: 4 users and 8 complaints before and after baseline; `001_baseline` recorded once; second run reported no pending migrations.
- Fresh database: `users`, `complaints` and `schema_migrations` created; both operational tables empty; second run reported no pending migrations.
- Partial database with only `users`: migration exited 1 and reported manual review required; it did not create `complaints` or record the baseline.
- Production bootstrap on the fresh local database exited 0 and left both operational tables empty when no administrator credentials were configured.
- PHP lint passed for the runner, migration library and bootstrap.

The Render Docker build and startup succeeded on 2 October 2026. Production TiDB `test` had the existing `users` and `complaints` tables before deployment; afterwards the SQL editor showed nine tables and `001_baseline`, `002_images`, `003_activity` in `schema_migrations`. Current counts were two users and one complaint. Pre-upgrade counts were not recorded, so production row-count preservation is not asserted. Isolated TiDB rehearsal and restore remain unverified.
