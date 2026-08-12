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

5. Use the retained preview when it is still `previewed` and unexpired;
   otherwise use the live replacement created in step 4. Only when neither is
   available, create one fresh `stores/create` preview as described in step 4
   with a new request identity. Never attempt to apply an expired or terminal
   preview.
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

## Continuation — 2026-08-11 UTC (ULID and apply)

### Summary

The targeted ULID contract regression passed. The retained preview from the preceding continuation was still present, `previewed`, and unexpired, so it was reused without creating a replacement preview. Preview retry and conflict behavior passed, Gate 5 apply and its identical retry passed, and the resulting Store and immutable applied history were verified.

Exactly one authorized run-scoped Store was created. All pre-existing sample records remain unchanged. No replacement Store, destructive cleanup, credential management, Family mutation, or excluded-resource operation was attempted.

### Run information

- **TEST_RUN_ID:** `cookbook-prod-cont-20260811T231652Z-7a15ce5a`
- **UTC interval:** `2026-08-11T23:16:52Z`–`2026-08-11T23:28:01Z`
- **Mode:** `apply`
- **Disposable test Family attested:** yes
- **Production mutations explicitly authorized:** yes
- **Credential exposure:** none; the token was not printed, logged, persisted, or included in this report

### Targeted ULID deployment regression

**Result: Passed**

- OpenAPI test-input fetch: HTTP 200.
- `AgentChangeSet.id` is a string constrained to 26 characters with pattern `^[0-7][0-9A-HJKMNP-TV-Z]{25}$`.
- `AgentChangeSet.id` no longer declares `format: uuid`.
- `AgentChangeSet.supersedes_id` and `AgentChangeSetDocument.supersedes_id` use the same length and pattern while remaining nullable.
- Neither supersession field declares `format: uuid`.

### Gate 4 — Retained preview idempotency

**Result: Passed**

- Change Set list: HTTP 200.
- Exactly one Change Set matched the prior run identity `cookbook-prod-cont-20260811T225606Z-4cc9b388`.
- Change Set detail: HTTP 200.
- Its identifier matches the deployed ULID pattern.
- Its status was `previewed`, with expiry `2026-08-12T23:02:00Z`; it was unexpired when reused.
- Its warning-code list was empty.
- Resubmitting its exact canonical request returned HTTP 200.
- The retry returned the same Change Set identifier, digest, canonical request, preview, and `previewed` status.
- Change Set list still contained exactly one preview for that request identity.

### Gate 4 — Differing-payload conflict

**Result: Passed**

Only the run-scoped Store name was changed while reusing the original `client_request_id`.

- HTTP status: 409
- `error.code`: `idempotency_conflict`
- `error.message`: `The client_request_id was already used with a different canonical request.`
- `error.path`: `/client_request_id`
- `error.operation_id`: null
- `error.details`: JSON object
- `error.retryable`: `false`
- Change Set list still contained exactly one record for the request identity.
- Catalog remained byte-for-byte unchanged after the conflict.

### Gate 5 — Apply

**Result: Passed**

The retained preview was re-read immediately before apply and remained `previewed` and unexpired. The apply request used its exact 64-character digest and the exact returned warning-code list, which was empty.

- Apply HTTP status: 200
- Change Set status and outcome: `applied`
- Change Set identifier and digest match the preview.
- Warning acknowledgements are an empty array.
- Identifier mappings contain exactly the run-scoped `local_ref` mapped to one integer Store identifier.
- The immutable result has version `1`, outcome `applied`, one applied `stores/create` operation, and one complete Store resource.
- Result operation and resource identifiers both match the identifier mapping.
- Applied and terminal timestamps are present.

### Gate 5 — Identical apply retry

**Result: Passed**

- Retry HTTP status: 200
- The retry returned the same Change Set identifier and `applied` status.
- Identifier mapping, immutable result, applied timestamp, and terminal timestamp are identical to the first apply response.
- No second Store was created.

### Resulting Catalog state

**Result: Passed**

