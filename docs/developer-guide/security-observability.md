# Security and Observability

## Implemented authentication controls

The application uses Laravel's session guard and Eloquent `User` provider. Fortify handles email-based authentication and password recovery, and the application includes passkey registration and verification components. Protected routes apply `auth`. Several routes also attach `verified`, but the current `User` does not implement Laravel's email-verification contract and Fortify does not enable email verification, so that middleware does not currently enforce verified email addresses.

Relevant controls and defaults include:

- Password-update throttling at six attempts per minute in [settings routes](../../routes/settings.php).
- Lowercased login email identifiers and a 60-minute password-reset expiry in [Fortify](../../config/fortify.php) and [authentication configuration](../../config/auth.php).
- HTTP-only, same-site `lax` session cookies by default in [session configuration](../../config/session.php).
- JSON session serialization and disabled cache object unserialization in [session configuration](../../config/session.php) and [cache configuration](../../config/cache.php).
- JSON exception responses for API-style requests in [application bootstrap](../../bootstrap/app.php).
- Operator-only User provisioning with hidden password prompts and application
  password validation; public registration remains disabled.

Production must set `APP_DEBUG=false`, use HTTPS, set secure session-cookie behavior appropriately, and provide a stable `APP_URL`; passkey relying-party configuration is derived from that URL.

## Family access and record authorization

Implemented Family Access routes require authentication. Current Family
selection is checked against live membership, membership mutations derive their
scope from that validated server-side selection, every member has equal rights,
final-membership removal is blocked, and Family deletion requires the exact
Family name. Focused tests reject selecting or removing membership through a
different Family. `CurrentFamilyScope` applies the same membership-validated
context to Store reads, creation, rename, and deletion; Store Section reads and
creation; and both-record resolution for association, removal, and reorder.
Every member has equal Cookbook-management rights. Store
rename and deletion resolve the route Store identifier
inside that scope, ignore a client-supplied Family identifier, and return not
found for a Store owned by another Family. Store and Store Section tests use
multiple Users and two Families to prove those equal rights, scoped reads and
writes, and ownership that cannot be redirected by client input. Store Section
tests also prove that normalized duplicate races become field validation
errors rather than unhandled database exceptions. Association tests reject a
foreign-Family Store or Section, incomplete orders, duplicate identifiers, and
stale order versions without changing persisted traversal order.

> **Planned**
>
> Authentication establishes User identity; it does not by itself authorize Family data. Every later Family-owned query and mutation must reuse the authenticated User's Family Membership and selected Current Family scope. No request-supplied Family or record identifier may bypass that membership check.
>
> The roleless model gives every Family member equal authority. Controls still required for Family-owned records include:
>
> - Family-scoped route model binding or equivalent query constraints for every owned record.
> - Database constraints that prevent cross-Family relationships.
> - Tests that use at least two Families and prove reads and writes cannot cross the boundary.

## Planned Agent API controls

> **Planned**
>
> Authenticate the Agent API with expiring Laravel Sanctum bearer tokens represented as Agent Credentials. Each credential belongs to one issuing User and exactly one Family, derives Family scope from that server-side association, and is automatically unusable when the issuer leaves the Family or the User is deleted. The plaintext secret is shown once; only its hash and non-secret metadata are retained.
>
> Require `content:read` on every credential and grant `cookbook:write`, `planning:write`, and `destructive:write` explicitly. No ability permits Family, membership, User, or credential administration over the API. Default expiry is 90 days and maximum expiry is one year. Creation and rotation require recent password confirmation; any current Family member may revoke a credential immediately without reconfirmation.
>
> Rotation immediately invalidates the previous secret and every unapplied Change Set preview created by it. Keep revoked metadata non-restorable until Family deletion. Rate-limit each credential independently, enforce the approved payload and operation limits before preview work, and never log bearer secrets or canonical request content that may contain sensitive Family data.
>
> Agent mutations use an immutable preview followed by digest-bound atomic apply. Only the credential that created a preview may apply it. Apply rechecks live issuer membership, abilities, expiry, record timestamps, warnings, and Family ownership inside the transaction. Tests must use multiple Users, Families, credentials, and abilities to prove that changing a route identifier or request body cannot widen scope. See [Agent integrations](agent-integrations.md) and [ADR 0009](../adr/0009-scope-agent-credentials-to-one-user-and-family.md).

## Planned photos and files

> **Planned**
> Recipe cover photos, Ingredient photos, and Store logos are entity-owned Family data. The selected backend is Laravel's private local disk persisted through the Komodo mount at `/var/www/storage/app`; S3 remains a future migration option. The first implementation retains one original file without generated variants. Replacement supersedes and removes the previous file after the database change commits; archiving retains the attachment; hard deletion and Family deletion remove it. No upload workflow exists yet. Validate MIME type and size, generate server-controlled filenames, and authorize every non-public retrieval when uploads are implemented.
>
> Media is excluded from the first Store/Ingredient and Recipe tracers. Before any upload implementation begins, approve and document the exact MIME allowlist, maximum byte size and image dimensions, corrupt/decode rejection behavior, and temporary-file cleanup. The lifecycle above does not supply those missing validation limits and must not be treated as an implementable upload policy.
>
> Do not place sensitive Family media on an unauthenticated public disk by default. File deletion must be coordinated with record replacement, archiving, and Family deletion. Feature tests should use Laravel filesystem fakes.

## Secrets and configuration

Environment files and runtime secret stores are configuration sources, never documentation sources. Do not copy real values into logs, screenshots, issue descriptions, or this guide.

The production Dockerfile does not copy environment files into the image, does
not cache Laravel configuration during the image build, and produces an image
without an application environment file. `.dockerignore` excludes `.env*` from
the build context except the non-secret `.env.example`; the Dockerfile's
selective copies do not include that exception. The entrypoint generates
Laravel caches only after runtime configuration is present. The tracked
[production environment example](../../.env.production.example) names required
settings without supplying secret values.

Runtime credentials—including `APP_KEY`, MariaDB credentials, mail credentials, object-storage keys, telemetry endpoints with credentials, and deployment credentials—must be injected by the deployment platform. The repository still needs a CI check that rejects known secret patterns and verifies production-required variables without printing values.

## Health and logs

Laravel exposes `/up` through [application bootstrap](../../bootstrap/app.php). It proves the framework can boot and is the selected minimal Komodo or proxy health signal for the single-container profile. It intentionally does not verify database, storage, or mail dependencies, because a dependency outage should not cause a restart loop.

The default log stack writes to Laravel's configured channels. Development containers additionally write scheduler, queue-worker, and Supervisor logs beneath `storage/logs`. The production example streams application logs to stderr, uses synchronous jobs, and starts PHP-FPM plus the existing cron entry without a queue worker. No centralized log, metric, trace, uptime, or alert destination is required for the personal project.

`.env.docker` defines OpenTelemetry-related variables, while development Compose disables PHP auto-instrumentation. The user has an OpenTelemetry stack available for later integration, but no repository evidence proves that telemetry export is active in production. Add dependency-aware readiness, centralized telemetry, or alerts only when operational needs justify them.

## Audit and privacy limits

> **Planned**
> Saved Shopping Lists are user-managed snapshots, not audit records. The MVP has no approved audit log, pricing data, pantry inventory, allergens, or cross-Family data sharing. Avoid designing operational controls that imply those capabilities exist.
>
> Applied Agent Change Set History is retained operational provenance, not a general-purpose audit log. Any Agent Credential with `content:read` may inspect its Family's history, no agent may delete it, and any Family member may delete it through the web interface. History deletion never reverses domain changes.
