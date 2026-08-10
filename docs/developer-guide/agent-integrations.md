# Agent Integrations

No Agent API, MCP server, Agent Credential, Agent Change Set, Sanctum token authentication, or Scramble-generated API contract exists in the repository today. This chapter records the approved design to add agent access after the Cookbook and Meal Planning model is implemented. Canonical definitions live in the final Domain Glossary chapter.

## Integration choice and boundary

> **Planned**
>
> Provide a versioned REST/JSON API described by OpenAPI as the primary AI-agent integration. Do not introduce an MCP server in the first implementation. The API has the smaller operational and authentication surface for trusted agents configured manually by a Family member, remains usable by ordinary HTTP clients, and gives agents a machine-readable contract. If MCP becomes useful later, implement a thin adapter over the same application services rather than a second domain boundary. [ADR 0008](../adr/0008-use-an-api-first-boundary-for-ai-agent-integrations.md) records this choice.
>
> The calling agent extracts and interprets webpages, PDFs, images, and free text, then submits canonical structured data. The application does not fetch remote sources, crawl, perform OCR, or invoke an AI model. This keeps prompt-injection, SSRF, model selection, and extraction failures outside the application. The agent may retain source URLs and a note as Change Set provenance. [ADR 0011](../adr/0011-keep-source-extraction-outside-the-agent-api.md) records the ingestion boundary.
>
> The v1 API covers these Family-owned resources:
>
> - Store and Store Section;
> - Ingredient and its package quantities, Nutrition Profile, Store Placement, and direct Alternatives;
> - Recipe Tag;
> - Recipe and its Ingredient lines, Steps, Tags, nutrition override, and source metadata; and
> - Calendar Entry.
>
> Nested package quantities, nutrition, alternatives, Store section order, Recipe Ingredients, Recipe Steps, and Recipe Tags are parts of their owning API aggregate. Family Access, Users, memberships, Agent Credential management, Simple Plans, Shopping Generation, Saved Shopping Lists, and media uploads are outside v1. An Agent API request can never create or select a Family.

## Authorized Family context

> **Planned**
>
> Refactor Family Access to produce one immutable Authorized Family Context containing the acting User and Family after verifying live membership. The interactive adapter resolves it from the User's Current Family. The Agent Credential adapter resolves it from the authenticated credential's fixed Family. Cookbook and Meal Planning actions accept this context instead of reading `users.current_family_id` themselves.
>
> Never switch the issuing User's Current Family to serve an API request, accept a client-supplied Family identifier, or duplicate web actions for agents. Both adapters enter the same application actions and therefore enforce the same normalized-name uniqueness, lifecycle, relationship, and Family-isolation rules. [ADR 0035](../adr/0035-pass-an-authorized-family-context-to-domain-actions.md) records this seam.

## Agent Credentials

> **Planned**
>
> Use Laravel Sanctum personal access tokens with a custom token model as Agent Credentials. Sanctum supplies hashed bearer secrets, ability checks, expiry, last-use tracking, and revocation; the custom model adds the issuing User, fixed Family, display name, and retained non-secret lifecycle metadata. Passport and OAuth are unnecessary while the clients are manually configured personal agents.
>
> Every credential:
>
> - belongs to exactly one issuing User and one Family in which that User has live membership;
> - derives API Family scope only from that association;
> - has a required display name, which need not be unique;
> - receives `content:read` and may additionally receive `cookbook:write`, `planning:write`, and `destructive:write`;
> - expires by default after 90 days and may not be issued for more than one year;
> - exposes its plaintext secret exactly once at creation or rotation; and
> - becomes unusable when revoked, expired, the issuer is deleted, or the issuer leaves the Family.
>
> `cookbook:write` authorizes non-destructive Cookbook creates and updates. `planning:write` authorizes non-destructive Calendar Entry changes. `destructive:write` is additionally required when the applicable domain action archives or deletes a record. Restoration requires the corresponding write ability. No ability grants Family Access, User, membership, or credential management through the API.
>
> The issuer may create and rotate a credential after recent password confirmation. Any current Family member may inspect non-secret metadata and revoke it immediately without password reconfirmation. Rotation replaces the secret, immediately revokes the old token, and invalidates every unapplied preview created by the old credential; the caller must preview again with the replacement. Revoked metadata is non-restorable and retained until the Family is deleted. [ADR 0009](../adr/0009-scope-agent-credentials-to-one-user-and-family.md) records the security trade-off.

## Catalog reads

