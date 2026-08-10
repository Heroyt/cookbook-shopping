# Architecture and System Boundaries

## Current request flow

The application is a Laravel 13 server-driven single-page application using Inertia 3 and Vue 3. Laravel owns routing, authentication, validation, persistence, and Inertia responses. Vue pages and shared components render the browser interface, while Vite builds the client assets. Wayfinder generates typed frontend bindings for Laravel routes.

The current repository contains an authenticated application shell, profile and security settings, appearance handling, a placeholder dashboard, Family Access inside `app/FamilyAccess`, and narrow Cookbook tracers inside `app/Cookbook`. Family Access persists Families, roleless Family Memberships, and the nullable Current Family preference; it implements operator provisioning, creation, selection, member lifecycle, exact-name-confirmed deletion, the no-orphan account-deletion guard, and a reusable Current Family query scope. Cookbook creates, lists, renames, and deletes Family-owned Stores; creates, lists, and deletes reusable Family-owned Store Sections with required colours; maintains each Store's ordered Section associations; and creates and lists Family-owned concrete Ingredients with canonical package quantities. See [Current application](current-application.md) for the implemented surface.

Current architectural evidence:

- [Laravel bootstrap](../../bootstrap/app.php) registers web routes, middleware, JSON exception behavior, and the `/up` health route.
- [Web routes](../../routes/web.php) expose the public welcome page and authenticated dashboard; the attached `verified` middleware is currently inert as explained in [Security and observability](security-observability.md#implemented-authentication-controls).
- [Settings routes](../../routes/settings.php) expose authenticated profile, security, password, and appearance operations.
- [Family Access routes](../../routes/family-access.php) expose authenticated Family creation.
- [Cookbook routes](../../routes/cookbook.php) expose Current-Family-scoped Store listing, creation, renaming, and deletion; Store Section creation and deletion; Store–Section attach, removal, and reorder commands; and Ingredient listing and creation.
- [Family creation action](../../app/FamilyAccess/Actions/CreateFamily.php) and its sibling module files contain the models, application actions, controller, and request validation.
- [Frontend entry point](../../resources/js/app.ts) resolves Inertia pages and initializes Vue.
- [Composer dependencies](../../composer.json) and [frontend dependencies](../../package.json) define the framework stack.

## Modular monolith direction

The physical modules now begin with `app/FamilyAccess` and `app/Cookbook`. Family Access owns Family persistence and application behavior without moving the existing `User` identity or authentication into the module. Its reusable `CurrentFamilyScope` resolves membership-validated context and applies that context through a Family-owned model's `family` relationship. Cookbook depends on this interface for Store, Store Section, and Ingredient reads and writes; Family Access does not depend on Cookbook.

<!-- prettier-ignore -->
> **Planned**
>
> Continue as one Laravel deployment and one logical database organized into four in-process domain modules plus one integration module. Family Access has begun; the remaining module boundaries and dependencies are the accepted direction in [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md) and the [Agent Integration decisions](agent-integrations.md#integration-choice-and-boundary).
>
> - **Family Access** owns Family, Family Membership, Current Family selection,
>   and authorization context. It may depend on the existing User identity and
>   authentication.
> - **Cookbook** owns Recipe, Ingredient, Recipe Tag, Store, Store Section,
>   nutrition, and package conversions. It may depend on Family Access for
>   ownership and authorization.
> - **Meal Planning** owns Calendar Entry, derived Calendar Day, Calendar
>   Selection, and temporary Simple Plan input. It may depend on Family Access
>   and read Cookbook recipes.
> - **Shopping Generation** owns Recipe Selection input, aggregation,
>   alternatives, Shopping List output, and Saved Shopping List snapshots. It
>   may depend on Cookbook value objects and Family Access for snapshot
>   ownership, but never on Meal Planning persistence.
> - **Agent Integration** owns Agent Credentials, Catalog projections, Agent
>   Change Sets, preview/apply coordination, API resources, and the OpenAPI
>   boundary. It depends on Family Access for authorization context and invokes
>   Cookbook and Meal Planning application actions; those modules do not depend
>   on Agent Integration.
>
> The dependency direction protects the central calculation boundary. Meal Planning translates selected Calendar Entries into Recipe Selections. A Simple Plan creates the same input directly. Shopping Generation does not query dates, calendar tables, controllers, or UI state; [ADR 0002](../adr/0002-keep-shopping-list-generation-persistence-independent.md) records this trade-off.
>
> <!-- diagram-alt: A User selects one Current Family. Calendar planning and Simple Plan each produce Recipe Selections, which enter the persistence-independent Shopping Generation module. The generator reads Cookbook recipe and ingredient definitions and emits a transient Shopping List that may be saved as a snapshot. -->
> ```mermaid
> flowchart LR
>     User["User in Current Family"] --> Calendar["Meal Planning: Calendar Selection"]
>     User --> Simple["Meal Planning: Simple Plan"]
>     Calendar --> Selections["Recipe Selections"]
>     Simple --> Selections
>     Cookbook["Cookbook: Recipes and Ingredients"] --> Generator["Shopping Generation"]
>     Selections --> Generator
>     Generator --> Result["Transient Shopping List"]
>     Result --> Snapshot["Optional Saved Shopping List"]
> ```
>
> The same Family boundary applies at every module edge. Identifiers from different Families must never be accepted together, and cross-Family generation or copying is outside the approved MVP.

## Application-layer responsibilities

The implemented Family-creation path keeps HTTP validation in a Form Request and delegates the transactional creation of the Family and its first membership to an application action. Account deletion delegates the no-orphan check and deletion transaction to a Family Access action. This establishes the controller-to-action seam without introducing a repository abstraction before one is needed.

Store rename follows the same explicit seam. `PATCH stores/{store}` enters `StoreUpdateRequest`, which normalizes and validates the proposed name without accepting a Family identifier. `StoreController::update` passes the authenticated User, Store identifier, and name to `RenameStore`. The action resolves the Store through `CurrentFamilyScope`, assigns the name so the Store model derives its normalized key, converts a database uniqueness collision to a field validation error, and saves. The controller then redirects to the Stores index with the internal `Store renamed.` translation key, rendered to the User as **Obchod byl přejmenován.** Cookbook depends on Family Access for this ownership boundary; Family Access does not depend on Cookbook.

Store deletion reuses that dependency direction without widening its input. `DELETE stores/{store}` enters `StoreDestroyRequest`; `StoreController::destroy` passes only the authenticated User and route Store identifier to `DeleteStore`. The action resolves the Store inside `CurrentFamilyScope`, returns not found for another Family, and hard-deletes the resolved Store. Its Store–Section associations cascade at the database boundary. The controller redirects to the Stores index with the internal `Store deleted.` translation key, rendered to the User as **Obchod byl smazán.** Ingredients exist without Store Placement persistence, so future placement-clearing behavior remains planned rather than simulated by this tracer.

Store Section creation follows the same boundary. `POST store-sections` enters `StoreSectionStoreRequest`, which normalizes the display name and validates a required six-digit hexadecimal colour without accepting a Family identifier. `StoreSectionController::store` passes the authenticated User, name, and colour to `CreateStoreSection`. The action derives ownership exclusively through `CurrentFamilyScope`, persists the ADR 0007 normalized key, and converts a uniqueness collision into a Czech `name` field error. The shared Stores index lists only the Current Family's Sections and renders both a colour swatch and its hexadecimal text; optional icons remain outside this tracer.

Store–Section maintenance uses three explicit association actions behind `StoreSectionAssociationController`. Attach and removal requests pass only the authenticated User and route identifiers; reorder additionally passes the complete Section identifier sequence and the Store order version the User saw. Every action resolves both Store and Store Section records through `CurrentFamilyScope`. Mutations lock the Store, keep positions contiguous, and increment `section_order_version`; reorder rejects a stale version and any sequence that is incomplete, duplicated, or foreign to that Store. Removal deletes only the association and retains the reusable Store Section entity. The application layer remains in Cookbook and depends on Family Access, preserving the modular direction.

Reusable Section deletion enters `StoreSectionDestroyRequest` through `DELETE store-sections/{storeSection}` and passes only the authenticated User and route identifier to `DeleteStoreSection`. Inside `CurrentFamilyScope`, the action locks the Section and its affected Stores, removes all associations, rewrites remaining positions contiguously, advances affected order versions, and deletes the Section in one transaction. The index supplies each Section's association count to a shadcn-vue AlertDialog, which also reports zero Ingredient placements because Store Placement persistence does not exist. Cross-Family identifiers are not found, client Family input is ignored, and the normalized name becomes reusable. Future Ingredient-placement clearing will extend this Cookbook action without introducing persistence into Shopping Generation.

Ingredient creation follows the same seam without broadening it. `POST ingredients` enters `IngredientStoreRequest`, which normalizes and validates the name plus canonical package quantities and accepts no Family identifier as authority. `IngredientController::store` passes the authenticated User, name, and an `IngredientPackageQuantities` value object to `CreateIngredient`. The action derives ownership through `CurrentFamilyScope`, persists the ADR 0007 normalized key, and converts a uniqueness race into a Czech `name` field error. The index query is scoped through the same interface and maps canonical decimals to presentation-ready `g`/`kg`, `ml`/`l`, and `ks` strings; Vue performs no quantity conversion. Ingredient editing and the wider Recipe or Shopping Generation boundary remain absent.

> **Planned**
>
> Controllers and Inertia pages should orchestrate use cases rather than perform domain calculations. Application actions load and authorize Family-owned records, translate them into typed domain input, invoke a domain service, and persist only explicit outputs such as Saved Shopping List snapshots.
>
> Keep framework-specific concerns at the boundary:
>
> - HTTP requests and authorization policies validate the Current Family context.
> - Family Access produces an immutable Authorized Family Context after live
>   membership validation. The Current Family and Agent Credential adapters
>   create that same context; domain actions do not inspect the adapter that
>   produced it.
> - Eloquent repositories or query services load aggregates and projections.
> - Domain value objects represent serving counts, quantities, measurement dimensions, package equivalents, and nutrition profiles using decimal-safe arithmetic.
> - The Shopping Generation service returns a result object and does not write to the database.
> - Inertia pages receive presentation-ready resources; Vue components do not reproduce package or nutrition calculations.
>
> The Agent Integration module exposes a small Catalog, preview, apply, and history interface while hiding operation parsing, dependency resolution, idempotency, warnings, staleness, transactionality, and persistence. Preview handlers construct side-effect-free proposed effects. Apply handlers invoke the same explicit Cookbook and Meal Planning actions as the web interface; do not expose a generic Eloquent mutation engine. [ADR 0035](../adr/0035-pass-an-authorized-family-context-to-domain-actions.md) and [ADR 0036](../adr/0036-use-a-deep-agent-integration-module.md) record these seams.

## Frontend boundary

The current frontend uses Vue single-file components, TypeScript, Inertia page resolution, Tailwind CSS, and shadcn-vue components. Family, Store, Store Section, and Ingredient management compose Inertia forms and visits with generated Wayfinder actions and installed shadcn-vue form, card, table, empty-state, dialog, alert-dialog, select, and button primitives. The Store Section form uses the native accessible colour input inside those primitives, while the list pairs the swatch with visible hexadecimal text and composes an association-counting destructive confirmation. Each Store card composes an association selector with accessible up, down, and remove controls; reorder submissions carry the complete identifier list and current version through generated Wayfinder actions. The Ingredient page composes labelled canonical quantity inputs, associated validation errors, a Family-scoped empty state, and presentation-ready package quantities supplied by Laravel. Generated Wayfinder modules live under ignored/generated paths in `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder`; regenerate them rather than hand-editing them.

> **Planned**
> Desktop layouts should optimize Recipe, Ingredient, Store, and Store Section maintenance. Mobile layouts should give equal support to the weekly planner and generated Shopping List. Both remain the same Inertia application rather than separate clients.

## Source boundaries

Do not duplicate canonical definitions in implementation comments or this guide. Update the repository source `CONTEXT.md` when domain language changes and add an ADR only for a hard-to-reverse, surprising trade-off with genuine alternatives. Update this guide after the underlying implementation or approved intent changes.
