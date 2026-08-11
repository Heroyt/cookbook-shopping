# Implementation Roadmap

This roadmap orders the approved MVP by dependency and risk. It is an implementation plan, not a statement of available behavior.

> **Planned**
> Each slice should deliver migrations, domain and application code, authorization, Inertia UI, factories, and focused PHPUnit/Vitest coverage together. Do not build all tables first and postpone the behavior that proves them.

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
> Verify the externally managed production profile before adding Family data:
>
> - Confirm the Komodo container boots and startup migrations succeed against
>   the same-host MariaDB service.
> - Configure Komodo or the proxy to poll `/up` and confirm it returns HTTP 200.
> - Confirm a MariaDB record and a private file beneath the persistent
>   `/var/www/storage/app` mount survive application-container recreation.
> - Exercise database-sensitive migrations and constraints against the selected
>   MariaDB server version before product migrations ship.
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

> **Planned**
>
> - Add optional Store logos and optional Store Section icons after their concrete media/catalogue prerequisites are approved.
> - Add optional Ingredient media after its concrete upload-validation policy is approved.
> - Extend the implemented quantity-kind removal guard to Recipe Ingredient dependencies when Slice 3 adds those records.
>
> Exclude photos and logos from the first Slice 2 tracer. Their implementation is blocked on the concrete upload-validation policy named in [Security and observability](security-observability.md#planned-photos-and-files).
>
> **Completion gate:** the non-media Slice 2 invariants are implemented and verified. The media portion remains deliberately blocked rather than guessed: Store logos, optional Store Section icons, and Ingredient media require the approved MIME, byte, dimension, decode/corruption, temporary-file cleanup, and icon-catalogue policies. Recipe Ingredient quantity-kind dependencies cannot exist before Slice 3; the implemented Nutrition Profile dependency proves the current removal guard and must be extended when Recipe Ingredients are introduced. The live Slice 0 Komodo/MariaDB recreation gate remains incomplete and is not replaced by local test evidence.

## Slice 3: Recipes and nutrition

> **Planned**
>
> - Add uniquely named Recipes with base Serving Count, one ordered ingredient list, optional ordered Recipe Steps and approved metadata, saved as complete versioned aggregates that reject stale edits.
> - Permit repeated Ingredient lines and fractional count amounts.
> - Scale quantities by `requested servings / base servings` using exact rational value objects created from validated `DECIMAL(20,6)` inputs.
> - Calculate complete or explicitly incomplete per-serving nutrition, with complete Recipe Nutrition Overrides taking precedence.
> - Add Recipe Tags with assignment-clearing hard deletion, search result layers, and reversible Recipe archival/restoration without individual hard deletion.
>
> **Completion gate:** every Planned Slice 3 invariant is proven. Automated tests cover mixed units, repeated lines, fractional servings, missing nutrition, overrides, Family isolation, active/archived filtering and restore-before-edit behavior, stale complete-aggregate rejection without partial writes, Tag deletion with assignment cleanup and name reuse, and layered Recipe search that deduplicates results while retaining every match reason. Media remains gated by the approved upload policy.

## Slice 4: pure Shopping Generation

> **Planned**
>
> - Implement the persistence-independent generator from Recipe Selections to Shopping List Lines.
> - Aggregate by final Ingredient before ceiling package counts.
> - Express required, purchased, and Surplus quantities in every configured unit.
> - Retain contribution breakdowns by source Recipe.
> - Offer one single-hop active direct Alternative Ingredient choice only when every canonical Recipe quantity kind exists on its package, with no cross-kind conversion or manual-quantity fallback, then globally re-aggregate while preserving per-choice provenance.
> - Compose a pure grouping collaborator behind the public generator facade to deterministically order Stores by normalized name, then output Store Section traversal order and Ingredient name, with unassigned groups last.
> - Return either a complete grouped Shopping List or typed Calculation Problems; never return partial purchase output.
>
> **Completion gate:** domain tests exercise every Planned Slice 4 bullet plus the examples and invariants in [Shopping-list generation](shopping-generation.md) without HTTP, database, calendar, or Inertia dependencies. They prove all-or-nothing problem collection, exact arithmetic and global pre-round aggregation, single-hop canonical-kind Alternative behavior, deterministic normalized-name/stable-identity grouping across input orders, and complete provenance.

## Slice 5: Simple Plan

> **Planned**
>
> - Build an unordered, temporary set with one Recipe Selection per Recipe and accumulate repeated additions into that row with an explicit resulting-total notice.
> - Generate the list through the same service used by Calendar planning.
> - Present responsive desktop and mobile output suitable for copying into another checklist tool.
>
> **Completion gate:** a browser-level test proves that Recipe selection and fractional servings produce the expected package counts without persisting the Simple Plan, and that adding the same Recipe again accumulates its submitted Serving Count into one row with an explicit resulting-total notice.

## Slice 6: weekly Calendar

> **Planned**
>
> - Persist Calendar Entries only; derive Calendar Days at read time.
> - Support the five fixed Czech Meal Labels plus unlabeled entries.
> - Prevent duplicate `(Family, date, Meal Label, Recipe)` rows by persisting a non-null internal key for the unlabeled case, while atomically accumulating duplicate creates and collision-producing edits with an explicit UI notice for every accepted request.
> - Provide a responsive weekly planner and arbitrary multi-date Calendar Selection with range-selection convenience.
> - Show calculated Recipe and daily nutrition, including incomplete-state warnings.
>
> **Completion gate:** every Planned Slice 6 invariant is proven. Selecting non-contiguous dates produces the same generator input and output as an equivalent Simple Plan; tests also cover duplicate create, collision-producing edit using the submitted edited Serving Count, concurrent collision, repeated transport request, explicit resulting-total notice, the internal unlabeled key, and restore-required restrictions on archived-Recipe entries.

## Slice 7: generation history

> **Planned**
>
> - Let a member explicitly save a new read-only Shopping List snapshot for every accepted request, identified to the User by generation timestamp.
> - Store relational ownership/provenance headers and a versioned immutable JSON payload containing display data, lossless calculated output, applied alternatives, and source provenance.
> - Permit any Family member to delete history entries.
>
> **Completion gate:** every Planned Slice 7 invariant is proven. Later edits or archival of Recipes and Ingredients, and edits or deletion of Stores and Sections, cannot change the rendered snapshot. Tests also prove that every accepted save—including identical content and a repeated request—creates a distinct row; the schema version is readable; exact values and provenance round-trip losslessly; and frozen localized display values render without consulting live records.

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
