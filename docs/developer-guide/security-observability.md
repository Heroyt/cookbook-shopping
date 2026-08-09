# Security and Observability

## Implemented authentication controls

The application uses Laravel's session guard and Eloquent `User` provider. Fortify handles email-based authentication and password recovery, and the application includes passkey registration and verification components. Protected routes apply `auth`. Several routes also attach `verified`, but the current `User` does not implement Laravel's email-verification contract and Fortify does not enable email verification, so that middleware does not currently enforce verified email addresses.

Relevant controls and defaults include:

- Password-update throttling at six attempts per minute in [settings routes](../../routes/settings.php).
- Lowercased login email identifiers and a 60-minute password-reset expiry in [Fortify](../../config/fortify.php) and [authentication configuration](../../config/auth.php).
- HTTP-only, same-site `lax` session cookies by default in [session configuration](../../config/session.php).
- JSON session serialization and disabled cache object unserialization in [session configuration](../../config/session.php) and [cache configuration](../../config/cache.php).
- JSON exception responses for API-style requests in [application bootstrap](../../bootstrap/app.php).

Production must set `APP_DEBUG=false`, use HTTPS, set secure session-cookie behavior appropriately, and provide a stable `APP_URL`; passkey relying-party configuration is derived from that URL.

## Planned Family authorization

> **Planned**
>
> Authentication establishes User identity; it does not by itself authorize Family data. Every Family-owned query and mutation must be scoped through the authenticated User's Family Membership and the selected Current Family. No request-supplied Family or record identifier may bypass that membership check.
>
> The roleless model gives every Family member equal authority, including membership changes and Family deletion. Required controls include:
>
> - A membership check before selecting or reusing a Current Family.
> - Family-scoped route model binding or equivalent query constraints for every owned record.
> - Database constraints that prevent cross-Family relationships.
> - Explicit confirmation using the Family name before deletion.
> - Protection against removing the final Family Membership without deleting the Family.
> - Tests that use at least two Families and prove reads and writes cannot cross the boundary.

## Planned photos and files

> **Planned**
> Recipe cover photos, Ingredient photos, and Store logos are Family-owned data. The current default filesystem is local and no upload workflow exists. Before implementing uploads, choose either durable mounted storage or an S3-compatible object store, validate MIME type and size, generate server-controlled filenames, and authorize every non-public retrieval.
>
> Do not place sensitive Family media on an unauthenticated public disk by default. File deletion must be coordinated with record replacement, archiving, and Family deletion. Feature tests should use Laravel filesystem fakes.

## Secrets and configuration

Environment files and runtime secret stores are configuration sources, never documentation sources. Do not copy real values into logs, screenshots, issue descriptions, or this guide.

The production Docker build currently copies `.env.docker` into a build layer before running Laravel configuration caching. That file is tracked and therefore must contain only non-secret build defaults. Runtime credentials—including `APP_KEY`, database credentials, mail credentials, object-storage keys, telemetry endpoints with credentials, and deployment credentials—should be injected by the deployment platform.

> **Planned**
> Remove build-time dependence on an application environment file. Build immutable code and assets, inject production configuration through Komodo/runtime secrets, and generate Laravel caches at container startup only after runtime configuration is present. Add a CI check that rejects known secret patterns and verifies production-required variables without printing values.

## Health and logs

Laravel exposes `/up` through [application bootstrap](../../bootstrap/app.php). It proves the framework can boot; it does not currently verify database, cache, queue, storage, or mail dependencies.

The default log stack writes to Laravel's configured channels. Development containers additionally write scheduler, queue-worker, and Supervisor logs beneath `storage/logs`. The production image starts the scheduler and PHP-FPM but does not configure a production queue worker inside the image.

> **Planned**
> Keep `/up` as a liveness check and add a deployment-specific readiness check for required dependencies without exposing sensitive details. Stream application and worker logs to container stdout/stderr or a centralized collector, define retention, and alert on HTTP error rate, failed jobs, queue age, migration failure, storage errors, and backup failure.

`.env.docker` defines OpenTelemetry-related variables, while development Compose disables PHP auto-instrumentation. No repository evidence proves that telemetry export is active in production. Treat OpenTelemetry as unverified until the deployed image and collector are observed.

## Audit and privacy limits

> **Planned**
> Saved Shopping Lists are user-managed snapshots, not audit records. The MVP has no approved audit log, pricing data, pantry inventory, allergens, or cross-Family data sharing. Avoid designing operational controls that imply those capabilities exist.
