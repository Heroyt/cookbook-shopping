# Stores and shopping order

The Store, reusable Store Section, and ordered association tracers are implemented. Authenticated members can list, create, rename, and delete Stores; list, create, and delete reusable Store Sections; and maintain each Store's ordered Section traversal in the Current Family from the responsive Stores page. Store and Store Section names squish repeated whitespace and enforce a 255-character limit. A PHP-generated lowercase `normalized_name`, stored as bytes and constrained by `(family_id, normalized_name)` in each table, makes name uniqueness race-safe and independent of SQLite/MariaDB text collations; PHP also compares those stored UTF-8 bytes with stable identity as a tie-breaker for deterministic list output. [ADR 0007](../adr/0007-use-application-normalized-keys-for-scoped-name-uniqueness.md) records the key trade-off. Store, Store Section, and association commands resolve their records through `CurrentFamilyScope` and accept no Family identifier. Database unique-key collisions become field validation errors. Tests prove equal member rights, two-Family isolation, normalization, malformed-input rejection, duplicate handling, client input that cannot redirect ownership, entity-deletion cleanup, contiguous association positions, exact complete reorders, and stale-version rejection. The complete Cookbook feature and unit suite passes 79 tests and 680 assertions against a disposable MariaDB 11.8 database after a successful full migration. This local compatibility check is not evidence about the external Komodo deployment.

Store Placements are implemented for Ingredients. Store logos, optional Store Section icons, and Shopping List grouping are not implemented. Store Section colour remains presentation metadata only. Canonical terms are in the final Domain Glossary chapter.

## Create and list Store Sections

The User must be authenticated and belong to a Family. The server uses their validated Current Family preference, or selects the lowest-identifier remaining membership when that preference is absent or stale. On the Stores page, enter the required **Název části obchodu** (Store Section name), use the visible **Barva části obchodu** (Store Section colour) picker, and choose **Vytvořit část obchodu**. The submitted name is squished, required, and limited to 255 characters. The colour must use the `#RRGGBB` form with exactly six hexadecimal digits.

The backend derives Family ownership only from the authenticated User through `CurrentFamilyScope`; extra client input cannot select another Family. A normalized duplicate in the Current Family returns an inline name error, including when the database uniqueness constraint resolves a concurrent collision. The same normalized name remains available in another Family. After success, the page lists the persisted display name, a visual colour swatch, and the hexadecimal text so colour is not the only signal, then flashes **Část obchodu byla vytvořena.** Every Family member has the same create and list rights.

## Delete a Store Section

The User must be authenticated, belong to the selected Current Family, and choose a reusable Store Section in that Family. On the Stores page, choose **Smazat**, review the destructive AlertDialog, and choose **Smazat část obchodu**. The confirmation discloses the current Store-association and Ingredient-placement counts. Choosing **Zrušit** closes the dialog without deleting anything.

The request supplies only the Store Section identifier. The backend derives Family context exclusively from the authenticated User through `CurrentFamilyScope`; a submitted Family identifier cannot redirect the operation, and a Section from another Family is not found. In one transaction, the action locks the Section, locks its affected Stores in identifier order, removes every association, closes each affected Store's positions contiguously, increments each affected Store's `section_order_version`, and deletes the reusable Section. Unaffected Stores do not change. The Section's normalized name becomes immediately reusable. [ADR 0012](../adr/0012-delete-store-sections-with-placement-preserving-cleanup.md) records the full lifecycle.

After success, the Stores page no longer lists the Section and the application flashes **Část obchodu byla smazána.** Every affected Ingredient retains its Store and clears only its Section.

## Rename a Store

The User must be authenticated, belong to the selected Current Family, and already have a Store in that Family. On the Stores page, choose **Přejmenovat** (Rename) for the Store, edit its pre-filled name in the **Přejmenovat obchod** Dialog, and choose **Přejmenovat obchod**. **Zrušit** (Cancel) closes the Dialog without submitting.

The submitted name is required after repeated whitespace is squished and must contain at most 255 characters. A normalized duplicate of another Store name in the Current Family appears as an inline name error and leaves the existing Store unchanged. A Store identifier owned by another Family is not found; a submitted Family identifier cannot redirect the write.