> **Planned**
>
> Give agents a compact, complete Family Catalog so they can resolve stable API identifiers without guessing by name:
>
> - `GET /api/v1/catalog` returns the Family's supported resources, with optional resource-type and status filters;
> - `GET /api/v1/catalog/{resource_type}/{id}` returns one complete aggregate; and
> - active and archived records are included by default and carry explicit status.
>
> The household-sized catalog is deliberately unpaginated. It may omit presentation-only expansion, but it must contain the identifiers, normalized identity, relationships, canonical units, lifecycle status, and ordinary `updated_at` values required to construct a Change Set. Use integer API identifiers for existing records. Do not expose storage paths or credential secrets.

## Agent Change Set protocol

> **Planned**
>
> All mutation enters through one two-step protocol:
>
> 1. `POST /api/v1/change-sets` validates an immutable request and returns its preview, effects, stable warnings, and digest.
> 2. `POST /api/v1/change-sets/{id}/apply` atomically applies that exact digest after the caller acknowledges every warning code.
>
> `GET /api/v1/change-sets` lists Family-visible previews and history, and `GET /api/v1/change-sets/{id}` returns one record. Any credential with `content:read` may inspect all Change Sets for its Family, but only the exact credential that created an unapplied preview may apply it. Agents cannot delete history.
>
> A request contains a required client request identifier, an unordered list of typed operations, and optional title, source URLs, and note. Each operation has a caller-defined `operation_id`. A create also has a request-unique `local_ref`; later operations may refer to it. Existing records are referenced only by integer API identifier. Do not fuzzy-match names or silently reuse a record with the same normalized name.
>
> Supported operation actions are lowercase `create`, `update`, `archive`, `restore`, and `delete` where the resource's normal domain lifecycle permits them. Recipe and Ingredient use archive and restore rather than individual hard deletion. Store, Store Section, Recipe Tag, and Calendar Entry use their domain-approved deletion actions. Every operation is handled explicitly; there is no generic Eloquent mutation endpoint.
>
> Updates separate `set` values from an `unset` field list. A `set` value cannot be null, omitted fields are unchanged, and `unset` explicitly clears an optional scalar. When a nested collection is supplied, it replaces that collection wholesale; an allowed empty collection clears it. Omitted nested collections remain unchanged. The preview rejects any replacement that violates an aggregate invariant.
>
> The canonical request uses English `snake_case` field names, lowercase action and status values, decimal quantities encoded as strings, RFC 3339 UTC technical timestamps, `YYYY-MM-DD` Calendar dates, integer-minute durations, and the exact Czech Meal Label values from the glossary.

## Preview, apply, and idempotency

> **Planned**
>
> Preview is side-effect-free. Explicit resource handlers load Family-owned state, validate cross-operation dependencies and domain invariants, and construct proposed effects without executing and rolling back the real mutation actions. Invalid requests return a structured `422` response and are not persisted. A valid preview is persisted with a digest of the canonical request.
>
> Apply rechecks authorization, credential validity, expected record timestamps, digest, expiry, and warning acknowledgements, then invokes the existing Cookbook and Meal Planning actions inside one database transaction. Either every operation commits or none does. The response maps create `local_ref` values to persisted API identifiers and returns the complete outcome.
>
> The required client request identifier provides credential-scoped idempotency. Reusing it with the same canonical payload returns the same preview or result; reusing it with a different payload is a conflict. An expired or stale request is never revised in place. The client submits a new request identifier and may link it to the previous Change Set through optional supersession metadata.
>
> A Change Set begins as `previewed` and becomes `applied`, `expired`, `invalidated`, or `stale`. Retryable server failures leave it `previewed`. Unapplied previews expire after 24 hours and are removed by scheduled cleanup after their terminal expiry is recorded. Revocation or rotation invalidates unapplied previews from that credential. Applied history is immutable until a Family member explicitly deletes it in the web interface; deletion never rolls back domain data.
>
> Preview returns every stable warning code. Apply includes the digest and acknowledgement of all returned warning codes; missing or obsolete acknowledgements reject the request. Warnings communicate accepted consequences, not a human approval queue. No separate human approval step or rollback capability is introduced.

## Concurrency contract

> **Planned**
>
> Catalog aggregates expose their ordinary `updated_at`. Update, archive, restore, and delete operations include the expected value, and preview and apply reject a mismatch as stale. This intentionally avoids separate version columns, microsecond requirements, or aggregate fingerprints for the personal project.
>
> This is a known limited guarantee: rapid writes within the database timestamp resolution, and nested changes that do not update their aggregate root, may escape detection. Preserve this accepted risk in tests and documentation rather than implying exact serializable optimistic concurrency. [ADR 0037](../adr/0037-use-updated-at-for-agent-concurrency.md) records the decision.

## Persistence and history

