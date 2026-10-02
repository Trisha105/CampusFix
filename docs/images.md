# Protected image storage

CampusFix uses the official Cloudinary PHP SDK 3.1.3, installed from `composer.lock`. The Docker build installs Composer dependencies in a separate build stage and enables PHP cURL and mbstring. Set `CLOUDINARY_URL` in the Render Environment after creating a Cloudinary account; **never commit or send its value in chat**. Without it, normal complaint/profile features remain usable and image upload controls show an unavailable message.

The application accepts JPEG, PNG and WebP with a verified file signature and dimensions, up to 5 MiB each. At most three images are stored per complaint. Student uploads receive the `before` label while the complaint is Pending; administrator uploads receive the `after` label. Profile pictures are optional. Browser preview/removal is convenience only; all limits and permissions are checked again on the server.

Cloudinary uploads use delivery type `authenticated`. Both original and transformed assets require a signature. CampusFix does **not** embed signed Cloudinary URLs in HTML: `complaint_image.php` and `profile_image.php` first check the current session and database ownership/role, then fetch the signed image server-side and return its bytes with private, no-store headers. A guessed image ID or shared page URL does not grant another account access.

When an upload fails after earlier images have reached Cloudinary, the endpoint deletes those assets. If a Cloudinary delete fails after a database reference is removed, it records `media_cleanup_jobs`; run `php database/media_cleanup.php` from a trusted CLI/worker to retry. Do not expose that script as a web route. A provider and database outage at the same time may still require manual reconciliation from server logs. Do not claim perfect cross-service atomicity.

Official references checked on 2 October 2026: [Cloudinary PHP SDK](https://cloudinary.com/documentation/php_integration), [media access control](https://cloudinary.com/documentation/control_access_to_media), and [Upload API](https://cloudinary.com/documentation/image_upload_api_reference). Cloudinary states that `authenticated` protects originals and derivatives; `private` by itself does not protect derivatives by default.

## Verification record

- `php tests/media_validation.php`: valid PNG accepted; text masquerading as an image and an oversize file rejected.
- `php tests/media_cleanup.php` against an isolated database with a fake provider: failed delete was queued, failed retry incremented attempts, and successful retry removed the job.
- Local MariaDB: `002_images` applied to populated and fresh databases and reran as a no-op; 4 users and 8 complaints remained in the populated copy.
- Local browser with a synthetic image reference: guest request returned 403; another student returned 404. Owner and admin passed authorization but reached 503 because Cloudinary was not configured. The fixture was removed after testing.
- PHP syntax checks passed on all application files on 2 October 2026. The owner configured `CLOUDINARY_URL` in Render and the Render Docker build/deploy succeeded. Live upload, protected Cloudinary download, deletion and orphan cleanup remain unverified pending an authenticated production test. The Windows host itself has no Docker CLI.
