# Recipes and Ingredients

Recipes, Ingredients, Recipe Tags, and their media are not implemented. This chapter expands the approved terms in [`CONTEXT.md`](../../CONTEXT.md) and the concrete-package decision in [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md). See [Data structure](data-structure.md) for the proposed persistence shape and [Shopping List generation](shopping-generation.md) for quantity aggregation.

## Concrete purchasable Ingredients

> **Planned**
>
> An Ingredient represents one concrete package the Family buys, not a generic culinary concept plus separate products. Its case-insensitively unique name remains reserved while active or archived. Optional metadata includes a photo and free-form description.
>
> Every Ingredient defines at least one positive package quantity. Each configured quantity describes the same whole package; for example, one ham Ingredient can define `150 g` and `6 pieces`. Those equivalents allow a Recipe to use `75 g` or `3 pieces`, each representing half a package before Recipe scaling.

## Measurement units and conversion

> **Planned**
>
> Supported standard units are metric weight (`mg`, `g`, `kg`) and metric volume (`ml`, `cl`, `l`). Standard metric units convert globally within their own dimension, so a package expressed in grams can satisfy a Recipe quantity entered in kilograms. Weight and volume remain distinct unless that Ingredient's package explicitly defines equivalent quantities in both dimensions; the system never guesses density.
>
> Ingredient-specific count units, such as piece or slice, convert only through that Ingredient's configured package equivalents. Count quantities may be fractional. Removing a configured package quantity is blocked while a Recipe Ingredient or nutrition basis depends on it. Editing an existing package quantity affects future Shopping List generation because Recipes retain culinary quantities rather than package snapshots.

## Recipe composition

> **Planned**
>
> A Recipe is valid immediately rather than having a draft state. It requires a case-insensitively unique Family-scoped name, a positive decimal base Serving Count, and at least one Recipe Ingredient. Optional metadata includes one cover photo, source URL, preparation duration, cooking duration, Recipe Notes, Recipe Tags, and ordered Recipe Steps.
>
> A Recipe Ingredient contains an Ingredient reference, positive decimal culinary quantity, supported unit, and defined position in one ordered list. The same Ingredient may occur on several lines for separate preparation stages. Ingredient groups and line-level preparation notes are deliberately absent; preparation detail belongs in the ordered plain-text Recipe Steps, while Recipe Notes hold additional free-form information.

## Tags and search

> **Planned**
>
> Recipe Tags are fully custom, case-insensitively unique within the Family, and many-to-many with Recipes. They are distinct from the five fixed Calendar Meal Labels.
>
> Cookbook search matches Recipe names, assigned Recipe Tags, and referenced Ingredient names. Results are displayed in separate reason layers but deduplicated into the strongest matching layer. A Recipe that matches several sources appears once with indicators explaining all matches.

## Alternatives

> **Planned**
>
> Alternative Ingredient links are symmetric and explicitly non-transitive. Linking A to B makes B available from A, but an A–B and B–C chain does not imply A–C. Alternatives are manually selected after initial generation and never modify the source Recipe.
>
> Automatic replacement is permitted only when the alternative can express the original Required Quantity through a compatible configured measure. Otherwise the User must enter the replacement quantity manually. A successful replacement adopts the alternative's package and Store Placement and globally re-aggregates every line targeting that final Ingredient before package rounding. See [Shopping List generation](shopping-generation.md#alternative-ingredients) for the workflow.

## Archival

> **Planned**
>
> Recipes and Ingredients are archived instead of ordinarily deleted when they remain part of live references or history. Archived Ingredients cannot be added to new Recipe Ingredient lines, but existing Recipes continue to use them. Archived Recipes cannot enter new Calendar Entries or Simple Plans, but existing Calendar Entries continue to resolve their current definition.
>
> Archiving does not release a name for reuse. Saved Shopping List snapshots remain independent of later edits or archival because they retain immutable generated values rather than live projections.
