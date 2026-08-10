# Stores and shopping order

The Store, reusable Store Section, and ordered association tracers are implemented. Authenticated members can list, create, rename, and delete Stores; list and create reusable Store Sections; and maintain each Store's ordered Section traversal in the Current Family from the responsive Stores page. Store and Store Section names squish repeated whitespace and enforce a 255-character limit. A PHP-generated lowercase `normalized_name`, stored as bytes and constrained by `(family_id, normalized_name)` in each table, makes name uniqueness race-safe and independent of SQLite/MariaDB text collations; [ADR 0007](../adr/0007-use-application-normalized-keys-for-scoped-name-uniqueness.md) records the trade-off. Store, Store Section, and association commands resolve their records through `CurrentFamilyScope` and accept no Family identifier. Database unique-key collisions become field validation errors. Tests prove equal member rights, two-Family isolation, normalization, malformed-input rejection, duplicate handling, client input that cannot redirect ownership, contiguous association positions, exact complete reorders, and stale-version rejection. The current Store, Store Section, and association suites pass 31 tests and 330 assertions on the default SQLite test connection. Separately, a full migration and the earlier rename-inclusive Store suite of 13 tests and 109 assertions passed against a disposable MariaDB 11.8 database. That earlier local compatibility check predates Store Section and association persistence and is not evidence about the external Komodo deployment.

Store logos, optional Store Section icons, Store Section entity deletion, Store Placements, and Shopping List grouping are not implemented. Store Section colour is stored only as presentation metadata. Canonical terms are in the final Domain Glossary chapter. The ownership model follows [ADR 0003](../adr/0003-scope-domain-data-to-families.md), while [Shopping List generation](shopping-generation.md) defines the calculated lines that placement will organize.

## Create and list Store Sections

The User must be authenticated and belong to a Family. The server uses their validated Current Family preference, or selects the lowest-identifier remaining membership when that preference is absent or stale. On the Stores page, enter the required **Název části obchodu** (Store Section name), use the visible **Barva části obchodu** (Store Section colour) picker, and choose **Vytvořit část obchodu**. The submitted name is squished, required, and limited to 255 characters. The colour must use the `#RRGGBB` form with exactly six hexadecimal digits.

The backend derives Family ownership only from the authenticated User through `CurrentFamilyScope`; extra client input cannot select another Family. A normalized duplicate in the Current Family returns an inline name error, including when the database uniqueness constraint resolves a concurrent collision. The same normalized name remains available in another Family. After success, the page lists the persisted display name, a visual colour swatch, and the hexadecimal text so colour is not the only signal, then flashes **Část obchodu byla vytvořena.** Every Family member has the same create and list rights.

## Rename a Store

The User must be authenticated, belong to the selected Current Family, and already have a Store in that Family. On the Stores page, choose **Přejmenovat** (Rename) for the Store, edit its pre-filled name in the **Přejmenovat obchod** Dialog, and choose **Přejmenovat obchod**. **Zrušit** (Cancel) closes the Dialog without submitting.

The submitted name is required after repeated whitespace is squished and must contain at most 255 characters. A normalized duplicate of another Store name in the Current Family appears as an inline name error and leaves the existing Store unchanged. A Store identifier owned by another Family is not found; a submitted Family identifier cannot redirect the write.

After a successful rename, the Dialog closes, the Stores page shows the persisted display name, and the application flashes **Obchod byl přejmenován.**

## Delete a Store

The User must be authenticated, belong to the selected Current Family, and choose an existing Store in that Family. On the Stores page, choose **Smazat** (Delete), review the permanent-deletion warning in the **Smazat obchod** confirmation AlertDialog, and choose **Smazat obchod**. Choosing **Zrušit** (Cancel) closes the AlertDialog without deleting anything.

The request supplies only the Store identifier. The backend derives Family context from the authenticated User and returns not found for a Store in another Family; a submitted Family identifier cannot redirect the operation. A missing or already-deleted Store also returns not found, as does deletion without a valid Current Family. In those cases the success redirect and flash do not run: refresh a stale Stores page, or select a valid Current Family before retrying. The current implementation hard-deletes the Store, and its Store–Section associations cascade with it. Ingredients and Store Placement do not exist, so there are no placement references to clear in this tracer.

After a successful deletion, the Stores page no longer lists the Store and the application flashes **Obchod byl smazán.**

## Store association and ordering

Each Store card shows its associated Store Sections in traversal order. Choose one of the Current Family's reusable Sections under **Přidat část obchodu** and submit **Přidat k obchodu** to append it to that Store. A Section can be associated with multiple Stores, once per Store, and can have a different position in each. Success flashes **Část obchodu byla přiřazena.**

Use the accessible **Posunout … nahoru** and **Posunout … dolů** controls to move a Section by one position. Every accepted reorder submits the complete associated Section sequence and the order version shown to the User. The backend locks the Store, verifies the exact unique membership, rewrites zero-based positions contiguously in one transaction, and increments the version. A stale version returns the field error **Pořadí částí obchodu se mezitím změnilo. Zkontrolujte nové pořadí a zkuste to znovu.** without changing the stored order. [ADR 0023](../adr/0023-rewrite-contiguous-store-section-positions.md) and [ADR 0028](../adr/0028-reject-stale-store-section-reorders.md) record this mechanism.

Use **Odebrat … z obchodu** to remove only that association. Remaining positions close contiguously, the reusable Store Section entity remains available to this and other Stores, and success flashes **Část obchodu byla odebrána.** Both the Store and Store Section route identifiers are resolved inside the authenticated User's Current Family through `CurrentFamilyScope`; a record from another Family is not found, and client-supplied Family input is never trusted.

> **Planned**
>
> Optional Store logos and Store Section icons remain blocked until their concrete media or catalogue prerequisites are approved. Store Section entity deletion and its ADR 0012 cleanup semantics remain a separate increment.

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
