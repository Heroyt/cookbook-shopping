# Production Agent API Test Report

## Summary

Testing stopped safely during Gate 4. Public contract, authentication protection, and authenticated reads worked. Preview/apply could not be completed because the published OpenAPI contract does not contain enough information to construct a runtime-valid create operation.

No Cookbook or Meal Planning data was changed.

## Run information

- **Base URL:** `https://cookbook.internal.esoul.cz`
- **TEST_RUN_ID:** `cookbook-prod-20260811T215920Z-9b2090b4`
- **UTC interval:** `2026-08-11T21:59:20Z`–`2026-08-11T22:11:46Z`
- **Mode:** `apply`
- **Disposable test Family attested:** yes
- **Production mutations explicitly authorized:** yes
- **Destructive operations:** not tested
- **Credential exposure:** none; the token was not printed or persisted

## Gate 1 — Public contract

**Result: Passed**

- Documentation UI returned HTTP 200.
- OpenAPI JSON returned HTTP 200.
- OpenAPI version is `3.1.0`.
- Documented server URL is `https://cookbook.internal.esoul.cz/api/v1`.
- All required operations are documented:
  - `GET /catalog`
  - `GET /catalog/{resourceType}/{id}`
  - `GET /change-sets`
  - `GET /change-sets/{changeSet}`
  - `POST /change-sets`
  - `POST /change-sets/{changeSet}/apply`
- Production documentation UI has no credential input, authorization control, “Try it,” or request-sending control.
- No generated OpenAPI artifact is committed or assumed to exist in the repository.

## Gate 2 — Unauthenticated protection

**Result: Passed with an OpenAPI mismatch**

`GET /api/v1/catalog` without credentials returned:

- HTTP 401
- `error.code`: `authentication_required`
- `error.retryable`: `false`
- No Family data

### Mismatch

Runtime returns:

```json
{
  "error": {
    "details": []
  }
}
```

OpenAPI declares `error.details` as an object.

## Gate 3 — Credential and read isolation

**Result: Passed where observable**

- `GET /catalog`: HTTP 200
- `GET /change-sets`: HTTP 200
- Both responses passed schema validation.
- The disposable Family was empty.

### Catalog counts

| Resource type | Count |
|---|---:|
| `stores` | 0 |
| `store_sections` | 0 |
| `ingredients` | 0 |
| `recipe_tags` | 0 |
| `recipes` | 0 |
| `calendar_entries` | 0 |

### Filters

- `resource_type=stores`: HTTP 200, empty valid collection
- `status=active`: HTTP 200, empty valid collection
- One DNS-resolution timeout occurred before the successful resource-type-filter retry.

### Not testable with an empty catalog

- Aggregate detail
- Exact identifier preservation
- Canonical decimal serialization
- Relationship preservation
- Live `updated_at` preservation

Two-Family isolation was not tested because no known foreign identifier was supplied.

The credential successfully performed content reads and reached Cookbook preview validation without an authentication or ability error. Planning and destructive abilities were not tested.

## Gate 4 — Preview

**Result: Failed safely due to contract/runtime mismatches**

Three requests were submitted using distinct `client_request_id` values. No Family identifier was included.

### Attempt 1

OpenAPI-valid create operation without `local_ref` or `data`.

- HTTP 422
- `error.code`: `validation_failed`
- `error.path`: `/operations/0/local_ref`
- Message: `The local_ref field must be a non-empty string.`

### Attempt 2

Added the documented `local_ref`.

- HTTP 422
- `error.code`: `validation_failed`
- `error.path`: `/operations/0/data`
- Message: `A create operation requires a data object.`

### Attempt 3

Added the documented `data: {}` object.

- HTTP 422
- `error.code`: `validation_failed`
- `error.path`: `/operations/0/data`
- Message: `A create operation requires a data object.`

### Contract problems blocking preview

1. OpenAPI makes `local_ref` optional and nullable, but runtime requires it for create operations.
2. OpenAPI makes `data` optional and nullable, but runtime requires it for create operations.
3. Runtime rejects an OpenAPI-valid empty object as not being a data object.
4. OpenAPI provides no action-specific schemas, required resource fields, or examples for operation `data`.
5. Constructing a non-empty store payload would require guessing undocumented fields, which the test contract explicitly prohibits.

