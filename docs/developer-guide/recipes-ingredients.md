# Recipes and Ingredients

Concrete packaged Ingredient management is implemented through editing, placement, archival/restoration, direct Alternatives, and optional Nutrition Profiles. Recipes, Recipe Tags, Ingredient media, and Shopping Generation remain planned. This chapter separates current behavior from approved intent in the final Domain Glossary chapter and [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md).

## Concrete purchasable Ingredients

An authenticated member creates a concrete purchasable package from the **Suroviny** page. The required name is squished, limited to 255 characters, and case-insensitively unique in the Current Family through the ADR 0007 normalized key and a database constraint. The server derives ownership only from the authenticated User through `CurrentFamilyScope`; all Family members have equal rights, another Family may reuse the name, and a uniqueness race becomes a Czech inline `name` error.

Every Ingredient defines either one positive canonical weight in grams or one positive canonical volume in millilitres, never both, and may additionally define one positive piece count. The form accepts explicit `mg`, `g`, `kg`, `ml`, `cl`, and `l` inputs and normalizes them exactly before persistence without retaining a preference. Piece count may be the only package quantity. All values use `DECIMAL(20,6)` and database checks reject missing, non-positive, or weight-plus-volume combinations. Creation flashes **Surovina byla vytvořena.** and editing flashes **Surovina byla upravena.** Optional description is presentation metadata for the concrete package.

The Current-Family-only list derives `g`/`kg`, `ml`/`l`, and Czech `ks` display from canonical persistence. It also shows optional description, Store Placement, Nutrition Profile presence, direct Alternatives, and archive status. Another Family's records are neither offered nor accepted. Ingredient media remains unimplemented pending the approved upload policy.

## Measurement units and conversion

Metric input normalization and derived display follow [ADR 0030](../adr/0030-derive-metric-display-units.md). Editing blocks removal of a quantity kind used by the saved Nutrition Profile. Slice 3 must extend that same guard when Recipe Ingredient dependencies exist. Removing the Nutrition Profile and its package kind in one edit is valid because no dependency remains after the transaction.

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

Members can link or unlink two active Current-Family Ingredients as direct Alternatives. Persistence orders the two identifiers and stores the pair once, so reads are symmetric and A–B plus B–C never implies A–C. Self-links, duplicates, archived candidates, and cross-Family candidates become field errors. Archiving preserves existing edges but removes that Ingredient from new link choices.

The pure `AlternativeEligibility` boundary accepts an Alternative package and required canonical kinds and returns true only when every required grams, millilitres, or piece kind exists. It performs no cross-kind conversion. Applying a single-hop replacement, provenance, re-aggregation, and package rounding remain planned inside persistence-independent Shopping Generation. [ADR 0016](../adr/0016-require-canonical-quantity-kinds-for-alternative-replacement.md) and [ADR 0021](../adr/0021-keep-alternative-replacement-single-hop.md) record that future workflow.

## Archival

Ingredients use reversible archival rather than individual deletion. The list exposes **Aktivní**, **Archivované**, and **Všechny** filters. Archiving requires a consequence-stating confirmation and flashes **Surovina byla archivována.**; restoration needs no confirmation and flashes **Surovina byla obnovena.** Archived Ingredients are read-only until restored, retain direct Alternative edges and placement, and keep their normalized names reserved. Recipe archival and downstream retained-reference behavior remain planned with Slice 3. [ADR 0022](../adr/0022-archive-and-restore-recipes-and-ingredients.md) records the complete intended lifecycle.