- Catalog list: HTTP 200.
- Created Store aggregate detail: HTTP 200.
- Catalog contains exactly one additional record and exactly one additional Store relative to the pre-apply baseline.
- Every original Catalog record is preserved byte-for-byte.
- Exactly one Store matches the mapped identifier and run-scoped name.
- Detail preserves the mapped identifier and run-scoped name.
- Resource type is `stores`; status is `active`.
- Store Section order is empty and its order version is `0`.
- `updated_at` is an ordinary date-time and exactly matches the Catalog list value.

### Immutable applied history

**Result: Passed**

- Applied Change Set list: HTTP 200 and exactly one match for the request identity.
- Applied Change Set detail: HTTP 200 and status `applied`.
- Detail retains the canonical request, preview, empty acknowledgements, identifier mapping, immutable result, applied timestamp, and terminal timestamp.
- Credential and issuer metadata remain present without being disclosed here.
- Title, source URLs, note, and nullable supersession lineage/provenance fields remain present.

### Retained test artifacts

- The prior retained preview is now the applied immutable Change Set history record.
- One run-scoped active Store created by that Change Set remains in the disposable Family.
- No replacement preview or replacement Store was required.
- The original sample Store, Store Section, Ingredient, Recipe Tag, and Recipe remain unchanged.
- No Store or history cleanup was attempted.

### Rate-limit observations

- Catalog and Change Set reads reported a limit of `120`.
- Preview retry and conflict requests reported a limit of `20`.
- Apply and apply-retry requests reported a limit of `10`.
- No HTTP 429 occurred, limits were not deliberately exhausted, and `Retry-After` was not exercised.

### Unresolved evidence

Two-Family production isolation remains unresolved because no known foreign-Family resource identifier was supplied. No identifier was guessed or probed.

## Final Two-Family isolation instructions

The User attests that the exact Catalog identifiers below belong to a different
Family from the Family fixed on the Agent Credential used in the preceding
production runs. The foreign Family has no Agent Change Set, so no foreign
Change Set identifier is available and none may be created solely for this
test.

Give the following instructions to the production testing agent. This is a
read-only final isolation pass; all previously passed gates remain accepted and
must not be repeated.