No preview was created. Therefore there was no preview ID, digest, expiry, warning, canonical request, effect list, or identifier mapping to validate.

## Gate 5 — Apply

**Result: Skipped**

Apply was explicitly authorized, but it could not proceed because Gate 4 produced no valid preview ID or digest.

The following were consequently not tested:

- Warning acknowledgements
- Apply response
- Apply idempotency retry
- Immutable result
- Identifier mappings
- Applied catalog state

## Side-effect verification

**Result: Passed**

After all rejected preview requests:

- Catalog remained empty and byte-for-byte unchanged.
- Change Set history remained empty and byte-for-byte unchanged.
- No preview was persisted.
- No Cookbook or Meal Planning mutation occurred.

## Rate limiting

Observed headers:

- Catalog/read limit: `120`
- Preview limit: `20`
- No HTTP 429 occurred.
- `Retry-After` behavior was not exercised.
- Rate limits were not bypassed.

## Recommended development actions

1. Correct the `AgentApiError.details` schema to match runtime, or return an object consistently at runtime.
2. Express action-dependent requirements for `local_ref`, `resource_id`, `expected_updated_at`, `data`, `set`, and `unset` in OpenAPI.
3. Publish resource- and action-specific `data` schemas for all supported operations.
4. Add valid request examples for every resource/action combination.
5. Fix empty JSON-object handling so `{}` is recognized as an object, or document the required resource fields so an empty object is never presented as valid.
6. Repeat preview and apply testing after the production OpenAPI contract is updated.

## Continuation instructions after deployment

Give the following instructions to the production testing agent after deploying the contract/runtime fixes. The previous passed gates remain accepted evidence and must not be repeated.

