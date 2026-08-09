# Shopping List generation

## Current status

Shopping List generation is not implemented. There is no generator service, Recipe persistence, Calendar persistence, Simple Plan, or saved-list history in the current repository. This chapter documents approved intended behavior from [`CONTEXT.md`](../../CONTEXT.md), [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md), and [ADR 0002](../adr/0002-keep-shopping-list-generation-persistence-independent.md).

## Service boundary

> **Planned** — Implement Shopping Generation as a pure, persistence-independent domain service inside the modular monolith. It accepts already resolved Recipe Selections and package facts, performs deterministic quantity calculations, and returns Shopping List Lines. It must not query Eloquent models, know whether selections originated in a Calendar or Simple Plan, write history, inspect pantry stock, or calculate prices.
>
> The application layer is responsible for authorizing the Current Family, loading only records from that Family, rejecting archived Recipes in new Simple Plans, and adapting either source into the same input contract. Existing Calendar Entries may resolve archived Recipes because their live references remain valid.

## Input contract

> **Planned** — Each generator invocation receives an unordered list of Recipe Selections. A selection contains a stable Recipe identity, display name, positive requested Serving Count, and a Recipe definition with a positive base Serving Count. Each Recipe Ingredient contains an Ingredient identity, positive decimal quantity, a supported unit, and the Ingredient's current package quantities. Standard metric units need only be convertible within a configured weight or volume dimension; Ingredient-specific count units must be explicitly configured. Duplicate Recipe Ingredients are valid.
>
> The input also carries enough immutable value data to form output and provenance without persistence access: Ingredient name, package-unit definitions, optional Store and Section placement, and source Recipe identity. Nutrition is not required for package calculation. Alternative selection is a deliberate transformation of the resolved input or result, not an automatic search performed by the core generator.
>
> A Calendar adapter gathers all Calendar Entries on an arbitrary selected set of dates and maps each to a Recipe Selection. A Simple Plan adapter maps its temporary unique Recipe rows directly. Neither source changes the generator's calculation rules.

## Calculation pipeline

> **Planned** — Use exact decimal arithmetic throughout intermediate calculations. For each Recipe Ingredient, scale its culinary quantity by `requested servings / base servings`. Normalize standard metric units within their dimension, then convert the scaled quantity into a fraction of that Ingredient's package using a configured weight, volume, or count equivalence. Package definitions, not a global density assumption, provide conversion between weight, volume, and count dimensions.
>
> Sum package fractions for every occurrence of the same final Ingredient across all selected Recipes before rounding. The Purchase Quantity is the ceiling of the combined package fraction and is therefore a positive whole package count. For each configured unit with package quantity `u`, derive:
>
> - required amount: `combined package fraction × u`;
> - purchased amount: `purchase package count × u`; and
> - surplus: `purchased amount − required amount`.
>
> This order is a core invariant. Two Recipes requiring `70 g` each from a `150 g` package produce `140 g required → buy 1 package`, not two independently rounded packages.

<!-- prettier-ignore -->
> **Planned**
>
> <!-- diagram-alt: Calendar Selection and transient Simple Plan are authorized and resolved by separate adapters, then feed the same pure generator, which scales Recipe quantities, converts them to package fractions, aggregates by final Ingredient, rounds once, and returns grouped lines that may optionally be saved as immutable history. -->
> ```mermaid
> flowchart LR
>     Calendar["Calendar Selection"] --> CalendarAdapter["Calendar adapter"]
>     Simple["Simple Plan"] --> SimpleAdapter["Simple Plan adapter"]
>     CalendarAdapter --> Resolve["Authorize Family and resolve Recipe Selections"]
>     SimpleAdapter --> Resolve
>     Resolve --> Scale["Scale Recipe Ingredient quantities"]
>     Scale --> Convert["Convert to package fractions"]
>     Convert --> Aggregate["Aggregate by final Ingredient"]
>     Aggregate --> Round["Round package count once"]
>     Round --> Present["Create and group Shopping List Lines"]
>     Present --> Transient["Transient result"]
>     Transient -->|"explicit save"| Snapshot["Immutable timestamped snapshot"]
> ```