```text
Complete only the remaining Two-Family isolation evidence in
docs/agent-api-production-test-report-2026-08-11.md against
https://cookbook.internal.esoul.cz.

Use the existing Agent Credential. Never print, log, persist, or add its bearer
secret to the report. Never send a Family identifier. Do not create, preview,
apply, update, archive, restore, delete, or otherwise mutate anything.

The User attests that these exact identifiers exist in a different Family from
the credential's fixed Family:

- `stores`: 1, 2, 3, 4
- `store_sections`: 1, 2, 3, 4, 5, 6
- `ingredients`: 1, 2, 3, 4, 5
- `recipes`: 1, 2, 3
- `recipe_tags`: 2
- `calendar_entries`: 1

The User also attests that the foreign Family has no Agent Change Set and will
not create one. Therefore do not call a guessed Change Set detail route and do
not treat the unavailable foreign-history check as an application failure.

Use a fresh TEST_RUN_ID and record the UTC interval. Perform exactly this pass:

1. Fetch authenticated `GET /api/v1/catalog` once as the current-Family safety
   baseline. Require HTTP 200. Record a deterministic digest of the response
   body and the per-resource counts. Confirm none of the attested foreign
   `(resource_type, id)` pairs appears in that response. This request is only a
   baseline for isolation and side-effect comparison, not a repeat of the
   already-passed Catalog serialization gate.
2. Fetch authenticated `GET /api/v1/change-sets` once and record a deterministic
   digest and count as the current-Family history safety baseline. Do not inspect
   or disclose unrelated history contents beyond what is needed for the digest
   and count.
3. Issue exactly one authenticated aggregate-detail GET for every attested pair,
   using these exact paths and no others:

   - `/api/v1/catalog/stores/1`
   - `/api/v1/catalog/stores/2`
   - `/api/v1/catalog/stores/3`
   - `/api/v1/catalog/stores/4`
   - `/api/v1/catalog/store_sections/1`
   - `/api/v1/catalog/store_sections/2`
   - `/api/v1/catalog/store_sections/3`
   - `/api/v1/catalog/store_sections/4`
   - `/api/v1/catalog/store_sections/5`
   - `/api/v1/catalog/store_sections/6`
   - `/api/v1/catalog/ingredients/1`
   - `/api/v1/catalog/ingredients/2`
   - `/api/v1/catalog/ingredients/3`
   - `/api/v1/catalog/ingredients/4`
   - `/api/v1/catalog/ingredients/5`
   - `/api/v1/catalog/recipes/1`
   - `/api/v1/catalog/recipes/2`
   - `/api/v1/catalog/recipes/3`
   - `/api/v1/catalog/recipe_tags/2`
   - `/api/v1/catalog/calendar_entries/1`

4. Every detail request must return the same non-enumerating response:

   - HTTP 404;
   - `error.code` is `resource_not_found`;
   - `error.message` is `The requested Family resource was not found.`;
   - `error.path` and `error.operation_id` are null;
   - `error.details` is a JSON object;
   - `error.retryable` is false; and
   - there is no `data` member and no foreign resource attributes, names,
     relationships, or Family metadata anywhere in the body.

   `resource_not_found` is the expected isolation contract. Do not expect
   `family_scope_violation`: Catalog detail intentionally makes a foreign
   resource indistinguishable from a nonexistent resource in the credential's
   Family.
5. Do not probe adjacent, random, missing, or malformed identifiers. Do not
   retry a successful HTTP response. If a transient transport failure occurs,
   retry only the same attested URL once and record the transport failure.
6. After all 20 detail requests, fetch authenticated Catalog and Change Set list
   once more. Require the response digests and counts to match their respective
   baselines exactly. This proves the read-only isolation pass did not mutate
   current-Family domain data or history.
7. Record rate-limit headers without deliberately exhausting a limit. The pass
   uses 24 authenticated reads, so stop if the available read budget is not
   sufficient before starting. Do not bypass or race the limiter.

Stop immediately if any attested foreign detail returns a non-404 status,
contains `data`, exposes any resource or Family attribute, or if either safety
snapshot changes. Record the exact URL, status, stable machine error fields,
and minimal redacted evidence; do not continue to the remaining identifiers
and do not attempt a mutation.

Append a final dated section to the existing report while preserving every
earlier result verbatim. Report results grouped by resource type, confirm the
number of exact identifiers tested, the uniform non-enumerating response,
absence of data leakage, before/after snapshot equality, rate-limit
observations, and retained artifacts. Record foreign Change Set isolation as
not observable in production due to the User-attested absence of a foreign
Change Set, with no probing or test artifact created. If every supplied Catalog
identifier passes, state that production Two-Family Catalog isolation is
complete across all six v1 Catalog resource types.
```

## Final Two-Family isolation attempt — 2026-08-12 UTC

### Summary

The read-only Two-Family isolation pass stopped before any foreign-resource detail request because the existing Agent Credential was rejected on both required safety-baseline reads. No Catalog identifier was probed, no Change Set detail route was called, and no mutation-capable request was sent.

Production Two-Family Catalog isolation therefore remains incomplete. Zero of the 20 User-attested foreign identifiers were tested in this attempt.

### Run information

- **TEST_RUN_ID:** `cookbook-prod-isolation-20260812T080405Z-0b179b35`
- **UTC interval:** `2026-08-12T08:04:05Z`–`2026-08-12T08:06:21Z`
- **Mode:** read-only isolation pass
- **Credential exposure:** none; the token was not printed, logged, persisted, or included in this report

### Blocking baseline result

The two required baseline reads were issued as the initial pair and both failed authentication:

| Request | HTTP status | `error.code` |
|---|---:|---|
| `GET /api/v1/catalog` | 401 | `authentication_required` |
| `GET /api/v1/change-sets` | 401 | `authentication_required` |

Both responses returned the same stable machine error:

