# Recipes and Ingredients

Recipes, Ingredients, Recipe Tags, and their media are not implemented. This chapter expands the approved terms in the final Domain Glossary chapter and the concrete-package decision in [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md). See [Data structure](data-structure.md) for the proposed persistence shape and [Shopping List generation](shopping-generation.md) for quantity aggregation.

## Concrete purchasable Ingredients

> **Planned**
>
> An Ingredient represents one concrete package the Family buys, not a generic culinary concept plus separate products. Its case-insensitively unique name remains reserved while active or archived. Optional metadata includes a photo and free-form description.
>
> Every Ingredient defines either one positive weight persisted in grams or one positive volume persisted in millilitres, never both, and may additionally define one positive piece count. At least one quantity is required. For example, one ham Ingredient can define `150 g` and `6 pieces`; `75 g` and `3 pieces` each represent half a package before Recipe scaling. [ADR 0026](../adr/0026-store-one-canonical-metric-package-quantity.md) records the shape.

## Measurement units and conversion

> **Planned**
>
> Forms may accept explicit metric weight units such as `mg`, `g`, and `kg`, or metric volume units such as `ml`, `cl`, and `l`, but normalize values at the application boundary. Persistence and calculation use grams for weight and millilitres for volume, and no input-unit preference is stored. Display uses `g` or `ml` below 1000 and `kg` or `l` from 1000. An Ingredient cannot define both weight and volume, so the system never stores or guesses density. [ADR 0030](../adr/0030-derive-metric-display-units.md) records the display rule.
>
> Piece quantity is stored as a count under the internal canonical kind `piece`, without a selectable unit identity. The Czech interface renders the localized label `ks`; optional descriptive wording never creates a distinct identity or affects calculation. Piece quantities may be fractional. Removing the weight, volume, or piece quantity is blocked while a Recipe Ingredient or nutrition basis depends on that quantity kind, and an Ingredient must retain at least one quantity. Editing an existing package quantity affects future Shopping List generation because Recipes retain normalized culinary quantities rather than package snapshots.

## Recipe composition

> **Planned**
>
> A Recipe is valid immediately rather than having a draft state. It requires a case-insensitively unique Family-scoped name, a positive decimal base Serving Count, and at least one Recipe Ingredient. Optional metadata includes one cover photo, source URL, preparation duration, cooking duration, Recipe Notes, Recipe Tags, and ordered Recipe Steps.
>
> A Recipe Ingredient contains an Ingredient reference, positive decimal culinary quantity normalized to the Ingredient's grams, millilitres, or piece-count kind, and defined position in one ordered list. Input-unit preference is not retained; display derives from the canonical quantity. The same Ingredient may occur on several lines for separate preparation stages. Ingredient groups and line-level preparation notes are deliberately absent; preparation detail belongs in the ordered plain-text Recipe Steps, while Recipe Notes hold additional free-form information.
>
> Recipe create and edit submit the complete aggregate with its contiguous Ingredient and Step positions and, for edits, the version the User saw. Save locks and validates the Recipe and replaces its child state transactionally. A stale version is rejected with fresh data rather than silently overwriting another member's changes. [ADR 0032](../adr/0032-save-recipes-as-versioned-aggregates.md) records the concurrency boundary.

## Tags and search

> **Planned**
>
> Recipe Tags are fully custom, case-insensitively unique within the Family, and many-to-many with Recipes. They are distinct from the five fixed Calendar Meal Labels. After consequence confirmation, deleting a Tag transactionally detaches every Recipe assignment, leaves the Recipes intact, and releases its normalized name for reuse; Tags are not archived. [ADR 0033](../adr/0033-delete-recipe-tags-with-assignment-cleanup.md) records this lifecycle.
>
> Cookbook search matches Recipe names, assigned Recipe Tags, and referenced Ingredient names. Results are displayed in separate reason layers but deduplicated into the strongest matching layer. A Recipe that matches several sources appears once with indicators explaining all matches.

## Alternatives

> **Planned**
>
> Alternative Ingredient links are symmetric and explicitly non-transitive. Linking A to B makes B available from A, but an A–B and B–C chain does not imply A–C. Alternatives are manually selected after initial generation and never modify the source Recipe.
>
> Persist each direct relationship once in a canonical self-referential many-to-many edge whose two Ingredient identifiers are ordered and unique for the pair. Both Ingredients must belong to the same Family; mirrored rows and equivalence groups are invalid representations.
>
> Offer an Alternative only when its package defines every canonical quantity kind used by the replaced Recipe Ingredient contributions: grams, millilitres, or piece count. User-facing metric units have already been normalized, and no cross-kind conversion establishes eligibility. There is no manual replacement-quantity fallback. Each originally generated Ingredient permits at most one direct replacement; a substituted or merged result cannot be substituted again. A successful replacement adopts the Alternative's package and Store Placement and globally re-aggregates every line targeting that final Ingredient before package rounding while retaining independently reversible source-choice provenance. [ADR 0016](../adr/0016-require-canonical-quantity-kinds-for-alternative-replacement.md) and [ADR 0021](../adr/0021-keep-alternative-replacement-single-hop.md) record these rules; [Shopping List generation](shopping-generation.md#alternative-ingredients) defines the workflow.

## Archival

> **Planned**
>
> Recipes and Ingredients have reversible archival and restoration rather than individual hard deletion in the MVP. Archived Ingredients cannot be added to new Recipe Ingredient lines or offered for a new Alternative selection, but existing Recipes continue to use them and Alternative edges remain stored. A concurrently archived Alternative is rejected when a transient replacement is submitted. Archived Recipes cannot enter new Calendar Entries or Simple Plans, but existing Calendar Entries continue to resolve their current definition. Restoration makes the entity eligible for new use again; only Family deletion ultimately removes it and its media. [ADR 0022](../adr/0022-archive-and-restore-recipes-and-ingredients.md) records the lifecycle.
>
> Recipe and Ingredient lists expose `Active`, `Archived`, and `All` filters. Archived records are read-only apart from restoration; a User must restore one before editing it. Archiving requires a consequence-stating confirmation and visible feedback. Restoration is non-destructive, requires no confirmation, and returns the User to an editable active record with visible feedback.
>
> Archiving does not release a name for reuse. Saved Shopping List snapshots remain independent of later edits or archival because they retain immutable generated values rather than live projections.
