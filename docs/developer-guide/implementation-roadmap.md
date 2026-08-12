# Implementation Roadmap

This roadmap records the dependency order and current completion state. Slices 1 through 7 are implemented; Slice 8 remains planned, and Slice 0 still lacks its external live-recreation evidence.

Each delivered slice includes migrations where needed, domain and application code, authorization, Inertia UI, factories, and focused pure PHPUnit/Vitest coverage rather than table-only scaffolding.

## Slice 0: delivery baseline

The verified implementation increments and resolved delivery decisions are:

- `composer setup` succeeds from an isolated clean checkout with SQLite,
  frozen pnpm dependencies, migrations, and a production frontend build.
- Development and test configuration use SQLite; the production environment
  contract uses MariaDB.
- Development and production entry points fail immediately when migrations
  fail.
- Production images accept runtime environment injection instead of copying an
  environment file or caching Laravel configuration during the image build.
- The production image builds with the declared pnpm version and includes the
  MariaDB PDO driver.
- The repository contract records the selected personal deployment profile:
  one Komodo-managed application container, host-local MariaDB, synchronous
  jobs, and a required persistent private filesystem mount. `/up` is the
  recommended minimal health signal; automated recovery and centralized
  observability are explicitly out of scope. Live server acceptance remains
  unverified.