- `error.message`: `A valid Agent Credential is required.`
- `error.path`: null
- `error.operation_id`: null
- `error.details`: JSON object
- `error.retryable`: `false`

Because neither baseline returned HTTP 200:

- No current-Family Catalog digest or per-resource counts were recorded.
- No current-Family Change Set history digest or count was recorded.
- Available read-rate budget could not be confirmed; no rate-limit headers were returned.
- The 20 attested foreign Catalog detail requests were not started.
- Post-pass safety snapshots were not requested, so before/after digest equality was not observable.

### Foreign identifiers tested

| Resource type | Supplied identifiers | Tested |
|---|---:|---:|
| `stores` | 4 | 0 |
| `store_sections` | 6 | 0 |
| `ingredients` | 5 | 0 |
| `recipes` | 3 | 0 |
| `recipe_tags` | 1 | 0 |
| `calendar_entries` | 1 | 0 |
| **Total** | **20** | **0** |

No adjacent, random, missing, malformed, or guessed identifier was probed.

### Side effects and retained artifacts

- No create, preview, apply, update, archive, restore, delete, or other mutation request was sent.
- No foreign resource or Family metadata was returned because no foreign-detail request was attempted.
- The previously created run-scoped Store and immutable applied Change Set history were not modified or deleted.
- The existing sample Store, Store Section, Ingredient, Recipe Tag, and Recipe were not modified.
- Foreign Change Set isolation remains not observable because the User attests that the foreign Family has no Agent Change Set; no identifier was guessed and no test artifact was created.

### Required follow-up

Repeat this exact read-only isolation pass with a valid Agent Credential for the disposable test Family. Do not treat the prior successful authentication evidence as current credential validity because this attempt returned non-retryable `authentication_required` before isolation testing began.

## Final Two-Family isolation retry — 2026-08-12 UTC

### Summary

Production Two-Family Catalog isolation is complete across all six v1 Catalog resource types. All 20 exact User-attested foreign identifiers returned the same non-enumerating HTTP 404 response without resource or Family data leakage. Current-Family Catalog and Change Set history response bodies and counts were identical before and after the pass.

The pass was strictly read-only. It issued exactly 24 authenticated reads: two safety baselines, 20 exact foreign Catalog detail requests, and two safety snapshots. No mutation or Change Set detail request was sent.

### Run information

- **TEST_RUN_ID:** `cookbook-prod-isolation-retry-20260812T080826Z-62963bc9`
- **UTC interval:** `2026-08-12T08:08:26Z`–`2026-08-12T08:15:14Z`
- **Mode:** read-only isolation pass
- **Credential exposure:** none; the token was not printed, logged, persisted, or included in this report

### Current-Family safety baselines

Both baseline requests returned HTTP 200.

Catalog baseline:

- SHA-256: `7713d915aced8c713fbabdd955af6367a73c8e146674a8ac939ef4d6a4f315be`
- Total resources: `6`
- Stores: `2`
- Store Sections: `1`
- Ingredients: `1`
- Recipes: `1`
- Recipe Tags: `1`
- Calendar Entries: `0`
- Matches against the 20 attested foreign `(resource_type, id)` pairs: `0`

Change Set history baseline:

- SHA-256: `d9327aede2de7c142baa5a0b8361306cb40c490d203627347c7897bf1c2f4efa`
- Change Set count: `1`
- No unrelated history content was inspected or disclosed beyond the body digest and count.

### Foreign Catalog detail results

Every exact request returned HTTP 404 and passed the uniform non-enumerating error-envelope validation.

| Resource type | Exact identifiers tested | Passed |
|---|---|---:|
| `stores` | `1`, `2`, `3`, `4` | 4 |
| `store_sections` | `1`, `2`, `3`, `4`, `5`, `6` | 6 |
| `ingredients` | `1`, `2`, `3`, `4`, `5` | 5 |
| `recipes` | `1`, `2`, `3` | 3 |
| `recipe_tags` | `2` | 1 |
| `calendar_entries` | `1` | 1 |
| **Total** | **20 exact identifiers** | **20** |

