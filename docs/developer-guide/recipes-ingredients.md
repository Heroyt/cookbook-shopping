# Recipes and Ingredients

Concrete packaged Ingredient and complete Recipe management are implemented. Ingredients support editing, placement, archival/restoration, direct Alternatives, optional Nutrition Profiles, and private photos. Recipes support optimistic complete-aggregate saves, repeated ordered Ingredient lines, ordered Steps, approved metadata, Tags, search, archival/restoration, nutrition, and private covers. This chapter applies the final Domain Glossary chapter and [ADR 0001](../adr/0001-use-concrete-purchasable-ingredients.md).

## Concrete purchasable Ingredients

An authenticated member creates a concrete purchasable package from the **Suroviny** page. The required name is squished, limited to 255 characters, and case-insensitively unique in the Current Family through the ADR 0007 normalized key and a database constraint. The server derives ownership only from the authenticated User through `CurrentFamilyScope`; all Family members have equal rights, another Family may reuse the name, and a uniqueness race becomes a Czech inline `name` error.

Every Ingredient defines either one positive canonical weight in grams or one positive canonical volume in millilitres, never both, and may additionally define one positive piece count. The form accepts explicit `mg`, `g`, `kg`, `ml`, `cl`, and `l` inputs and normalizes them exactly before persistence without retaining a preference. Piece count may be the only package quantity. All values use `DECIMAL(20,6)` and database checks reject missing, non-positive, or weight-plus-volume combinations. Optional description is presentation metadata for the concrete package.

The Current-Family-only list derives `g`/`kg`, `ml`/`l`, and Czech `ks` display from canonical persistence. It also shows optional description, Store Placement, Nutrition Profile presence, direct Alternatives, protected photo, and archive status. Another Family's records are neither offered nor accepted.

## Measurement units and dependencies

Metric input normalization and derived display follow [ADR 0030](../adr/0030-derive-metric-display-units.md). Editing blocks removal of a quantity kind used by either the saved Nutrition Profile or any current Recipe Ingredient. A focused indexed dependency query performs that check rather than loading Recipe aggregates. Removing a Nutrition Profile and its package kind in one edit is valid only when no Recipe Ingredient dependency remains after the transaction.

## Recipe composition

A Recipe is valid immediately rather than having a draft state. It requires a case-insensitively unique Family-scoped name, a positive decimal base Serving Count, and at least one Recipe Ingredient. Optional metadata includes one private cover image, source URL, preparation duration, cooking duration, Recipe Notes, Recipe Tags, a complete per-serving Nutrition Override, and ordered Recipe Steps.

A Recipe Ingredient contains an active Current-Family Ingredient reference, positive decimal culinary quantity normalized to grams, millilitres, or piece count, and a contiguous position in one ordered list. Input-unit preference is not retained; display derives from the canonical quantity. The same Ingredient may occur on several lines. Ingredient groups and line-level preparation notes are absent; preparation detail belongs in ordered plain-text Recipe Steps, while Recipe Notes hold additional free-form information.

Recipe create and edit submit the complete aggregate with contiguous Ingredient and Step positions and, for edits, the version the User saw. Save locks and validates the Recipe, related Ingredients, and Tags, then replaces all scalar and child state transactionally. A stale version returns a Czech validation error and leaves every prior scalar, Ingredient, Step, Tag, and override value unchanged. Normalized-name races and invalid foreign-Family children are likewise converted or rejected without partial writes. [ADR 0032](../adr/0032-save-recipes-as-versioned-aggregates.md) records the concurrency boundary.

## Tags and search

Recipe Tags are fully custom, case-insensitively unique within the Family, and many-to-many with Recipes. They are distinct from the five fixed Calendar Meal Labels. After consequence confirmation, deleting a Tag transactionally detaches every Recipe assignment, leaves Recipes intact, and releases its normalized name for reuse; Tags are not archived. [ADR 0033](../adr/0033-delete-recipe-tags-with-assignment-cleanup.md) records this lifecycle.

Cookbook search matches Recipe names, assigned Recipe Tags, and referenced Ingredient names inside the Current Family. The focused projection deduplicates each Recipe into its strongest matching layer while retaining every reason, so a result appears once with name, Tag, and Ingredient indicators as applicable. Active, archived, and all status filters compose with search.

## Alternatives

Members can link or unlink two active Current-Family Ingredients as direct Alternatives. Persistence orders the two identifiers and stores the pair once, so reads are symmetric and A–B plus B–C never implies A–C. Self-links, duplicates, archived candidates, and cross-Family candidates become field errors. Archiving preserves existing edges but removes that Ingredient from new link choices.

The pure `AlternativeEligibility` boundary returns true only when an Alternative package defines every required grams, millilitres, or piece kind. Shopping Generation applies one direct active eligible Alternative per original Ingredient, uses the normal exact package pipeline, globally re-aggregates final Ingredients before rounding, preserves per-choice provenance, and permits explicit revert. It never edits source Recipes or chains from a substituted output. [ADR 0016](../adr/0016-require-canonical-quantity-kinds-for-alternative-replacement.md) and [ADR 0021](../adr/0021-keep-alternative-replacement-single-hop.md) record the workflow.

## Archival

Ingredients use reversible archival rather than individual deletion. The list exposes **Aktivní**, **Archivované**, and **Všechny** filters. Archiving requires a consequence-stating confirmation; restoration needs no destructive confirmation. Archived Ingredients are read-only until restored, retain direct Alternative edges, placement, and media, and keep their normalized names reserved.

Recipes use the same reversible lifecycle and retain names, children, Tags, media, and existing Calendar Entries. Archived Recipes are read-only until restored and cannot be added to a new Simple Plan or Calendar Entry. Existing Calendar Entries remain live and may change only Serving Count or be deleted. [ADR 0022](../adr/0022-archive-and-restore-recipes-and-ingredients.md) records the lifecycle.

## Private entity media

Store logos, Store Section images, Ingredient photos, and Recipe covers use one Family-scoped media pipeline. Uploads accept JPEG or PNG files up to 5 MB, validate extension, MIME, complete structure, and successful decode, and reject source dimensions above the configured pre-decode limits of 8192 pixels per side or 25,000,000 total pixels. The service preserves aspect ratio without upscaling and writes only configured WebP variants: 256×256 Store logos, 128×128 Store Section images, 480×480 and 1280×1280 Ingredient variants, and 640×360 and 1600×900 Recipe variants by default. Source limits, variant bounds, disk, root, and WebP quality are project configuration.

Paths and filenames are deterministic from Family, entity type, entity identifier, and variant. A later upload serializes writers through the entity lock, replaces the previous variants, removes obsolete variants, and restores the prior files if any write fails. Retrieval is authenticated, Current-Family scoped, private/no-store, and never exposes storage paths. Archive retains Recipe and Ingredient images; hard Store, Store Section, or Family deletion removes affected files. Cleanup failure restores prior files and rolls back the database lifecycle operation. Archived Recipes and Ingredients must be restored before changing their image.
