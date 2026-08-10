# Domain model

The repository currently implements the authenticated `User` and account-security shell only. The [`User` model](../../app/Models/User.php) has no Family or cookbook relationships, and there are no models, migrations, routes, controllers, or pages for the cookbook and shopping-planning domain. See [Current application](current-application.md) for the implemented surface.

[`CONTEXT.md`](../../CONTEXT.md) is the canonical implementation-free glossary. These developer chapters explain relationships, invariants, and implementation consequences without replacing that vocabulary.

## Decision map

The approved design is governed by:

- [ADR 0001: Use concrete purchasable ingredients](../adr/0001-use-concrete-purchasable-ingredients.md)
- [ADR 0002: Keep Shopping List generation persistence-independent](../adr/0002-keep-shopping-list-generation-persistence-independent.md)
- [ADR 0003: Scope domain data to Families](../adr/0003-scope-domain-data-to-families.md)
- [ADR 0004: Build a Laravel modular monolith](../adr/0004-build-a-laravel-modular-monolith.md)
- [ADR 0005: Use MariaDB in production and SQLite locally](../adr/0005-use-mariadb-in-production-and-sqlite-locally.md)
- [ADR 0006: Use a single-host personal production profile](../adr/0006-use-a-single-host-personal-production-profile.md)

## Planned boundaries

> **Planned**
>
> The application remains one Laravel deployment and logical database with four explicit in-process modules. **Family Access** owns Family Membership and Current Family context. **Cookbook** owns Recipes, Ingredients, Stores, Store Sections, Recipe Tags, preparation content, package definitions, and nutrition. **Meal Planning** owns persistent Calendar Entries and adapts Calendar Selections or temporary Simple Plans into Recipe Selections. **Shopping Generation** consumes those selections through a persistence-independent service and returns calculated Shopping List Lines.
>
> Family is the ownership and authorization boundary at every module edge. Cookbook, planning, and saved-history records belong to exactly one Family. No operation combines or copies data across Families in the MVP.

See [Architecture and system boundaries](architecture.md) for framework placement and module dependencies, and [Planned data structure](data-structure.md) for the conceptual relational shape.

## Capability chapters

- [Family access](family-access.md) — membership, Current Family selection, isolation, and destructive operations.
- [Recipes and Ingredients](recipes-ingredients.md) — concrete packages, units, conversions, alternatives, Recipe composition, search, and archival.
- [Nutrition](nutrition.md) — Ingredient bases, calculated per-serving values, overrides, incomplete profiles, and daily totals.
- [Stores and shopping order](stores-shopping-order.md) — reusable Sections, Ingredient placement, deletion behavior, and final grouping.
- [Calendar planning](calendar-planning.md) — Calendar Entries, fixed Meal Labels, Calendar Selection, weekly planning, and Simple Plans.
- [Shopping List generation](shopping-generation.md) — generator input and output, calculation order, alternatives, grouping, and immutable history.

The dependency-ordered delivery sequence is in the [Implementation roadmap](implementation-roadmap.md).

## MVP boundary

> **Planned**
>
> The initial domain excludes pantry subtraction, price and cost tracking, structured allergens or dietary-safety claims, recurring Calendar rules, cross-Family copying, interactive checklist state, and external checklist integrations. Each requires a separate domain review before implementation.