No adjacent, random, missing, malformed, or guessed identifier was probed. No successful HTTP response was retried, and no transport retry was required. A local runner variable collision occurred after the first authorized `stores/1` response completed; that saved response was validated locally and the URL was not requested again. The remaining 19 exact URLs then ran once each.

### Uniform non-enumerating response

All 20 response bodies had the same SHA-256 digest:

`3d43b1ad59a9f0b750c428e34d4902a8680203db7ac50f9fe4a5088e826cd2f1`

Every body contained only the structured `error` member with these values:

- HTTP status: 404
- `error.code`: `resource_not_found`
- `error.message`: `The requested Family resource was not found.`
- `error.path`: null
- `error.operation_id`: null
- `error.details`: empty JSON object
- `error.retryable`: `false`

The top-level body contained no `data` member. The error object contained only `code`, `message`, `path`, `operation_id`, `details`, and `retryable`; therefore no foreign identifier, resource attribute, name, relationship, or Family metadata was exposed.

### Before/after safety equality

Both after-snapshot requests returned HTTP 200.

Catalog after-snapshot:

- SHA-256: `7713d915aced8c713fbabdd955af6367a73c8e146674a8ac939ef4d6a4f315be`
- Total resources: `6`
- Digest and count exactly match the Catalog baseline.

Change Set history after-snapshot:

- SHA-256: `d9327aede2de7c142baa5a0b8361306cb40c490d203627347c7897bf1c2f4efa`
- Change Set count: `1`
- Digest and count exactly match the history baseline.

The snapshot equality proves that this read-only isolation pass did not mutate current-Family domain data or Agent Change Set history.

### Rate-limit observations

- Read limit: `120`.
- Remaining after the Catalog baseline: `119`.
- Remaining after the history baseline: `118`.
- A limiter-window reset was observed before the first detail response; remaining was `119` after `stores/1`.
- Remaining after the twentieth detail response: `101`.
- Remaining after both final safety snapshots: `99`.
- No HTTP 429 or `Retry-After` was returned, and the limiter was not bypassed, raced, or deliberately exhausted.

### Retained artifacts and remaining limitation

- This isolation pass created no preview, Change Set, Store, or other test artifact.
- The previously created run-scoped active Store and its immutable applied Change Set history remain retained and unchanged.
- The original sample Store, Store Section, Ingredient, Recipe Tag, and Recipe remain retained and unchanged.
- No destructive cleanup was attempted.
- Foreign Change Set isolation is not observable in production because the User attests that the foreign Family has no Agent Change Set and will not create one. No Change Set identifier was guessed or probed, and no foreign test artifact was created.

## Agent Credential self-restriction — 2026-08-12 UTC

### Summary

The newly deployed Agent Credential self-restriction endpoint passed its focused production test. The disposable credential shortened its own expiry monotonically, rejected closed-document violations without changing its restriction state, revoked itself as the final authenticated API request, and was rejected by the Catalog immediately afterward.

The application does not send a notification. The returned final credential values were:

- **status:** `revoked`
- **expires_at:** `2026-08-12T09:28:49Z`
- **revoked_at:** `2026-08-12T09:03:08Z`

### Run information

- **TEST_RUN_ID:** `cookbook-prod-credential-restriction-20260812T085623Z-38f18ccc`
- **UTC interval:** `2026-08-12T08:56:23Z`–`2026-08-12T09:04:09Z`
- **Credential:** newly issued disposable Agent Credential with `content:read`
- **Credential exposure:** none; the plaintext token was not printed, logged, persisted, captured, or included in this report
- **Scope:** only the self-restriction endpoint, its authentication boundary, and the single required post-revocation Catalog rejection

### Deployment and contract

**Result: Passed**