## Output contract

> **Planned** — Return one Shopping List Line per final Ingredient after aggregation. Its primary instruction is the whole package Purchase Quantity. Secondary values include Required Quantity, purchased quantity, and Surplus in every unit configured on that Ingredient. Include the final Ingredient identity and name, package definition, Store Placement used for grouping, and a contribution breakdown by source Recipe.
>
> Group lines first by Store, then by that Store's Section traversal order. Place Store-assigned lines without a Section after configured Sections, and unplaced Ingredients after all Store groups. Sort Ingredient names alphabetically within a Section. Inter-Store order has no domain significance and must not influence totals or snapshot identity.

## Alternative Ingredients

> **Planned** — Initial generation uses the concrete Ingredients referenced by Recipes. A User may then select an explicitly linked Alternative Ingredient. Links are symmetric but not transitive, so the application offers only direct alternatives.
>
> Recalculate automatically only when source requirement and alternative share a compatible configured measure. Translate the exact required amount to a package fraction of the alternative, replace the target Ingredient, and globally re-aggregate all lines that now resolve to that same alternative before rounding. The replacement line adopts the alternative's Store Placement and package units. If no compatible conversion exists, require a manual replacement quantity before accepting the substitution. Never change source Recipes.

## Nutrition boundary

> **Planned** — Nutrition and Shopping List quantity generation share Recipe inputs but have separate rules. Recipe and Calendar-day nutrition can be calculated alongside planning views, yet missing nutrition must never block Shopping List generation or be interpreted as zero. The package generator does not need kcal or macros to produce Purchase Quantities.

## Transience and saved snapshots

> **Planned** — Return a transient result by default. Generating a list must not create a database record. An explicit save command records immutable output and provenance as a Saved Shopping List identified by its generation timestamp.
>
> Calendar provenance includes the selected dates; Simple Plan provenance includes the Recipe identities and requested Serving Counts. The snapshot freezes displayed names, package definitions, alternative choices, Store/Section grouping, contributions, all derived unit values, and source kind. Subsequent edits never recalculate it. Saving and deletion belong to the application/persistence layer, not the pure generator.

## Validation and failure behavior

> **Planned** — Reject or prevent generation when an input has a non-positive Serving Count, a Recipe has no positive base Serving Count, a Recipe Ingredient has a non-positive quantity, its unit is neither a supported standard metric unit convertible to the configured package dimension nor an explicitly configured count unit, or a required package conversion cannot be established. Family isolation and eligibility failures are application-layer authorization/validation errors rather than arithmetic errors.
>
> Do not silently guess density, reinterpret missing units, round per Recipe, discard unknown contributions, or substitute an alternative. A calculation failure must identify the Recipe, Ingredient, quantity, and unit that could not be converted so the stored definition can be corrected.

## Testable invariants

> **Planned** — Unit-test the generator without Laravel or a database. At minimum, cover fractional Serving Counts, repeated Ingredients within one Recipe, cross-Recipe aggregation before rounding, mixed configured units, weight-volume separation, count conversion, exact whole-package boundaries, Surplus in every unit, source breakdowns, and deterministic results independent of input order.
>
> Application tests must separately cover Family scoping, Calendar and Simple Plan adaptation, archived-record eligibility, alternative re-aggregation and manual fallback, grouping order, transient-by-default behavior, and immutable saved history. Property-based or table-driven cases are especially useful for the invariants `purchased ≥ required`, `surplus = purchased − required`, and `purchase packages = ceiling(required packages)`.

## Unresolved implementation choices

> **Planned** — Before coding the service, settle the fixed decimal representation and scale, error/result type, whether grouping is returned by the domain service or a presentation collaborator, and the exact compatible-measure rule for manual alternative fallback. These choices must preserve the persistence-independent interface and approved calculation order.