After a successful rename, the Dialog closes, the Stores page shows the persisted display name, and the application flashes **Obchod byl přejmenován.**

## Delete a Store

The User must be authenticated, belong to the selected Current Family, and choose an existing Store in that Family. On the Stores page, choose **Smazat** (Delete), review the permanent-deletion warning in the **Smazat obchod** confirmation AlertDialog, and choose **Smazat obchod**. Choosing **Zrušit** (Cancel) closes the AlertDialog without deleting anything.

The request supplies only the Store identifier. The backend derives Family context from the authenticated User and returns not found for a Store in another Family; a submitted Family identifier cannot redirect the operation. Before hard-deleting the Store, the transactional action clears both placement fields from affected Ingredients. Store–Section associations then cascade with the Store; Ingredients remain intact.

After a successful deletion, the Stores page no longer lists the Store and the application flashes **Obchod byl smazán.**

## Store association and ordering

Each Store card shows its associated Store Sections in traversal order. Choose one of the Current Family's reusable Sections under **Přidat část obchodu** and submit **Přidat k obchodu** to append it to that Store. A Section can be associated with multiple Stores, once per Store, and can have a different position in each. Success flashes **Část obchodu byla přiřazena.**

Use the accessible **Posunout … nahoru** and **Posunout … dolů** controls to move a Section by one position. Every accepted reorder submits the complete associated Section sequence and the order version shown to the User. The backend locks the Store, verifies the exact unique membership, rewrites zero-based positions contiguously in one transaction, and increments the version. A stale version returns the field error **Pořadí částí obchodu se mezitím změnilo. Zkontrolujte nové pořadí a zkuste to znovu.** without changing the stored order. [ADR 0023](../adr/0023-rewrite-contiguous-store-section-positions.md) and [ADR 0028](../adr/0028-reject-stale-store-section-reorders.md) record this mechanism.

Use **Odebrat … z obchodu** to remove only that association. Remaining positions close contiguously, the reusable Store Section entity remains available to this and other Stores, and success flashes **Část obchodu byla odebrána.** Both the Store and Store Section route identifiers are resolved inside the authenticated User's Current Family through `CurrentFamilyScope`; a record from another Family is not found, and client-supplied Family input is never trusted.

> **Planned**
>
> Optional Store logos and Store Section icons remain blocked until their concrete media or catalogue prerequisites are approved.

## Ingredient Store Placement

The Ingredient create/edit form offers an optional Current-Family Store and then only that Store's associated Sections. Store-only placement is valid. Request resolution rejects a Section without Store, an unassociated pair, and either record from another Family. A database check and composite foreign key independently enforce the same shape. Placement is guidance for future grouping and walking order, not availability, inventory, or price data.

## Removal behavior

Removing a Section association clears that Section only from Ingredient placements in the selected Store and retains their Store. Deleting the reusable Section clears it from every affected placement while retaining each Store, then removes associations, closes positions, advances order versions, and releases the name. Deleting a Store clears both fields. All three operations are transactional and preserve Ingredients. [ADR 0012](../adr/0012-delete-store-sections-with-placement-preserving-cleanup.md) and [ADR 0015](../adr/0015-enforce-store-placement-through-the-store-section-association.md) record these invariants.

## Shopping List grouping and order

> **Planned**
>
> Group final Shopping List Lines first by Store and then by that Store's Section traversal order. Sort Store groups deterministically by the application-normalized Store name with stable identity as a tie-breaker, although that order has no domain significance. A Store's unsectioned Ingredients appear after its configured Sections, and Ingredients without a Store appear after all Store groups.
>
> Sort Ingredient names within a Section by the same application-normalized name semantics used for scoped uniqueness, comparing the stored normalized UTF-8 bytes and then stable Ingredient identity. This makes Czech and accent-distinct names deterministic across database collations and input order without claiming locale-aware dictionary collation. Replacements and global re-aggregation happen before grouping, so each line appears under the Store Placement of the concrete Ingredient that will be purchased. Placement never changes Required Quantity or Purchase Quantity.

The conceptual associations and integrity checks are described in [Data structure](data-structure.md#placement-integrity). The delivery sequence is in Slice 2 of the [Implementation roadmap](implementation-roadmap.md#slice-2-stores-and-packaged-ingredients).
