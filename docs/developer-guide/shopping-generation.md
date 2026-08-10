# Shopping List generation

## Current status

Shopping List generation is not implemented. There is no generator service, Recipe persistence, Calendar persistence, Simple Plan, or saved-list history in the current repository. This chapter documents approved intended behavior from the final Domain Glossary chapter, [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md), and [ADR 0002](../adr/0002-keep-shopping-list-generation-persistence-independent.md).

## Service boundary

> **Planned** — Implement Shopping Generation as a pure, persistence-independent domain service inside the modular monolith. Its public facade accepts already resolved Recipe Selections and package facts, composes a quantity calculator with a pure grouping collaborator, and returns an all-or-nothing typed result containing either a complete grouped Shopping List or structured Calculation Problems. It must not query Eloquent models, know whether selections originated in a Calendar or Simple Plan, write history, inspect pantry stock, or calculate prices. [ADR 0017](../adr/0017-return-an-all-or-nothing-typed-generation-result.md) and [ADR 0018](../adr/0018-compose-grouping-inside-shopping-generation.md) record this boundary.
>
> The application layer is responsible for authorizing the Current Family, loading only records from that Family, rejecting archived Recipes in new Simple Plans, and adapting either source into the same input contract. Existing Calendar Entries may resolve archived Recipes because their live references remain valid.

## Input contract

> **Planned** — Each generator invocation receives an unordered list of Recipe Selections. A selection contains a stable Recipe identity, display name, positive requested Serving Count, and a Recipe definition with a positive base Serving Count. Each Recipe Ingredient contains an Ingredient identity, a positive decimal quantity already normalized as grams, millilitres, or piece count, and the Ingredient's current canonical package quantities. An Ingredient package has weight or volume but never both, and may additionally have a piece count. Duplicate Recipe Ingredients are valid.
>
> The input also carries enough immutable value data to form output and provenance without persistence access: Ingredient name, package-unit definitions, optional Store and Section placement, and source Recipe identity. Nutrition is not required for package calculation. Alternative selection is a deliberate transformation of the resolved input or result, not an automatic search performed by the core generator.
>
> A Calendar adapter gathers all Calendar Entries on an arbitrary selected set of dates and maps each to a Recipe Selection. A Simple Plan adapter maps its temporary unique Recipe rows directly. Neither source changes the generator's calculation rules.

## Calculation pipeline

