# Data structure

## Implemented persistence

The repository currently persists Users and authentication infrastructure only at the domain level. The initial migration creates `users`, `password_reset_tokens`, and `sessions`; a separate migration creates `passkeys`. Framework migrations also persist cache entries and locks, queued jobs, job batches, and failed jobs. The [`User` model](../../app/Models/User.php) exposes authentication and passkey behavior but no domain relationships. See the [migration directory](../../database/migrations/).

No Family, cookbook, Store, planning, nutrition, or Shopping List tables or models exist yet. The connected Laravel Boost database is intentionally excluded as evidence by the approved documentation specification.

The conceptual model below applies the decisions to use [Family ownership](../adr/0003-scope-domain-data-to-families.md), [concrete purchasable Ingredients](../adr/0001-use-concrete-purchasable-ingredients.md), a [persistence-independent generator](../adr/0002-keep-shopping-list-generation-persistence-independent.md), and one [Laravel modular monolith](../adr/0004-build-a-laravel-modular-monolith.md). Capability workflows remain authoritative in [Family access](family-access.md), [Recipes and Ingredients](recipes-ingredients.md), [Stores and shopping order](stores-shopping-order.md), [Calendar planning](calendar-planning.md), and [Shopping List generation](shopping-generation.md).

## Proposed relational shape

> **Planned** — The following structure is conceptual. It names durable relationships and constraints approved in [`CONTEXT.md`](../../CONTEXT.md); it is not a migration contract. Table names, key types, decimal precision, indexes, media storage, and snapshot encoding must be finalized during implementation.
>
>
> - **Family Access:** `families`, `family_memberships`, and a Current Family
>   preference. Membership is unique per User and Family and has no role. A User
>   can have many memberships. Current Family must reference one of that User's
>   memberships.
> - **Store layout:** `stores`, `store_sections`, and
>   `store_section_positions`. Stores and Sections belong to one Family. The
>   association between a Store and reusable Section carries traversal position
>   and is unique for that pair.
> - **Ingredients:** `ingredients`, `ingredient_package_quantities`, and
>   `ingredient_alternatives`. An Ingredient belongs to one Family, has at least
>   one positive package quantity, and optionally references one Store and one
>   Section valid for that Store. Alternative edges are symmetric and
>   non-transitive.
> - **Nutrition:** `ingredient_nutrition_profiles`. A profile belongs to an
>   Ingredient and stores an explicit basis quantity and unit plus kcal, fat,
>   protein, and carbohydrates.
> - **Recipes:** `recipes`, `recipe_ingredients`, `recipe_steps`, `recipe_tags`,
>   and `recipe_tag_assignments`. A Recipe belongs to one Family, has a positive
>   base Serving Count, and has one or more ordered Recipe Ingredients. Ingredient
>   lines may repeat. Steps are ordered. Tags are Family-scoped and many-to-many
>   with Recipes.
> - **Calendar:** `calendar_entries`. An entry belongs to one Family and
>   references a Recipe from that Family. Date, optional fixed Meal Label, and
>   positive Serving Count are stored. Recipe, date, and Meal Label form the
>   business uniqueness rule.
> - **Saved history:** `saved_shopping_lists` plus a snapshot payload or immutable
>   child records. A saved list belongs to one Family, is identified by generation
>   timestamp, and freezes output plus Calendar or Simple Plan provenance.

<!-- prettier-ignore -->
> **Planned**
>
> <!-- diagram-alt: A User joins many Families through roleless memberships; each Family owns Stores and reusable Sections, packaged Ingredients, Recipes and Tags, Calendar Entries, and saved Shopping List snapshots. Recipes contain Ingredient lines, and Stores order shared Sections through an association. -->
> ```mermaid
> erDiagram
>     USER ||--o{ FAMILY_MEMBERSHIP : joins
>     FAMILY ||--|{ FAMILY_MEMBERSHIP : has
>     FAMILY ||--o{ STORE : owns
>     FAMILY ||--o{ STORE_SECTION : owns
>     STORE ||--o{ STORE_SECTION_POSITION : orders
>     STORE_SECTION ||--o{ STORE_SECTION_POSITION : reused_by
>     FAMILY ||--o{ INGREDIENT : owns
>     INGREDIENT ||--|{ PACKAGE_QUANTITY : defines
>     INGREDIENT o|--o| INGREDIENT_NUTRITION : has
>     FAMILY ||--o{ RECIPE : owns
>     RECIPE ||--|{ RECIPE_INGREDIENT : contains
>     INGREDIENT ||--o{ RECIPE_INGREDIENT : referenced_by
>     RECIPE ||--o{ RECIPE_STEP : contains
>     FAMILY ||--o{ RECIPE_TAG : owns
>     RECIPE }o--o{ RECIPE_TAG : classified_by
>     FAMILY ||--o{ CALENDAR_ENTRY : owns
>     RECIPE ||--o{ CALENDAR_ENTRY : planned_as
>     FAMILY ||--o{ SAVED_SHOPPING_LIST : owns
> ```

## Ownership and authorization constraints

