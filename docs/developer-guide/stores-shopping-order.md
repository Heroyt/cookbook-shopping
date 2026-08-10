# Stores and shopping order

The first Store tracer is implemented: authenticated members can list, create, and rename Stores in the Current Family from the responsive Stores page. Creation and rename squish repeated whitespace and enforce a 255-character limit. A PHP-generated lowercase `normalized_name`, stored as bytes and constrained by `(family_id, normalized_name)`, makes name uniqueness race-safe and independent of SQLite/MariaDB text collations; [ADR 0007](../adr/0007-use-application-normalized-keys-for-scoped-name-uniqueness.md) records the trade-off. Rename resolves the Store through `CurrentFamilyScope`, so a Store from another Family returns not found and a client-supplied Family identifier cannot redirect the write. Database unique-key collisions become field validation errors. Tests prove equal create/rename rights, cross-Family read/write isolation, normalization, duplicate handling, and that accent-distinct names remain distinct. The migration and focused Store suite also pass against an ephemeral MariaDB 11.8 database; this local check is not evidence about the external Komodo deployment.

Store deletion, logos, Store Sections, Store Placements, and Shopping List grouping are not implemented. Canonical terms are in [`CONTEXT.md`](../../CONTEXT.md). The ownership model follows [ADR 0003](../adr/0003-scope-domain-data-to-families.md), while [Shopping List generation](shopping-generation.md) defines the calculated lines that placement will organize.

## Stores and reusable Sections

> **Planned**
>
> Store deletion and optional logos remain to be added. A Store Section belongs to one Family and has a case-insensitively unique name, colour, and optional icon. Sections are reusable: several Stores may associate with the same Section entity.
>
> Each Store maintains its own ordered list of associated Store Sections. The traversal position belongs to the Store-to-Section association, so a shared Section can appear at different positions in different Stores.

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
> Deleting a Store clears both Store and Store Section placement on affected Ingredients. It does not delete the Ingredients or alter Recipe composition; they move to the globally unplaced group.
>
> Deleting a reusable Store Section entity from the Family, rather than removing it from one Store, still needs an explicit persistence policy before implementation. The result must preserve valid Ingredients and cannot leave dangling placements.

## Shopping List grouping and order

> **Planned**
>
> Group final Shopping List Lines first by Store and then by that Store's Section traversal order. A Store's unsectioned Ingredients appear after its configured Sections. Ingredients without a Store appear after all Store groups. Store group order has no domain significance.
>
> Sort Ingredient names alphabetically within a Section. Replacements and global re-aggregation happen before grouping, so each line appears under the Store Placement of the concrete Ingredient that will be purchased. Placement never changes Required Quantity or Purchase Quantity.

The conceptual associations and integrity checks are described in [Data structure](data-structure.md#placement-integrity). The delivery sequence is in Slice 2 of the [Implementation roadmap](implementation-roadmap.md#slice-2-stores-and-packaged-ingredients).