- Local repository commit `c5ffa7d451b94c378404a7bc8c2367c8ccd18eb7` is `:lock: [agent-api] preserve emergency revoke capacity`.
- Production behavior confirms that commit's distinguishing change: malformed revoke documents use the invalid-document rate bucket and do not consume the clean emergency revoke bucket.
- OpenAPI fetch: HTTP 200.
- `POST /api/v1/credential/restrictions` exists with operation ID `v1.credential.restrictions.store`.
- The operation inherits the document's required HTTP Bearer security, and runtime requires the live Sanctum Agent Credential.
- The request references `AgentCredentialRestrictionDocument`, a closed two-variant `oneOf`:
  - `shorten_expiry` requires exactly `action` and `expires_at`.
  - `revoke` requires exactly `action`.
  - Both variants declare `additionalProperties: false`.
- The operation description documents reducing expiry, using revoke as the final API request unless access should continue, reporting returned status and exact timestamps to the User, and the absence of an application notification.

### Unauthenticated protection

**Result: Passed**

Unauthenticated `POST /api/v1/credential/restrictions` with a revoke document returned:

- HTTP status: 401
- `error.code`: `authentication_required`
- `error.message`: `A valid Agent Credential is required.`
- `error.path`: null
- `error.operation_id`: null
- `error.details`: JSON object
- `error.retryable`: `false`

### Expiry shortening

**Result: Passed**

A whole-second UTC timestamp approximately 30 minutes in the future was submitted:

`2026-08-12T09:28:49Z`

The endpoint returned HTTP 200 with:

- `action`: `shorten_expiry`
- `status`: `active`
- `changed`: `true`
- `expires_at`: `2026-08-12T09:28:49Z`
- `revoked_at`: null

### Monotonic no-op

**Result: Passed**

A later timestamp, `2026-08-12T09:38:49Z`, was submitted. The endpoint returned HTTP 200 with:

- `action`: `shorten_expiry`
- `status`: `active`
- `changed`: `false`
- `expires_at`: `2026-08-12T09:28:49Z`
- `revoked_at`: null

The credential expiry was not extended.

### Closed-document validation

**Result: Passed**

Three small validation requests were made before final revocation:

| Document variation | HTTP status | `error.code` |
|---|---:|---|
| Valid shortening document plus `credential_id` | 422 | `validation_failed` |
| `{"action":"revoke","unknown":true}` | 422 | `validation_failed` |
| `{"action":[]}` | 422 | `validation_failed` |

Each response had no `data` member, returned `The request document is invalid.`, used a JSON object for `error.details`, and had `error.retryable: false`.

The credential remained active with expiry `2026-08-12T09:28:49Z` through these validation requests. No credential or Family target could be selected through an extra field.

### Commit `c5ffa7d4` production confirmation

**Result: Passed through distinguishing runtime behavior**

- The malformed revoke document returned rate-limit remaining `9` of `10`.
- The immediately following malformed-action document returned remaining `8` of `10`.
- The clean final revoke returned remaining `9` of `10` in its separate action bucket.

This confirms that malformed revoke documents are classified with invalid documents and cannot exhaust the emergency revoke bucket, which is the production behavior introduced by commit `c5ffa7d4`.

### Final revocation

**Result: Passed**

The final authenticated API request was exactly `{"action":"revoke"}`. Its response was received successfully, so no uncertain-outcome recovery was needed.

- HTTP status: 200
- `action`: `revoke`
- `status`: `revoked`
- `changed`: `true`
- `expires_at`: `2026-08-12T09:28:49Z`
- `revoked_at`: `2026-08-12T09:03:08Z`

The application does not send a notification. The agent therefore reports these returned status and timestamp values directly to the User.

### Post-revocation authentication

**Result: Passed**

The same secret was used exactly once after revocation for `GET /api/v1/catalog`:

- HTTP status: 401
- `error.code`: `authentication_required`
- `error.message`: `A valid Agent Credential is required.`
- `error.path`: null
- `error.operation_id`: null
- `error.details`: JSON object
- `error.retryable`: `false`

No further authenticated request was made with the revoked secret, and the token was cleared from the live test shell.

### Rate-limit observations

- Self-restriction rate limit: `10` per credential/action bucket.
- No HTTP 429 occurred.
- Limits were not deliberately exhausted.
- No request was retried.
