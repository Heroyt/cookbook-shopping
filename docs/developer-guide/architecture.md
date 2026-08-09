# Architecture and System Boundaries

## Current request flow

The application is a Laravel 13 server-driven single-page application using Inertia 3 and Vue 3. Laravel owns routing, authentication, validation, persistence, and Inertia responses. Vue pages and shared components render the browser interface, while Vite builds the client assets. Wayfinder generates typed frontend bindings for Laravel routes.

The current repository contains an authenticated application shell, profile and security settings, appearance handling, and a placeholder dashboard. The only application model is `User`; the cookbook domain has no migrations, models, routes, controllers, or pages yet. See [Current application](current-application.md) for the implemented surface.

Current architectural evidence:

- [Laravel bootstrap](../../bootstrap/app.php) registers web routes, middleware, JSON exception behavior, and the `/up` health route.
- [Web routes](../../routes/web.php) expose the public welcome page and authenticated dashboard; the attached `verified` middleware is currently inert as explained in [Security and observability](security-observability.md#implemented-authentication-controls).
- [Settings routes](../../routes/settings.php) expose authenticated profile, security, password, and appearance operations.
- [Frontend entry point](../../resources/js/app.ts) resolves Inertia pages and initializes Vue.
- [Composer dependencies](../../composer.json) and [frontend dependencies](../../package.json) define the framework stack.

## Planned modular monolith

<!-- prettier-ignore -->
> **Planned**
>
> The application remains one Laravel deployment and one logical database, organized into four in-process domain modules. This is the accepted direction in [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md), not the current directory structure.
>
> | Module              | Owns                                                                                                      | May depend on                                                                                    |
> | ------------------- | --------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
> | Family Access       | Family, Family Membership, Current Family selection and authorization context                             | Existing User identity and authentication                                                        |
> | Cookbook            | Recipe, Ingredient, Recipe Tag, Store, Store Section, nutrition and package conversions                   | Family Access for ownership and authorization                                                    |
> | Meal Planning       | Calendar Entry, derived Calendar Day, Calendar Selection, and temporary Simple Plan input                 | Family Access and read access to Cookbook recipes                                                |
> | Shopping Generation | Recipe Selection input, aggregation, alternatives, Shopping List output and Saved Shopping List snapshots | Cookbook value objects and Family Access for snapshot ownership; never Meal Planning persistence |
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

> **Planned**
>
> Controllers and Inertia pages should orchestrate use cases rather than perform domain calculations. Application actions load and authorize Family-owned records, translate them into typed domain input, invoke a domain service, and persist only explicit outputs such as Saved Shopping List snapshots.
>
> Keep framework-specific concerns at the boundary:
>
> - HTTP requests and authorization policies validate the Current Family context.
> - Eloquent repositories or query services load aggregates and projections.
> - Domain value objects represent serving counts, quantities, measurement dimensions, package equivalents, and nutrition profiles using decimal-safe arithmetic.
> - The Shopping Generation service returns a result object and does not write to the database.
> - Inertia pages receive presentation-ready resources; Vue components do not reproduce package or nutrition calculations.

## Frontend boundary

The current frontend uses Vue single-file components, TypeScript, Inertia page resolution, Tailwind CSS, and shadcn-vue components. Generated Wayfinder modules live under ignored/generated paths in `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder`; regenerate them rather than hand-editing them.

> **Planned**
> Desktop layouts should optimize Recipe, Ingredient, Store, and Store Section maintenance. Mobile layouts should give equal support to the weekly planner and generated Shopping List. Both remain the same Inertia application rather than separate clients.

## Source boundaries

Do not duplicate canonical definitions in implementation comments or this guide. Update [CONTEXT.md](../../CONTEXT.md) when domain language changes and add an ADR only for a hard-to-reverse, surprising trade-off with genuine alternatives. Update this guide after the underlying implementation or approved intent changes.