```text
Continue the production Agent API test recorded in
docs/agent-api-production-test-report-2026-08-11.md against
https://cookbook.internal.esoul.cz.

The User authorizes continuation in apply mode. A sample Store, Store Section,
Ingredient, Recipe Tag, and Recipe now exist in the credential's disposable
Family. Preserve those sample records: they may be read and referenced in a
side-effect-free preview, but must not be updated, archived, restored, or
deleted. Never send a Family identifier.

Treat these earlier results as accepted evidence and do not rerun them:

- Gate 1 public documentation availability, route inventory, OpenAPI version,
  server URL, production renderer safety, and absence of a generated artifact;
- Gate 2 authentication protection except for the one unresolved details-shape
  regression below;
- Gate 3 successful authenticated Catalog and Change Set reads, filters, and
  credential-derived Family scope where previously observable; and
- rejected-preview side-effect safety and the observed rate-limit headers.

Use a fresh TEST_RUN_ID and never print, log, persist, or include the bearer
secret in the report. Record UTC timestamps, HTTP status, stable error code,
and only the minimum redacted response fields required as evidence. Stop safely
on any unexpected mutation or contract mismatch.

Start with only these targeted deployment regressions:

1. Fetch `/docs/agent-api/v1/openapi.json` once as test input, not as a repeat of
   Gate 1. Confirm the deployed `AgentChangeSetOperation` contains 20 `oneOf`
   resource/action variants and `AgentChangeSetDocument` contains 20 examples.
   Confirm `CreateStoreOperation` requires `operation_id`, `resource_type`,
   `action`, `local_ref`, and `data`, and its `data` requires `name`. If the old
   flat nullable operation schema is still served, stop and report that the
   deployment or Scramble cache was not rebuilt.
2. Make one unauthenticated `GET /api/v1/catalog` request solely to verify the
   unresolved regression: `error.details` must be a JSON object (`{}`), not an
   array (`[]`). Do not repeat the remaining Gate 2 assertions.

Then complete the previously untestable Catalog evidence using the existing
sample records:

3. With the Agent Credential, fetch `GET /api/v1/catalog`. Locate exactly one
   intended sample of each available type by its returned name: Store, Store
   Section, Ingredient, Recipe Tag, and Recipe. Do not guess identifiers.
4. Fetch aggregate detail for each located identifier through
   `GET /api/v1/catalog/{resourceType}/{id}`. Verify exact identifier and
   ordinary `updated_at` preservation, canonical quantities as decimal strings,
   active/archive status, Store/Section placement, Store section order, Recipe
   Ingredient references and quantities, Recipe Tag assignments, and all other
   relationships actually configured on the samples. Record absent optional
   relationships as observed data, not failures.
5. Two-Family isolation still requires a known foreign identifier. Do not invent
   or probe identifiers. Leave that production-only evidence explicitly
   unresolved unless the User supplies a foreign Family resource identifier.

Resume at Gate 4 using the deployed contract rather than hand-written guesses:

6. Select the published full-document `stores/create` example and replace only:
   - `client_request_id` with a fresh value containing TEST_RUN_ID;
   - `operation_id` and `local_ref` with fresh run-scoped values; and
   - `data.name` with a unique run-scoped name such as
     `API test <TEST_RUN_ID>`.
   Keep `store_section_ids` as an empty array. Do not include null envelope
   fields and do not include a Family identifier.
7. Submit `POST /api/v1/change-sets`. Require HTTP 201 and verify status
   `previewed`, canonical request, effects, execution order, 64-character digest,
   expiry, warnings, operation count, payload bytes, and no Catalog mutation.
   Verify the created preview is visible through both Change Set list and detail.
8. Retry the identical preview request with the same `client_request_id` and
   require the documented idempotent HTTP 200 response with the same Change Set
   identifier and digest. Then reuse that `client_request_id` with a different
   payload and require the documented idempotency-conflict response without a
   second preview or Catalog mutation.

Complete Gate 5 only from that successful preview:

9. Submit `POST /api/v1/change-sets/{id}/apply` with the exact preview digest and
   exactly the returned warning codes (an empty list when no warnings exist).
   Require HTTP 200, status `applied`, a mapping from the fresh `local_ref` to an
   integer resource identifier, and a complete immutable result for the created
   Store.
10. Retry the identical apply request and require the same successful immutable
    result without a second Store.
11. Verify the newly created Store through Catalog list and aggregate detail,
    including its exact identifier, name, active status, empty section order,
    and `updated_at`. Verify applied Change Set history retains the canonical
    request, preview, acknowledgements, identifier mapping, result, credential,
    issuer, and provenance.

Do not delete the applied test Store or its history unless the User separately
authorizes destructive production cleanup. Do not test credential management,
Family mutations, Simple Plans, Shopping Generation, Saved Shopping Lists,
media, or excluded API resources.

Append a new dated continuation section to the existing report. Preserve the
original Gate 1–4 evidence verbatim. Report targeted regression results,
previously missing Catalog evidence, Gate 4 preview/idempotency, Gate 5
apply/idempotency, retained test artifacts, rate-limit observations without
deliberately exhausting limits, and genuinely unresolved evidence. If any step
fails, stop, verify whether the fresh run caused a side effect, and report the
exact stable machine error without guessing a replacement request shape.
```

## Continuation — 2026-08-11 UTC

### Summary

The targeted deployment regressions passed, and the previously missing Catalog evidence was collected from the disposable Family's five existing sample records. Gate 4 then created a valid side-effect-free preview, but testing stopped before idempotency and apply because runtime returned a 26-character ULID for a Change Set whose deployed OpenAPI schema declares `format: uuid`.

The Catalog remained byte-for-byte unchanged. No Store or other Cookbook or Meal Planning record was created, updated, archived, restored, or deleted. The unapplied preview remains retained temporarily according to the configured preview lifecycle.

### Run information

- **TEST_RUN_ID:** `cookbook-prod-cont-20260811T225606Z-4cc9b388`
- **UTC interval:** `2026-08-11T22:56:06Z`–`2026-08-11T23:03:50Z`
- **Mode:** `apply`
- **Disposable test Family attested:** yes
- **Production mutations explicitly authorized:** yes
- **Destructive operations:** not authorized or tested
- **Credential exposure:** none; the token was not printed, logged, persisted, or included in this report

### Targeted deployment regressions

**Result: Passed**

- OpenAPI test-input fetch: HTTP 200.
- `AgentChangeSetOperation.oneOf` contains 20 resource/action variants.
- `AgentChangeSetDocument.examples` contains 20 full-document examples.
- `CreateStoreOperation` requires `operation_id`, `resource_type`, `action`, `local_ref`, and `data`.
- `CreateStoreOperation.data` requires `name` and documents `store_section_ids`.
- The old flat nullable operation schema is absent.
- Targeted unauthenticated Catalog request: HTTP 401, `authentication_required`.
- `error.details` is now a JSON object (`{}`), resolving the earlier array/object regression.