> **Planned**
>
> Verify the externally managed production profile before declaring Slice 0 or
> the live production profile accepted:
>
> - Confirm the Komodo container boots and startup migrations succeed against
>   the same-host MariaDB service.
> - Configure Komodo or the proxy to poll `/up` and confirm it returns HTTP 200.
> - Confirm a MariaDB record and a private file beneath the persistent
>   `/var/www/storage/app` mount survive application-container recreation.
> - Exercise the complete database-sensitive migration and constraint suite
>   against the selected live MariaDB version before accepting Slice 0.
>
> Follow the non-secret
> [external acceptance checklist](infrastructure-deployment.md#external-acceptance-checklist)
> to collect this evidence without using real Family data.
>
> **Completion gate (not yet met):** a production-like deployment can boot,
> migrate, serve `/up`, and preserve both MariaDB data and a private test file
> across container recreation. Queue-worker, scheduler-workload, backup/restore,
> and telemetry checks are excluded by the accepted personal-project profile in
> [ADR 0006](../adr/0006-use-a-single-host-personal-production-profile.md).

On 2026-08-10 the User explicitly directed implementation to proceed into Slice 1 while they retain responsibility for deployment. This authorizes the development sequence but is not external acceptance evidence and does not complete Slice 0.

## Slice 1: Family access

The verified Family Access increments now provide:

- `families` and unique roleless `family_memberships` persistence with factories and Eloquent relationships;
- authenticated Family creation through a validated Inertia form, including the first membership in one transaction;
- operator-only `user:create` provisioning while public registration remains disabled;
- persisted, membership-validated Current Family selection with automatic fallback;
- Current-Family-scoped member listing, add-by-email, leave, removal, and exact-name-confirmed Family deletion;
- a Family Access module boundary under `app/FamilyAccess` without changing the pure Shopping Generation boundary;
- an account-deletion guard that rejects deletion if the User is the final member of any Family and otherwise preserves every Family; and
- focused PHPUnit coverage for provisioning, authentication, validation, atomic creation, Current Family fallback, member lifecycle, cross-Family membership isolation, destructive confirmation, and the selected account-deletion policy; and
- a responsive Inertia/Vue management screen composed from shadcn-vue primitives and generated Wayfinder actions.

The reusable `CurrentFamilyScope` and the first Family-owned Store tracer complete the Slice 1 authorization gate. Tests with two Users and two Families prove equal member rights, Current-Family-only Store reads, and that a client-supplied Family identifier cannot redirect a Store write.

**Completion gate met:** Family Access supplies validated Current Family context to another module without reversing the modular dependency or changing Shopping Generation.

## Slice 2: Stores and packaged Ingredients

The verified Slice 2 tracer now provides:

- `stores`, `store_sections`, ordered association, and `ingredients` persistence with explicit Family ownership and cascade deletion; Store associations use contiguous positions and a per-Store optimistic order version;
- PHP-generated display/key normalization plus database-backed `(family_id, normalized_name)` uniqueness for Store, Store Section, and Ingredient names across SQLite and MariaDB semantics;
- authenticated Current-Family-scoped Store creation, listing, renaming, and deletion; Store Section creation, listing, and deletion; and Store–Section association, removal, and reorder through the reusable Family Access scope;
- Store rename resolution that accepts only the authenticated User, Store identifier, and proposed name, then resolves ownership inside `CurrentFamilyScope` without accepting a Family identifier;
- Store deletion resolution that accepts only the authenticated User and Store identifier, returns not found for another Family, and does not accept a Family identifier;
- Store Section deletion that removes all Store associations, closes affected positions, advances affected order versions, releases the normalized name, and reports and clears affected Ingredient Sections while retaining their Stores;
- authenticated Current-Family-scoped creation, editing, listing, placement, archival, restoration, Alternative management, and Nutrition Profile management for concrete Ingredients;
- approved `DECIMAL(20,6)` package columns and database checks for positive, mutually exclusive metric quantities, with request-scale rejection and derived two-fractional-digit `g`/`kg`, `ml`/`l`, and `ks` presentation;
- a responsive Inertia/Vue Stores page composed from shadcn-vue primitives and generated Wayfinder actions, including an accessible Store Section colour picker, non-colour-only list output, consequence-stating Section deletion, and accessible per-Store association/order controls; and
- a responsive Inertia/Vue Ingredients page composed from the same conventions with explicit metric units, placement, nutrition, alternatives, archive filters/actions, Czech validation, an empty state, and presentation-ready quantities; and
- focused PHPUnit/Vitest coverage for equal member rights, cross-Family read/write isolation, validation, normalization, race-safe duplicate handling, success feedback, package and database invariants, deletion cleanup, exact complete ordering, stale-version rejection, generated action wiring, and rendered Store Section and Ingredient output.
- Ingredient editing with optional description, exact normalization of `mg`, `g`, `kg`, `ml`, `cl`, and `l`, Store-only or association-backed Store Placement, reversible archive/filter/restore, direct symmetric/non-transitive Alternative edges, a pure canonical-kind eligibility predicate, and complete optional Nutrition Profiles;
- database-backed placement, Alternative-pair, and Nutrition Profile invariants plus lifecycle cleanup for Store, association, and reusable Section removal; and
- Czech shadcn-vue/Inertia controls for placement, nutrition, alternatives, filtering, archive confirmation, restoration, and restore-before-edit behavior.

- private Store logo, Store Section image, Ingredient photo, and Recipe cover uploads accept approved JPEG/PNG input up to 5 MB, decode and normalize to configured deterministic WebP variants, authorize every read, serialize replacement writers, restore prior variants on failure, retain Recipe/Ingredient archive media, and remove affected files with rollback-aware hard Store, Store Section, or Family deletion;
- Store Sections may select one allowlisted SVG icon key independently of their colour or uploaded image; and
- the Ingredient quantity-kind removal guard now checks indexed Recipe Ingredient dependencies in addition to Nutrition Profiles.

**Completion gate met:** every approved Slice 2 invariant, including media and icon policy, is implemented and verified on SQLite and relevant disposable MariaDB suites. The live Slice 0 Komodo/MariaDB recreation gate remains incomplete and is not replaced by local evidence.

## Slice 3: Recipes and nutrition

The implemented Slice 3 provides uniquely named complete Recipe aggregates with positive base Serving Counts, repeated ordered Ingredients, optional ordered Steps, source URL, durations, Notes, Tags, and complete nutrition overrides. Optimistic versions reject stale full saves without partial writes. Exact Recipe nutrition calculates across package, metric, and piece bases, preserves known totals in explicit incomplete results, and lets a complete override take precedence. Tags hard-delete with assignment cleanup and name reuse. Layered Current-Family search deduplicates Recipes while preserving all name, Tag, and Ingredient reasons. Recipe archive/filter/restore and restore-before-edit are complete.

**Completion gate met:** focused tests cover exact and fractional quantities, repeated lines, missing nutrition, overrides, two-Family isolation, rollback, stale versions, Tag cleanup, archive/restore, and layered search.

## Slice 4: pure Shopping Generation

The implemented pure generator scales exact Recipe Selections, aggregates by final Ingredient before ceiling package counts, calculates required/purchased/Surplus in every configured kind, retains deterministic Recipe contributions, applies one eligible direct active Alternative per original Ingredient, globally re-aggregates with reversible provenance, and groups by normalized Store, Section traversal, and normalized Ingredient. It returns either a complete Shopping List or every typed Calculation Problem and has no HTTP, database, Calendar, or session dependency.

**Completion gate met:** pure PHPUnit proves exact arithmetic, all-or-nothing problem collection, deterministic grouping across input orders, complete provenance, single-hop Alternative rules, and internal contract violations.

## Slice 5: Simple Plan

The implemented Simple Plan is a Current-Family-namespaced transient session set with one Recipe Selection per active Recipe. Duplicate additions accumulate exact Serving Count with Czech resulting-total feedback. Generation is refresh-safe, preserves the plan through correction, presents every problem and exact correction link, applies/reverts valid direct Alternatives, and uses the pure generator without a Simple Plan table.

**Completion gate met:** PHPUnit, Vitest, and recorded browser evidence prove fractional/additive selection, two-Family isolation, transience, responsive output, correction/retry, alternatives, focus, and Czech feedback.

## Slice 6: weekly Calendar

The implemented Calendar persists only entries and derives ordered weekly days plus exact daily nutrition. It supports five fixed Czech labels and internal `unlabeled`, atomically accumulates duplicate creates and collision edits, restricts archived-Recipe entry edits, provides arbitrary non-contiguous date selection, preserves selection while invalidating stale generated presentations, and produces the same generator request/output as equivalent Simple Plan input.

**Completion gate met:** SQLite and disposable MariaDB tests cover unique-key collision, exact accumulation, rollback, repeated accepted requests, two-Family scope, archived restrictions, arbitrary dates, alternatives, source-specific correction flow, and nutrition. Vitest/browser evidence covers responsive planner and composed dialog/focus behavior.

## Slice 7: generation history

The implemented history explicitly saves a separate immutable snapshot for every accepted request. Relational headers support Current-Family bounded cursor history while explicit `SavedShoppingListV1` serialization freezes lossless output, localized display, alternatives, and Simple Plan or Calendar provenance. Detail does not consult live records; unsupported/corrupt schemas render an intentional Czech unavailable state. Any member may delete history with visible focus recovery.

**Completion gate met:** tests prove repeated/identical saves remain distinct, microsecond timestamp plus identifier ordering, schema-aware round-trip, immutability, live-record independence, two-Family isolation, summary-only index queries, pagination, cascade, and equal-member deletion. Vitest/browser evidence covers save, history navigation/detail, cancellation and confirmation focus, and console-clean behavior.

## Slice 8: Agent Integration

> **Planned**
>
> Deliver Agent Integration only after the Cookbook and Meal Planning entities in its v1 contract have complete application actions and tests.
>
> - Refactor web actions to accept a membership-validated Authorized Family Context without changing Current Family behavior.
> - Add Sanctum-backed Agent Credentials scoped to one issuer and Family, with explicit abilities, expiry, rotation, revocation, issuer-membership invalidation, and the Current Family Agent Access screen.
> - Add the read-only Family Catalog for Stores, Store Sections, Ingredients, Recipe Tags, Recipes, and Calendar Entries.
> - Add the deep Agent Change Set module with explicit resource handlers, side-effect-free preview, digest-bound atomic apply through existing domain actions, idempotency, warnings, structured errors, staleness checks, limits, and expiry cleanup.
> - Add immutable applied history and the Current Family Agent Change History screen.
> - Add the Scramble-generated OpenAPI 3.1 contract and public documentation routes, with production caching and interactive requests disabled.
>
> **Completion gate:** a separately configured trusted agent can discover one Family's complete catalog, preview and atomically apply a mixed-resource Change Set, retry it idempotently, and inspect its immutable result without reading another Family or managing Family Access. Tests prove credential lifecycle, ability boundaries, two-Family isolation, stale and warning rejection, rollback, generated documentation, and responsive credential/history management. See [Agent integrations](agent-integrations.md).

## Deferred capabilities

> **Planned**
>
> Do not fold pantry inventory, pricing, allergens, recurring calendar rules, cross-Family copying, checklist state, external checklist integration, server-side source extraction, media import, or an MCP server into these slices. Each requires a separate domain review before implementation.