> **Planned**
>
> Keep the Agent Integration module deep: its small catalog, preview, apply, and history interfaces hide operation parsing, dependency ordering, idempotency, warnings, concurrency checks, transactions, persistence, and wire formatting. Use explicit handlers per resource and action. Controllers validate HTTP shape and delegate; they do not sequence domain models. [ADR 0036](../adr/0036-use-a-deep-agent-integration-module.md) records this module design.
>
> Store valid previews and applied history in one `agent_change_sets` table. Index relational metadata needed for authorization, lifecycle, and Family history filters. Store versioned JSON documents for the canonical request, preview and effects, warning acknowledgements, identifier mappings, and final result. History also retains credential and issuer provenance, timestamps, digest, outcome, optional title, source URLs, note, and optional supersession lineage.
>
> This is operational provenance rather than a general-purpose audit log. Agents may read Family history but cannot remove it. Any Family member may delete an applied record through the web interface, and deleting it does not reverse the underlying changes.

## Errors, limits, and rates

> **Planned**
>
> Return stable machine-readable error codes with an English message, JSON path, operation reference where applicable, and structured details. Distinguish authentication, ability, Family-scope, validation, name-conflict, stale-preview, digest, acknowledgement, idempotency, expiry, payload-limit, and rate-limit failures. Include retry guidance only where retrying can succeed.
>
> Process preview and apply synchronously. Start with configurable limits of 250 operations and 2 MiB of JSON per Change Set. Apply per-credential minute limits of 120 Catalog reads, 20 previews, and 10 apply attempts. Return ordinary rate-limit metadata so clients can back off. These limits protect accidental agent loops without introducing queues into the personal deployment profile.

## OpenAPI and versioning

> **Planned**
>
> Add `dedoc/scramble` and generate an OpenAPI 3.1 contract from versioned Laravel routes, Form Requests, response resources, and `auth:sanctum` middleware. Treat executable validation and typed responses as the contract source; add explicit Scramble schema customization where inference is incomplete. Do not maintain a second handwritten schema.
>
> Publish the documentation UI at `/docs/agent-api/v1` and JSON contract at `/docs/agent-api/v1/openapi.json`. Both documentation endpoints are public, while all data endpoints require an Agent Credential. Disable interactive requests in production so long-lived bearer secrets are not pasted into the public renderer. Cache the runtime contract in production and rebuild that cache during deployment. Do not export or commit a generated OpenAPI artifact.
>
> Make additive changes within v1. A breaking contract creates `/api/v2` and a separate generated contract; retain v1 for a 90-day migration window. [ADR 0034](../adr/0034-generate-the-agent-api-contract-with-scramble.md) records this choice.

## Family management interface

> **Planned**
>
> Add two responsive Current Family screens, with all user-facing copy in Czech:
>
> - **Agent Access** shows family-wide non-secret credential metadata. The issuer can create and rotate a credential after password confirmation; any member can revoke one immediately. Creation and rotation show the new secret once with a clear copy-and-store warning.
> - **Agent Change History** lists applied Change Sets and filters by credential, issuer, date, resource type, and outcome. Any member can inspect a record and delete its history through a consequence-stating confirmation dialog.
>
> These screens manage the integration, not domain data. Preview and apply are agent-driven API operations and do not require a human approval inbox.

## Delivery sequence and verification

> **Planned**
>
> Implement Agent Integration only after Stores, Store Sections, Ingredients, Recipe Tags, Recipes, and Calendar Entries have complete domain actions and tests. Then deliver in this order:
>
> 1. Refactor the existing web actions to accept Authorized Family Context and prove web behavior and cross-Family isolation remain unchanged.
> 2. Add Sanctum and the custom Agent Credential model, abilities, expiry, issuer-membership invalidation, rotation, revocation, and Agent Access UI.
> 3. Add the read-only Catalog and complete aggregate resources.
> 4. Add Agent Change Set persistence, canonicalization, explicit preview handlers, idempotency, lifecycle, structured errors, and expiry cleanup.
> 5. Add transactional apply handlers that invoke the existing domain actions, plus warning acknowledgement and identifier mapping.
> 6. Add Family Change History UI and retention/deletion behavior.
> 7. Add Scramble, versioned contract generation, production caching, public documentation routes, and deployment cache rebuilding.
>
> Cover authentication and ability matrices, issuer-membership invalidation, secret rotation, two-Family isolation, catalog serialization, every resource/action handler, local-reference dependencies, normalized-name conflicts, nested replacement semantics, warning acknowledgements, digest binding, idempotent retries, stale and expired previews, atomic rollback, concurrent apply attempts, history visibility/deletion, limits, rate limiting, and generated OpenAPI routes with focused PHPUnit tests. Use frontend Vitest and browser coverage for credential and history screens. Verify migration and JSON behavior on SQLite and the selected MariaDB version.