> **Planned** — Persist `family_id` on Family-owned aggregate roots even when Family could be inferred through another relation. This makes request scoping, policy checks, and isolation queries explicit. Every cross-record reference must point to a record in the same Family; foreign keys alone may not express this rule, so application validation, scoped relationship queries, and targeted tests are required.
>
> Use a unique membership constraint for `(family_id, user_id)`. Deleting a Family cascades through its owned data only after application-level destructive confirmation. Removing the final Family Membership is rejected before persistence. Current Family selection must be cleared or replaced if its membership disappears.

## Names, archival, and deletion

> **Planned** — Enforce case-insensitive uniqueness per Family for Ingredient, Recipe, Store, Store Section, and Recipe Tag names. The constraint includes archived Ingredients and Recipes. Normalize consistently at the application boundary and back it with a database strategy appropriate to the chosen database collation.
>
> Recipes and Ingredients use archival state rather than ordinary deletion when referenced by live or historical data. Store and Store-Section removal intentionally nulls optional Ingredient placement as defined in the glossary. Database actions must match those semantics: deleting a Store clears both placement fields; removing a Section from a Store clears only the affected Section reference.

## Units and exact quantities

> **Planned** — Store Serving Counts, Recipe Ingredient quantities, package quantities, nutrition bases, and macro values as fixed-precision decimals rather than binary floating point. Package Purchase Quantity is the only whole-number result. The implementation must choose precision and maximum scale only after representative household values and conversion limits are tested.
>
> A package-quantity record needs an Ingredient, unit identity, unit dimension, and positive quantity representing one whole package. Standard metric units (`mg`, `g`, `kg`, `ml`, `cl`, `l`) have stable conversion factors within their dimension, so a Recipe may use a supported metric unit convertible to the package's configured weight or volume dimension. Ingredient-specific count units such as piece or slice must be explicitly configured on that Ingredient. At least one package quantity per Ingredient is a transactional aggregate invariant; a simple non-null column cannot enforce it by itself.
>
> Removing a package quantity requires a dependency check against Recipe Ingredients and nutrition bases. A Recipe Ingredient stores the quantity and selected supported unit; standard metric units normalize within the configured package dimension, while count units reference an explicit Ingredient package equivalence. Generator input converts the normalized line into an exact package fraction.

## Placement integrity

> **Planned** — An Ingredient has nullable Store and Store Section references. When both exist, the Section must be associated with that Store. Prefer a constraint design that makes an invalid pair difficult to persist, supported by application validation. The Store-to-Section association owns the traversal position; a shared Section can therefore appear at different positions in different Stores.

## Recipe and Calendar integrity

> **Planned** — Recipe Ingredient lines and Recipe Steps need explicit positions. Ingredient lines deliberately do not have a uniqueness constraint on `(recipe_id, ingredient_id)`, because repeated Ingredients are allowed. Recipe Steps store plain text; Recipe Notes stay on the Recipe as a separate free-form field.
>
> A complete Recipe Nutrition Override is all-or-none across kcal, fat, protein, and carbohydrates and is expressed per serving. Enforce completeness in the domain and validation layers, and add a database check where supported.
>
> Calendar Entries store concrete dates and no recurrence fields. Their uniqueness rule is `(family, date, meal_label, recipe)`, including exactly one permitted unlabeled occurrence. Because SQL uniqueness commonly treats `NULL` values as distinct, the migration must deliberately handle the unlabeled case through a sentinel representation, expression index, or equivalent database-specific constraint plus application validation. Calendar Day and Simple Plan require no tables.
>
> Calendar Selection and Simple Plan are application inputs rather than extra persisted aggregates. Their adapters load and authorize the relevant Family-owned Recipes, then pass the same Recipe Selection contract to [Shopping List generation](shopping-generation.md#service-boundary).

## Immutable Shopping List history

> **Planned** — Saved Shopping Lists must survive later edits to Recipes, Ingredients, package definitions, names, Stores, Sections, alternatives, and Calendar Entries without recalculation. Persist the generated timestamp, source kind, provenance, grouping metadata, source-recipe breakdown, and every line's package count plus required, purchased, and surplus values in all units as snapshot values rather than live projections.
>
> A normalized set of immutable snapshot child records and a versioned JSON payload can both satisfy the domain. Choose one during implementation based on query requirements and migration strategy. In either case, include a payload/schema version and do not rely solely on foreign keys to mutable domain rows for historical display.
>
> The snapshot flow begins only after an explicit save of the transient generator result; [Shopping List generation](shopping-generation.md#transience-and-saved-snapshots) defines its output and provenance boundary.

## Open implementation decisions

> **Planned** — Resolve these points before writing the affected migrations:
>
> - case-insensitive uniqueness strategy that behaves consistently across the
>   selected production MariaDB database and local/test SQLite databases;
> - decimal precision and rounding policy for intermediate conversions;
> - whether Current Family preference is server-side per User or local per device while preserving membership validation;
> - how account provisioning works while self-registration is disabled, because add-by-email accepts only an already registered User;
> - how account deletion handles Families in which the User is the final member, without violating the no-orphan invariant;
> - storage and lifecycle for Recipe photos, Ingredient photos, Store logos, colours, and icons;
> - representation and validation of custom count-unit identifiers;
> - canonical storage for symmetric Alternative Ingredient edges;
> - normalized child records versus a versioned JSON payload for saved snapshots; and
> - deletion behavior when a reusable Store Section is deleted from the Family, rather than merely removed from one Store.
