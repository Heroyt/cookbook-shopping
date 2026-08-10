# Stores and shopping order

The first Store tracer is implemented: authenticated members can list, create, rename, and delete Stores in the Current Family from the responsive Stores page. Creation and rename squish repeated whitespace and enforce a 255-character limit. A PHP-generated lowercase `normalized_name`, stored as bytes and constrained by `(family_id, normalized_name)`, makes name uniqueness race-safe and independent of SQLite/MariaDB text collations; [ADR 0007](../adr/0007-use-application-normalized-keys-for-scoped-name-uniqueness.md) records the trade-off. Rename and deletion resolve the Store through `CurrentFamilyScope`, so a Store from another Family returns not found and a client-supplied Family identifier cannot redirect either write. Database unique-key collisions become field validation errors. Tests prove equal create/rename/delete rights, cross-Family read/write isolation, normalization, duplicate handling, and that accent-distinct names remain distinct. The current deletion-inclusive Store suite passes 15 tests and 132 assertions on the default SQLite test connection. Separately, a full migration and the earlier rename-inclusive suite of 13 tests and 109 assertions passed against a disposable MariaDB 11.8 database. That local compatibility check is not evidence about the external Komodo deployment.

Store logos, Store Sections, Store Placements, and Shopping List grouping are not implemented. Canonical terms are in the final Domain Glossary chapter. The ownership model follows [ADR 0003](../adr/0003-scope-domain-data-to-families.md), while [Shopping List generation](shopping-generation.md) defines the calculated lines that placement will organize.

## Rename a Store

The User must be authenticated, belong to the selected Current Family, and already have a Store in that Family. On the Stores page, choose **Přejmenovat** (Rename) for the Store, edit its pre-filled name in the **Přejmenovat obchod** Dialog, and choose **Přejmenovat obchod**. **Zrušit** (Cancel) closes the Dialog without submitting.

The submitted name is required after repeated whitespace is squished and must contain at most 255 characters. A normalized duplicate of another Store name in the Current Family appears as an inline name error and leaves the existing Store unchanged. A Store identifier owned by another Family is not found; a submitted Family identifier cannot redirect the write.

After a successful rename, the Dialog closes, the Stores page shows the persisted display name, and the application flashes **Obchod byl přejmenován.**

## Delete a Store

The User must be authenticated, belong to the selected Current Family, and choose an existing Store in that Family. On the Stores page, choose **Smazat** (Delete), review the permanent-deletion warning in the **Smazat obchod** confirmation AlertDialog, and choose **Smazat obchod**. Choosing **Zrušit** (Cancel) closes the AlertDialog without deleting anything.

The request supplies only the Store identifier. The backend derives Family context from the authenticated User and returns not found for a Store in another Family; a submitted Family identifier cannot redirect the operation. A missing or already-deleted Store also returns not found, as does deletion without a valid Current Family. In those cases the success redirect and flash do not run: refresh a stale Stores page, or select a valid Current Family before retrying. The current implementation hard-deletes the Store. No Ingredient or Store Section persistence exists yet, so there are no placement references to clear in this tracer.

After a successful deletion, the Stores page no longer lists the Store and the application flashes **Obchod byl smazán.**

## Stores and reusable Sections

> **Planned**
>
> Optional Store logos remain to be added. A Store Section belongs to one Family and has a case-insensitively unique name, user-selected six-digit hexadecimal colour, and optional icon from the supported application catalogue. The UI uses a colour picker while the colour remains presentation metadata with no domain meaning. Sections are reusable: several Stores may associate with the same Section entity.
>
> Each Store maintains its own ordered list of associated Store Sections. The association stores a contiguous integer position unique within that Store, so a shared Section can appear at different positions in different Stores. Reordering submits the complete associated Section sequence and the order version the User saw, locks that Store's associations, validates exact membership and uniqueness, and rewrites positions transactionally. A stale version is rejected with the fresh order for review rather than silently overwriting a concurrent member's change. [ADR 0023](../adr/0023-rewrite-contiguous-store-section-positions.md) and [ADR 0028](../adr/0028-reject-stale-store-section-reorders.md) record this mechanism.

## Ingredient Store Placement

> **Planned**
>
> An Ingredient belongs to at most one Store and optionally to one Store Section associated with that Store. Both values are optional. A Section without its matching Store is invalid, and a Section associated only with another Store cannot be assigned.
>
> Placement is guidance for grouping and walking order, not availability, inventory, or price data. Choosing an Alternative Ingredient replaces the final Ingredient and therefore uses that alternative's Store Placement.

## Removal behavior

> **Planned**
>
> Removing a Store Section from one Store clears that Section from Ingredient placements in that Store and leaves their Store assignment intact. Those Ingredients move to the Store's unsectioned group.
>
> When Ingredients and Store Placements are implemented, deleting a Store must clear both Store and Store Section placement on affected Ingredients. It must not delete the Ingredients or alter Recipe composition; they move to the globally unplaced group.
>
> Deleting a reusable Store Section entity from the Family requires a destructive confirmation that reports its affected Store-association and Ingredient-placement counts. One transaction removes every Store association, clears that Section from every affected Ingredient while retaining each Store assignment, and deletes the Section; Ingredients and Recipes remain intact, no dangling placement is possible, and the Section name becomes reusable. [ADR 0012](../adr/0012-delete-store-sections-with-placement-preserving-cleanup.md) records this lifecycle.

## Shopping List grouping and order

> **Planned**
>
> Group final Shopping List Lines first by Store and then by that Store's Section traversal order. Sort Store groups deterministically by the application-normalized Store name with stable identity as a tie-breaker, although that order has no domain significance. A Store's unsectioned Ingredients appear after its configured Sections, and Ingredients without a Store appear after all Store groups.
>
> Sort Ingredient names within a Section by the same application-normalized name semantics used for scoped uniqueness, comparing the stored normalized UTF-8 bytes and then stable Ingredient identity. This makes Czech and accent-distinct names deterministic across database collations and input order without claiming locale-aware dictionary collation. Replacements and global re-aggregation happen before grouping, so each line appears under the Store Placement of the concrete Ingredient that will be purchased. Placement never changes Required Quantity or Purchase Quantity.

The conceptual associations and integrity checks are described in [Data structure](data-structure.md#placement-integrity). The delivery sequence is in Slice 2 of the [Implementation roadmap](implementation-roadmap.md#slice-2-stores-and-packaged-ingredients).