> **Planned** — Convert persisted `DECIMAL(20,6)` inputs to exact rational values for all intermediate calculations. Reject over-scale normalized inputs instead of silently quantizing them. For each Recipe Ingredient, scale its canonical grams, millilitres, or piece count by `requested servings / base servings`, then divide it by the matching canonical package quantity. Metric input normalization belongs to the application adapter and never occurs inside the generator. A package's optional piece count can express its metric-to-piece equivalence, but weight and volume cannot coexist and no density conversion exists. [ADR 0013](../adr/0013-use-exact-rational-quantity-calculation.md) and [ADR 0026](../adr/0026-store-one-canonical-metric-package-quantity.md) record these choices.
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
>     Round --> Lines["Create calculated Shopping List Lines"]
>     Lines --> Group["Pure Store and Section grouping"]
>     Group --> Transient["Complete transient result"]
>     Transient -->|"explicit save"| Snapshot["Immutable timestamped snapshot"]
> ```

## Output contract

> **Planned** — Return one Shopping List Line per final Ingredient after aggregation. Its primary instruction is the whole package Purchase Quantity. Secondary values include Required Quantity, purchased quantity, and Surplus in every canonical quantity kind configured on that Ingredient, with display units derived at the presentation boundary. Include the final Ingredient identity and name, package definition, Store Placement used for grouping, and a contribution breakdown by source Recipe.
>
> A dedicated pure collaborator inside Shopping Generation groups lines first by Store, then by that Store's Section traversal order. Store groups sort deterministically by the application-normalized Store name with stable identity as a tie-breaker, although this order carries no domain significance and must not influence totals or snapshot identity. Store-assigned lines without a Section follow configured Sections, unplaced Ingredients follow all Store groups. Ingredient names use their application-normalized UTF-8 byte key and stable identity as the final comparator, so ordering is identical across input order, SQLite, MariaDB, and platform locale while accent-distinct names remain distinct. The public generator facade returns this complete grouping; Inertia only renders it.

## Alternative Ingredients

> **Planned** — Initial generation uses the concrete Ingredients referenced by Recipes. A User may then select an explicitly linked active Alternative Ingredient. Links are symmetric but not transitive, so the application offers only direct active alternatives while retaining edges to archived Ingredients for historical structure.
>
> Offer an Alternative only when its package configures every canonical quantity kind used by the replaced Recipe Ingredient contributions. User inputs such as kilograms or litres were normalized before persistence, so eligibility compares grams, millilitres, and piece count without parsing or converting display units. Divide each normalized requirement by the Alternative's matching package quantity using the ordinary package-count pipeline; never bridge a missing quantity kind. There is no manual replacement-quantity fallback. Each originally generated Ingredient may be replaced once by one direct Alternative; a substituted or merged result cannot be substituted again. Then globally re-aggregate all lines that resolve to the same final Alternative before rounding while retaining separate original contribution and choice provenance so each replacement can be reverted or changed. The replacement line adopts the Alternative's Store Placement and package quantities. Never change source Recipes. [ADR 0016](../adr/0016-require-canonical-quantity-kinds-for-alternative-replacement.md) and [ADR 0021](../adr/0021-keep-alternative-replacement-single-hop.md) record these rules.

## Nutrition boundary

> **Planned** — Nutrition and Shopping List quantity generation share Recipe inputs but have separate rules. Recipe and Calendar-day nutrition can be calculated alongside planning views, yet missing nutrition must never block Shopping List generation or be interpreted as zero. The package generator does not need kcal or macros to produce Purchase Quantities.

## Transience and saved snapshots

> **Planned** — Return a transient result by default. Generating a list must not create a database record. An explicit save command records immutable output and provenance as a Saved Shopping List identified by its generation timestamp.
>
> Calendar provenance includes the selected dates; Simple Plan provenance includes the Recipe identities and requested Serving Counts. Relational headers store Family ownership, generation timestamp, source kind, and payload schema version; one immutable versioned JSON payload freezes displayed names, package definitions, Alternative choices, Store/Section grouping, contributions, losslessly encoded exact quantities, two-decimal rendered values, and source provenance. Subsequent edits never recalculate it. Every accepted save creates a new snapshot even for identical content or a repeated request; the UI may lock only while its current request is processing. Saving and deletion belong to the application/persistence layer, not the pure generator. [ADR 0020](../adr/0020-store-versioned-shopping-list-snapshot-payloads.md) and [ADR 0025](../adr/0025-create-a-snapshot-for-every-save.md) record the history boundary.

## Validation and failure behavior

> **Planned** — Reject or prevent generation when an input has a non-positive Serving Count, a Recipe has no positive base Serving Count, a Recipe Ingredient has a non-positive normalized quantity, its canonical quantity kind is absent from the Ingredient package, or a required package fraction cannot be established. Reject non-canonical generator inputs; explicit metric-unit parsing and normalization belong to the application boundary. Family isolation and eligibility failures are application-layer authorization/validation errors rather than arithmetic errors.
>
> Return either the complete Shopping List or a typed collection of every recoverable Calculation Problem found. Each problem identifies the Recipe, Ingredient, quantity, unit, and reason so the stored definition can be corrected. The application presents the complete problem collection, links to the relevant Recipe or Ingredient editor, preserves the source Calendar Selection or Simple Plan, and requires an explicit retry after correction. Never return a partial list, and reserve exceptions for programming errors or violated internal invariants. Do not silently guess density, reinterpret missing units, round per Recipe, discard unknown contributions, or substitute an Alternative. [ADR 0017](../adr/0017-return-an-all-or-nothing-typed-generation-result.md) records the failure contract.

## Presentation precision

> **Planned** — Keep exact rational values throughout calculation and snapshot provenance, but derive metric display units without storing a preference: weight below 1000 displays in grams and from 1000 in kilograms; volume below 1000 displays in millilitres and from 1000 in litres. Then render all secondary required, purchased, and Surplus quantities with at most two fractional digits using decimal half-up rounding and stripped trailing zeroes, marking a value approximate whenever this changes the exact value. Piece counts retain the internal canonical kind `piece` and render with the Czech label `ks`, while the primary Purchase Quantity remains an exact whole package count. [ADR 0030](../adr/0030-derive-metric-display-units.md) records the unit rule.

## Testable invariants

> **Planned** — Unit-test the generator without Laravel or a database. At minimum, cover fractional Serving Counts, repeated Ingredients within one Recipe, cross-Recipe aggregation before rounding, canonical grams, millilitres, and piece counts, the weight-volume exclusivity invariant, metric-to-piece package equivalence, exact whole-package boundaries, Surplus in every configured quantity kind, source breakdowns, and deterministic results independent of input order.
>
> Application tests must separately cover Family scoping, Calendar and Simple Plan adaptation, archived-record eligibility, exact-unit Alternative filtering, single-hop re-aggregation, grouping order, transient-by-default behavior, and immutable saved history. Pure tests must prove the normalized-name/stable-identity comparator is independent of input order and database collation, including Czech and accent-distinct names. Property-based or table-driven cases are especially useful for the invariants `purchased ≥ required`, `surplus = purchased − required`, and `purchase packages = ceiling(required packages)`.

## Resolved implementation boundary

> **Planned** — The blocking generator choices are settled: exact rational arithmetic over canonical grams, millilitres, and piece counts; canonical-kind-only single-hop Alternative eligibility with no manual fallback; an all-or-nothing typed result; and deterministic grouping through a dedicated pure collaborator behind the public facade. Concrete PHP value-object and collection names remain implementation details and must preserve these contracts.
