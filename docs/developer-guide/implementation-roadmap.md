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

- `stores` persistence with explicit Family ownership and cascade deletion;
- PHP-generated display/key normalization plus database-backed `(family_id, normalized_name)` uniqueness across SQLite and MariaDB semantics;
- authenticated Current-Family-scoped Store creation, listing, renaming, and deletion through the reusable Family Access scope;
- Store rename resolution that accepts only the authenticated User, Store identifier, and proposed name, then resolves ownership inside `CurrentFamilyScope` without accepting a Family identifier;
- Store deletion resolution that accepts only the authenticated User and Store identifier, returns not found for another Family, and does not accept a Family identifier;
- a responsive Inertia/Vue Stores page composed from shadcn-vue primitives and generated Wayfinder actions; and
- focused PHPUnit/Vitest coverage for equal member create/rename/delete rights, cross-Family read/write isolation, validation, normalization, race-safe duplicate handling, success feedback, and frontend wiring.

> **Planned**
>
> - Add optional Store logos and reusable Store Section entities within a Family.
> - Maintain each Store's ordered section associations.
> - Add Ingredients as concrete purchasable packages with at least one positive unit quantity, optional Store Placement, media, description, nutrition, and direct symmetric/non-transitive alternatives.
> - Support metric weight and volume units plus Ingredient-specific count units.
> - Archive Ingredients and guard removal of units referenced by Recipe Ingredients.
>
> **Completion gate (not yet met):** a Family can describe a package such as `150 g = 6 pieces`, place it in a Store Section, reorder Store sections, archive it, and prove that another Family cannot observe it. The Store tracer proves the isolation portion only.

## Slice 3: Recipes and nutrition

> **Planned**
>
> - Add uniquely named Recipes with base Serving Count, one ordered ingredient list, optional ordered Recipe Steps and approved metadata.
> - Permit repeated Ingredient lines and fractional count amounts.
> - Scale quantities by `requested servings / base servings` using decimal-safe value objects.
> - Calculate complete or explicitly incomplete per-serving nutrition, with complete Recipe Nutrition Overrides taking precedence.
> - Add Recipe Tags, search result layers, and Recipe archiving.
>
> **Completion gate:** automated tests cover mixed units, repeated lines, fractional servings, missing nutrition, overrides, archive behavior, and Family isolation.

## Slice 4: pure Shopping Generation

> **Planned**
>
> - Implement the persistence-independent generator from Recipe Selections to Shopping List Lines.
> - Aggregate by final Ingredient before ceiling package counts.
> - Express required, purchased, and Surplus quantities in every configured unit.
> - Retain contribution breakdowns by source Recipe.
> - Apply manual Alternative Ingredient choices, regroup by the final Store Placement, and globally re-aggregate.
> - Order output by Store, Store Section traversal order, and Ingredient name, with unassigned groups last.
>
> **Completion gate:** domain tests exercise the examples and invariants in [Shopping-list generation](shopping-generation.md) without HTTP, database, calendar, or Inertia dependencies.

## Slice 5: Simple Plan

> **Planned**
>
> - Build an unordered, temporary set with one Recipe Selection per Recipe.
> - Generate the list through the same service used by Calendar planning.
> - Present responsive desktop and mobile output suitable for copying into another checklist tool.
>
> **Completion gate:** a browser-level test proves that Recipe selection and fractional servings produce the expected package counts without persisting the Simple Plan.

## Slice 6: weekly Calendar

> **Planned**
>
> - Persist Calendar Entries only; derive Calendar Days at read time.
> - Support the five fixed Czech Meal Labels plus unlabeled entries.
> - Prevent duplicate `(Family, date, Meal Label, Recipe)` combinations, including the unlabeled case.
> - Provide a responsive weekly planner and arbitrary multi-date Calendar Selection with range-selection convenience.
> - Show calculated Recipe and daily nutrition, including incomplete-state warnings.
>
> **Completion gate:** selecting non-contiguous dates produces the same generator input and output as an equivalent Simple Plan.

## Slice 7: generation history

> **Planned**
>
> - Let a member explicitly save a read-only Shopping List snapshot identified by generation timestamp.
> - Copy display data, calculated output, applied alternatives, and immutable source provenance into the snapshot.
> - Permit any Family member to delete history entries.
>
> **Completion gate:** later edits or archival of Recipes and Ingredients, and edits or deletion of Stores and Sections, cannot change the rendered snapshot.

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
