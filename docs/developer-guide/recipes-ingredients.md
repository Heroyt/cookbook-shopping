# Recipes and Ingredients

Concrete packaged Ingredient creation and listing are implemented. Recipes, Recipe Tags, Ingredient editing and archival, alternatives, placement, nutrition, and media are not implemented. This chapter separates that current tracer from approved intent in the final Domain Glossary chapter and the concrete-package decision in [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md). See [Data structure](data-structure.md) for persistence details and [Shopping List generation](shopping-generation.md) for planned quantity aggregation.

## Concrete purchasable Ingredients

An authenticated member creates a concrete purchasable package from the **Suroviny** page. The required name is squished, limited to 255 characters, and case-insensitively unique in the Current Family through the ADR 0007 normalized key and a database constraint. The server derives ownership only from the authenticated User through `CurrentFamilyScope`; all Family members have equal rights, another Family may reuse the name, and a uniqueness race becomes a Czech inline `name` error.

Every current Ingredient defines either one positive weight entered and persisted in grams or one positive volume entered and persisted in millilitres, never both, and may additionally define one positive piece count. Piece count may also be the only package quantity, and it may be fractional. At least one quantity is required. All three fields use the approved `DECIMAL(20,6)` shape; the request rejects more than six fractional places, and a database check rejects missing, non-positive, or weight-plus-volume combinations. For example, one package may define `150 g` and `6 ks`, while another may define only `12,5 ks`. Creation redirects to the list and flashes **Surovina byla vytvořena.** [ADR 0026](../adr/0026-store-one-canonical-metric-package-quantity.md) records the package shape.

The list is Current-Family-only and derives display values from canonical persistence. Weight below 1000 renders in `g` and from 1000 in `kg`; volume uses `ml` and `l` at the same threshold. Values are half-up rounded to at most two fractional digits with trailing zeroes removed, and piece count renders as `ks`. The current form deliberately accepts only canonical `g` and `ml` inputs and does not retain an input-unit preference.

> **Planned**
>
> Ingredient editing, description, photo, Store Placement, Nutrition Profile, Alternative Ingredients, and reversible archival/restoration remain unimplemented. Archival will keep the normalized name reserved across active and archived records.

## Measurement units and conversion

> **Planned**
>
> Future forms may accept explicit metric weight units such as `mg`, `g`, and `kg`, or metric volume units such as `ml`, `cl`, and `l`, but must normalize values at the application boundary. [ADR 0030](../adr/0030-derive-metric-display-units.md) records the display rule that the current canonical-input tracer already applies.
>
> Future editing must block removal of weight, volume, or piece count while a Recipe Ingredient or nutrition basis depends on that canonical quantity kind, and an Ingredient must retain at least one quantity. Optional piece wording remains presentation only and never creates a distinct identity or affects calculation. Editing an existing package quantity will affect future Shopping List generation because Recipes retain normalized culinary quantities rather than package snapshots.

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
