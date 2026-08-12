# ADR 0009 — Scope Agent Credentials to one User and Family

Each Agent Credential is issued by one User for exactly one Family and is automatically revoked when that User is deleted or loses membership in that Family. Every credential receives `content:read`; its issuer may additionally select `cookbook:write`, `planning:write`, and `destructive:write`. It must expire within one year and defaults to 90 days. API requests derive their Family exclusively from the credential rather than a route parameter or the User's Current Family preference.

The credential never follows the User into other Families and cannot manage Families, Family Memberships, Users, or other Agent Credentials. The only credential-lifecycle operation available to the authenticated agent is a self-restriction command: it may move its own expiry earlier or revoke itself, but may never extend, restore, rotate, rename, change abilities, or target another credential. The command accepts no User, Family, or credential identifier. This gives trusted agents useful domain access with an accountable human principal and a bounded blast radius, at the cost of issuing a separate credential for each User-and-Family integration and periodically rotating its secret.

Only the issuing User may create or rotate the credential, and its plaintext secret is displayed once. Every current member of the affected Family may inspect non-secret metadata and revoke the credential so shared data access does not depend solely on the issuer remaining available.

Laravel Sanctum personal access tokens provide the bearer-token authentication, hashing, abilities, expiry, revocation, and last-use tracking. A custom Sanctum token model carries the Family association and Agent Credential metadata. Passport and OAuth are deliberately excluded while clients remain manually configured trusted agents.

Rotation immediately invalidates the old secret and every unapplied Change Set created through it. The replacement credential must preview those changes again rather than inheriting authority represented by the old secret.

Revocation retains the credential's non-secret metadata in a permanent, non-restorable revoked state until its Family is deleted. This keeps historical attribution intact without leaving a path to reactivate an old secret.

Self-revocation immediately invalidates every unapplied Change Set created by the credential and deliberately records no revoking User. Shortening is monotonic and idempotent: a requested timestamp at or after the current expiry is a successful no-op. The generated OpenAPI operation instructs the agent to report the returned status and timestamps to the User; the application sends no notification.

Users manage credentials on the Current Family's Agent Access page. A required display name may be duplicated and is disambiguated by credential identifier, issuer, creation time, expiry, and abilities. Creation and rotation require recent password confirmation; revocation deliberately does not so any current Family member can stop suspected access immediately.