### Catalog evidence

**Result: Passed where configured**

- Catalog list: HTTP 200.
- Exactly one named sample was present for each required type: Store, Store Section, Ingredient, Recipe Tag, and Recipe.
- Aggregate detail returned HTTP 200 for all five samples.
- Exact identifiers, names, active statuses, and ordinary `updated_at` values were preserved between list and detail.
- Store Section order contains the single sample Section at position `0`; Store and Section references are bidirectional.
- Ingredient package weight and piece count are canonical decimal strings; volume is absent.
- The Ingredient Store Placement object has null Store and Section identifiers, so the sample is observed as unassigned.
- The Ingredient has no Alternatives, description, or Nutrition Profile.
- The Recipe has one Ingredient reference whose identifier matches the sample Ingredient. Its quantity is a canonical decimal string and its quantity kind is `piece`.
- The Recipe and Recipe Tag references are bidirectional.
- Recipe base servings are a canonical decimal string.
- The Recipe has two ordered Steps at positions `1` and `2`.
- Recipe cooking duration, notes, nutrition override, and source URL are absent optional data.
- No known foreign-Family identifier was supplied; Two-Family isolation remains unresolved and was not probed.

### Gate 4 — Preview

**Result: Preview created; further testing stopped on a contract mismatch**

- The unique published full-document `stores/create` example was selected.
- Only `client_request_id`, `operation_id`, `local_ref`, and `data.name` were replaced with fresh run-scoped values.
- `store_section_ids` remained an empty array.
- The request contained no null envelope fields and no Family identifier.
- Preview submission: HTTP 201.
- Status: `previewed`.
- Operation count: `1`.
- Preview contains one effect, no dependencies, correct execution order, and no warnings.
- Digest is a 64-character lowercase hexadecimal string.
- Expiry and creation timestamps were returned as date-time strings.
- Payload byte count was returned as an integer.
- Canonical request exactly matches the submitted run-scoped document.
- Identifier mappings and result are null before apply, as expected.
- Credential and issuer metadata are present without exposing them in this report.
- The preview is visible exactly once through Change Set list and is readable through Change Set detail; detail preserves the same digest, canonical request, preview, and `previewed` status.
- Catalog remained byte-for-byte unchanged after previewing.

### Blocking contract mismatch

The deployed OpenAPI schema declares `AgentChangeSet.id` as:

```json
{
  "type": "string",
  "format": "uuid"
}
```

Runtime returned a 26-character ULID. No machine error was returned because preview creation otherwise succeeded. Testing stopped immediately after safe read-only side-effect verification.

The following Gate 4 checks were skipped:

- Exact preview idempotency retry
- Reused-identifier conflict response
- Verification that the conflict creates no second preview

### Gate 5 — Apply

**Result: Skipped due to the blocking Gate 4 contract mismatch**

No apply request was sent. Consequently, apply idempotency, identifier mapping, immutable result, created-Store Catalog state, and applied-history retention remain untested.

### Retained test artifacts

- One unapplied `previewed` Change Set from this continuation remains retained temporarily.
- No run-scoped Store was created.
- All five original sample records remain unchanged.
- No destructive cleanup was attempted.

### Rate-limit observations

- Catalog and Change Set reads returned a limit of `120`.
- Preview creation returned a limit of `20`.
- No HTTP 429 occurred, and limits were not deliberately exhausted.
- `Retry-After` was not exercised.

### Unresolved evidence

1. Align the documented Change Set identifier format with runtime: return a UUID or document the ULID schema accurately.
2. After deployment, resume with the exact-preview retry and differing-payload conflict check before applying.
3. Complete Gate 5 apply, retry, resulting Catalog detail, and immutable-history verification only after Gate 4 is contract-consistent.
4. Two-Family isolation still requires a known foreign-Family resource identifier supplied specifically for that test.

## Continuation instructions after the ULID contract deployment

Give the following instructions to the production testing agent after deploying
the Change Set identifier schema correction. All evidence already marked passed
in the two preceding runs remains accepted and must not be repeated.

