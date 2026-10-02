# Complaint activity and notifications

Migration `003_activity.sql` adds three additive tables. `complaint_events` stores who changed a complaint and the old/new status and priority. `complaint_comments` stores owner/staff messages and one-level replies. `notifications` stores per-recipient bell entries and `read_at`. All refer to the existing complaint/user rows; complaint deletion cascades to its activity. A deleted author/actor is displayed as a deleted account.

Existing complaints retain their current status and priority. No earlier event is backfilled because the prior schema did not record actors or transition times. Their timeline starts with the next change. Newly submitted complaints get a creation event.

The administrator update locks the complaint and commits the current state, event and owner notification in one transaction. A no-op update creates no event or notification. A student edit that changes priority also records the old/new priority and notifies admins in one transaction. A comment and its recipient notifications commit together. Students may read and comment only on their own complaint; admins may do so on any complaint. Reply targets must belong to that complaint and be top-level comments. Comments are 2–2000 characters and displayed through `e()` HTML escaping. Mark-as-read uses POST, CSRF and a `recipient_id` predicate. The notification list does not expose complaint links to unauthorized users.

## Local verification, 2 October 2026

- MariaDB 10.4: migration applied to a populated copy (4 users, 8 complaints) and empty installation, then reran without pending migrations. Existing row counts stayed the same.
- PHP lint passed for all 44 application/test PHP files at this stage.
- Local browser using synthetic users: the owner and admin opened the detail view; another student could not post to that complaint; an admin Pending → In Progress update appeared in the owner's timeline; owner comment appeared for admin; admin received the in-app comment notification. These test records were removed and the local sample complaint restored.
- A one-character comment was rejected without adding a row. An HTML-like comment was displayed as text, with no injected script element. An invalid admin status added no event or notification. A different student submitted a mark-read request for a fixture notification; its `read_at` remained null. The owner then marked a notification read successfully. Test fixtures were removed.
- A top-level comment and its reply were saved; a reply to a nonexistent parent was rejected, leaving two rows. Test rows were removed.
- A student Pending-complaint edit from High to Low wrote one old/new priority event and an admin notification. The local sample was restored and test rows removed.
- Mobile detail page at 390 px was visually reviewed with the empty timeline/comment state.

Cloudinary live actions, TiDB staging, Docker build and production browser checks are still pending. None of these local checks should be treated as production acceptance.
