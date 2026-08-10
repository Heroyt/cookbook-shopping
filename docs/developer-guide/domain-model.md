# Domain model

The repository implements the authenticated `User` and account-security shell, Family Access, and narrow Cookbook Store, Store Section, and packaged Ingredient tracers. The [`User` model](../../app/Models/User.php) exposes Current Family, Family Membership, and Family relationships, while `app/FamilyAccess` implements Family lifecycle and reusable Current Family scoping. `app/Cookbook` currently owns Store creation, listing, renaming, and deletion; reusable Store Section creation/listing/deletion and per-Store association/removal/ordering; and Ingredient creation/listing with core canonical package quantities. The rest of Cookbook, Meal Planning, and Shopping Generation remains unimplemented. See [Current application](current-application.md) for the implemented surface.

The final Domain Glossary chapter is the canonical implementation-free vocabulary. These developer chapters explain relationships, invariants, and implementation consequences without replacing it.

## Decision map

The approved design is governed by:

- [ADR 0001: Use concrete purchasable ingredients](../adr/0001-use-concrete-purchasable-ingredients.md)
- [ADR 0002: Keep Shopping List generation persistence-independent](../adr/0002-keep-shopping-list-generation-persistence-independent.md)
- [ADR 0003: Scope domain data to Families](../adr/0003-scope-domain-data-to-families.md)
- [ADR 0004: Build a Laravel modular monolith](../adr/0004-build-a-laravel-modular-monolith.md)
- [ADR 0005: Use MariaDB in production and SQLite locally](../adr/0005-use-mariadb-in-production-and-sqlite-locally.md)
- [ADR 0006: Use a single-host personal production profile](../adr/0006-use-a-single-host-personal-production-profile.md)
- [ADR 0007: Use application-normalized keys for scoped name uniqueness](../adr/0007-use-application-normalized-keys-for-scoped-name-uniqueness.md)
- [ADR 0012: Delete Store Sections with placement-preserving cleanup](../adr/0012-delete-store-sections-with-placement-preserving-cleanup.md)
- [ADR 0013: Use exact rational quantity calculation](../adr/0013-use-exact-rational-quantity-calculation.md)
- [ADR 0014: Use one universal piece unit](../adr/0014-use-one-universal-piece-unit.md)
- [ADR 0015: Enforce Store Placement through the Store–Section association](../adr/0015-enforce-store-placement-through-the-store-section-association.md)
- [ADR 0016: Require canonical quantity kinds for Alternative replacement](../adr/0016-require-canonical-quantity-kinds-for-alternative-replacement.md)
- [ADR 0017: Return an all-or-nothing typed generation result](../adr/0017-return-an-all-or-nothing-typed-generation-result.md)
- [ADR 0018: Compose grouping inside Shopping Generation](../adr/0018-compose-grouping-inside-shopping-generation.md)
- [ADR 0019: Persist an unlabeled Calendar key](../adr/0019-persist-an-unlabeled-calendar-key.md)
- [ADR 0020: Store versioned Shopping List snapshot payloads](../adr/0020-store-versioned-shopping-list-snapshot-payloads.md)
- [ADR 0021: Keep Alternative replacement single-hop](../adr/0021-keep-alternative-replacement-single-hop.md)
- [ADR 0022: Archive and restore Recipes and Ingredients](../adr/0022-archive-and-restore-recipes-and-ingredients.md)
- [ADR 0023: Rewrite contiguous Store Section positions](../adr/0023-rewrite-contiguous-store-section-positions.md)
- [ADR 0024: Accumulate duplicate Calendar Serving Counts](../adr/0024-accumulate-duplicate-calendar-serving-counts.md)
- [ADR 0025: Create a Saved Shopping List for every save](../adr/0025-create-a-snapshot-for-every-save.md)
- [ADR 0026: Store one canonical metric package quantity](../adr/0026-store-one-canonical-metric-package-quantity.md)
- [ADR 0027: Restrict edits for archived-Recipe Calendar Entries](../adr/0027-restrict-edits-for-archived-recipe-calendar-entries.md)
- [ADR 0028: Reject stale Store Section reorders](../adr/0028-reject-stale-store-section-reorders.md)
- [ADR 0029: Apply every Calendar accumulation request](../adr/0029-apply-every-calendar-accumulation-request.md)
- [ADR 0030: Derive metric display units](../adr/0030-derive-metric-display-units.md)
- [ADR 0031: Accumulate duplicate Simple Plan selections](../adr/0031-accumulate-duplicate-simple-plan-selections.md)
- [ADR 0032: Save Recipes as versioned aggregates](../adr/0032-save-recipes-as-versioned-aggregates.md)
- [ADR 0033: Delete Recipe Tags with assignment cleanup](../adr/0033-delete-recipe-tags-with-assignment-cleanup.md)

## Planned boundaries

> **Planned**
>
> The application remains one Laravel deployment and logical database with four explicit in-process modules. **Family Access** owns Family Membership and Current Family context. **Cookbook** owns Recipes, Ingredients, Stores, Store Sections, Recipe Tags, preparation content, package definitions, and nutrition. **Meal Planning** owns persistent Calendar Entries and adapts Calendar Selections or temporary Simple Plans into Recipe Selections. **Shopping Generation** consumes those selections through a persistence-independent service and returns calculated Shopping List Lines.
>
> Family is the ownership and authorization boundary at every module edge. Cookbook, planning, and saved-history records belong to exactly one Family. No operation combines or copies data across Families in the MVP.

See [Architecture and system boundaries](architecture.md) for framework placement and module dependencies, and [Data structure](data-structure.md) for implemented persistence and the remaining conceptual relational shape.

## Capability chapters

- [Family access](family-access.md) — implemented provisioning, Family and membership lifecycle, Current Family selection, account-deletion protection, and the reusable scope proven by Stores, Store Sections, and Ingredients; later aggregates must reuse it.
- [Recipes and Ingredients](recipes-ingredients.md) — implemented concrete package creation/listing and canonical quantity display plus planned conversions, alternatives, Recipe composition, search, and archival.
- [Nutrition](nutrition.md) — Ingredient bases, calculated per-serving values, overrides, incomplete profiles, and daily totals.
- [Stores and shopping order](stores-shopping-order.md) — implemented Store lifecycle, reusable Store Section creation/listing/deletion, and per-Store association/order plus planned placement effects and final grouping.
- [Calendar planning](calendar-planning.md) — Calendar Entries, fixed Meal Labels, Calendar Selection, weekly planning, and Simple Plans.
- [Shopping List generation](shopping-generation.md) — generator input and output, calculation order, alternatives, grouping, and immutable history.
- [Agent integrations](agent-integrations.md) — the planned Agent Credential, Family Catalog, atomic Agent Change Set, and OpenAPI boundary.

The dependency-ordered delivery sequence is in the [Implementation roadmap](implementation-roadmap.md).

## MVP boundary

> **Planned**
>
> The initial domain excludes pantry subtraction, price and cost tracking, structured allergens or dietary-safety claims, recurring Calendar rules, cross-Family copying, interactive checklist state, and external checklist integrations. Each requires a separate domain review before implementation.