```text
Continue the production Agent API test recorded in
docs/agent-api-production-test-report-2026-08-11.md against
https://cookbook.internal.esoul.cz.

The User authorizes continuation in apply mode. Preserve the existing sample
Store, Store Section, Ingredient, Recipe Tag, and Recipe. They may be read but
must not be updated, archived, restored, or deleted. The previously previewed
run-scoped Store and, if necessary, one replacement run-scoped Store may be
created by apply. Never send a Family identifier.

Do not rerun any passed public-route, authentication, Catalog, aggregate-detail,
relationship, canonical-decimal, operation-schema, example, error-envelope, or
side-effect-free-preview checks. Do not deliberately exhaust rate limits. Use
the existing Agent Credential without printing, logging, persisting, or adding
its bearer secret to the report.

Use a fresh continuation TEST_RUN_ID and record UTC timestamps. Start with only
this targeted deployment regression:

1. Fetch `/docs/agent-api/v1/openapi.json` once as test input. Confirm:
   - `AgentChangeSet.id` is a string with `minLength: 26`, `maxLength: 26`, and
     pattern `^[0-7][0-9A-HJKMNP-TV-Z]{25}$`;
   - it no longer declares `format: uuid`; and
   - both `AgentChangeSet.supersedes_id` and
     `AgentChangeSetDocument.supersedes_id` use the same length and pattern and
     do not declare `format: uuid`.
   If any UUID declaration remains, stop and report that deployment or the
   production Scramble cache was not rebuilt.

Resume the uncompleted Gate 4 evidence from the retained preview:

2. Read `GET /api/v1/change-sets` and locate the single Change Set whose
   `client_request_id` belongs to the prior run
   `cookbook-prod-cont-20260811T225606Z-4cc9b388`. Do not guess its identifier.
   Fetch its detail, confirm its returned identifier matches the deployed ULID
   pattern, and record its status and expiry.
3. If the retained Change Set still exists, resubmit its exact
   `canonical_request` unchanged. Require HTTP 200 with the same Change Set
   identifier and digest and no second preview. Then reuse the same
   `client_request_id` with only the run-scoped Store name changed. Require HTTP
   409 `idempotency_conflict`, no second preview, and no Catalog mutation.
4. If scheduled cleanup has already removed the retained Change Set, record
   that the old retry could not be observed. Create one fresh `stores/create`
   preview from the published example using fresh run-scoped
   `client_request_id`, `operation_id`, `local_ref`, and Store name. Immediately
   perform the identical HTTP 200 retry and differing-payload HTTP 409 conflict
   check against that fresh preview. Do not repeat the already-passed preview
   field audit beyond recording the identifier, digest, warnings, and expiry
   required for apply.

Complete Gate 5 from a live, unexpired preview:

5. If the retained preview is still `previewed` and unexpired, use it. Otherwise
   create one fresh `stores/create` preview as described in step 4 with a new
   request identity. Never attempt to apply an expired or terminal preview.
6. Submit `POST /api/v1/change-sets/{id}/apply` with the exact digest and exactly
   the preview warning codes, using an empty array when there are no warnings.
   Require HTTP 200, status `applied`, a mapping from the run-scoped `local_ref`
   to one integer Store identifier, and a complete immutable result.
7. Retry the identical apply request. Require the same HTTP 200 immutable result
   and identifier mapping, with no second Store.
8. Verify the created Store through Catalog list and aggregate detail: exact
   mapped identifier, run-scoped name, active status, empty Section order, and
   ordinary `updated_at`. Verify applied Change Set list/detail retains the
   canonical request, preview, acknowledgements, identifier mapping, result,
   credential, issuer, lineage/provenance, and `applied` status.

Do not delete the created Store, the applied history, or the older retained
preview unless the User separately authorizes destructive cleanup. Do not test
credential management, Family mutations, excluded API resources, or the
already-passed gates. Two-Family production isolation remains explicitly
unresolved unless the User supplies a known foreign-Family identifier; never
probe or invent one.

Append a new dated continuation section to this report and preserve all earlier
evidence verbatim. Report the targeted ULID regression, preview idempotency and
conflict behavior, whether the retained preview was reusable or replaced,
apply/idempotent retry, resulting Catalog and immutable history, retained test
artifacts, non-exhaustive rate-limit observations, and genuinely unresolved
evidence. On any mismatch, stop, check for side effects, and report the stable
machine error without guessing another request shape.
```
