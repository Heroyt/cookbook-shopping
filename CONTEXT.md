# Cookbook and Shopping Planning

This context describes a family's cookbook and the shopping plans generated from its recipes. Authentication protects family data when the application is hosted publicly.

## Commit convention

- Use the subject format `:emoji: [optional context] message`.
- Write the Gitmoji as its colon-wrapped code, not as a Unicode emoji. For example: `:bug: [shopping-list] fix package rounding`.
- Keep commits focused on one logical change. Split unrelated implementation, tests, documentation, and tooling changes into separate commits when that improves reviewability.

## User-facing language

The application interface is exclusively Czech. This includes visible copy, page metadata, navigation, forms, dialogs, validation and authentication feedback, toasts, loading and empty states, and accessible names that may be announced by assistive technology. Source-code identifiers and developer/operator documentation remain English, while backend and package messages are translated through `lang/cs` with Czech configured as both the application and fallback locale.

## Language

**User**:
A registered person who may participate in multiple families.
_Avoid_: Account, member

**Family**:
A shared household workspace that exclusively owns its recipes, recipe tags, ingredients, stores, store sections, calendar entries, and saved shopping lists. Any member may delete it after explicit destructive confirmation.
_Avoid_: Household, team, tenant

**Family Membership**:
The roleless association that allows a user to participate in a family. Members have equal rights to add or remove memberships and may leave freely, but a family must retain at least one member; users may belong to multiple families.
_Avoid_: Family user, collaborator

**Current Family**:
The one family whose data a user is presently viewing and modifying. Operations never combine recipes, plans, or shopping lists across families.
_Avoid_: Active account, selected tenant

**Agent Credential**:
A revocable, expiring secret issued by one user to an AI agent for exactly one family with an explicit set of permitted abilities. Every member of that family may inspect its non-secret metadata and revoke it; it also becomes invalid when its issuing user is deleted or leaves the family.
_Avoid_: Family credential, user-wide token, API key

**Agent Change Set**:
An immutable, validated set of requested creates, updates, archives, restorations, and deletions that an AI agent may apply atomically to one family's cookbook and meal-planning data after previewing its effects. It cannot manage users, families, family memberships, or agent credentials.
_Avoid_: Import batch, CRUD request

**Agent Change Set History**:
A read-only family-owned record containing an applied agent change set's canonical request, preview, acknowledgements, identifier mappings, final result, and provenance until a family member deletes it. AI agents cannot delete these records.
_Avoid_: Audit log, rollback record

**Cookbook**:
A family's collection of saved recipes.
_Avoid_: Recipe book, library

**Recipe**:
A saved set with a case-insensitively unique name across active and archived recipes in its family, a positive base serving count, and at least one recipe ingredient; it has no draft state. It may also contain ordered preparation steps, notes, a cover photo, source URL, preparation and cooking durations, tags, and a nutrition override.
_Avoid_: Meal, dish

**Recipe Step**:
One plain-text preparation instruction at a defined position in a recipe's ordered procedure. Preparation details are expressed in step text rather than extra recipe-ingredient fields.
_Avoid_: Instruction field, method

**Recipe Notes**:
Optional free-form information about a recipe that is not part of its ordered preparation procedure.
_Avoid_: Recipe step, description

**Recipe Tag**:
A family-defined label with a case-insensitively unique name that may classify many recipes, while each recipe may have multiple tags. Deleting it removes those assignments without changing the Recipes and releases its name for reuse.
_Avoid_: Recipe category, folder

**Archived Recipe**:
A recipe temporarily unavailable for new calendar entries or simple plans but retained for existing calendar entries and saved shopping-list snapshots; restoring it makes it available again.
_Avoid_: Deleted recipe, inactive meal

**Ingredient**:
A concrete purchasable package with a case-insensitively unique name across active and archived ingredients in its family. It defines either a positive canonical weight in grams or a positive canonical volume in millilitres, never both, and may additionally define a positive piece count; at least one quantity is required and every value describes the full contents of one package.
_Avoid_: Generic ingredient, product

**Store**:
An editable place where ingredients are bought, identified by a case-insensitively unique name and optional logo. A store maintains its own ordered list of store sections.
_Avoid_: Shop, retailer

**Store Section**:
A reusable shopping-area classification with a case-insensitively unique name, user-chosen colour, and optional icon, such as vegetables, fruit, or pasta. Different stores may include the same section at different positions in their traversal order.
_Avoid_: Category, aisle

**Store Placement**:
An ingredient's optional assignment to at most one store and one of that store's sections. Removing a section from one store clears it only from placements in that store; deleting the reusable section clears it from every affected placement; both retain Store assignment, while deleting a store clears the entire placement.
_Avoid_: Ingredient category, store availability

**Alternative Ingredient**:
Another active concrete ingredient connected through a symmetric, non-transitive relationship that the user may manually choose once for an originally generated shopping-list ingredient. It is selectable only when its package defines every canonical quantity kind used by the replaced recipe contributions; replacement uses already normalized grams, millilitres, or piece counts, adopts the alternative's store placement, recalculates and globally re-aggregates compatible lines, and does not modify source recipes.
_Avoid_: Automatic substitute, product variant

**Ingredient Conversion**:
A conversion derived from the metric quantity and optional piece count that describe one Ingredient package. Weight and volume are mutually exclusive; only a package's weight-to-piece or volume-to-piece equivalence can bridge its metric and count quantities.
_Avoid_: Global unit conversion, assumed density

**Measurement Unit**:
A user input unit for weight or volume. Canonical weight uses grams and canonical volume uses millilitres for calculation; display uses grams or millilitres below 1000 and kilograms or litres from 1000. Pieces are a universal unitless count called `piece` in the domain and `ks` in the Czech interface; other wording never creates a distinct unit identity.
_Avoid_: Free-form amount, cup, spoon

**Archived Ingredient**:
An ingredient temporarily unavailable for new recipe lines or Alternative choices but retained for existing recipes and saved shopping-list snapshots; restoring it makes it available again.
_Avoid_: Deleted ingredient, inactive product

**Recipe Ingredient**:
An ingredient and positive decimal culinary quantity at a defined position in a recipe's single ingredient list. Its quantity is expressed as canonical grams, millilitres, or piece count according to the Ingredient package; piece counts may be fractional, and generation combines repeated occurrences using the current package definition.
_Avoid_: Ingredient, shopping-list line

**Recipe Selection**:
A recipe paired with the number of servings the user intends to prepare. One or more recipe selections form the input for shopping-list generation.
_Avoid_: Food, food item

**Serving Count**:
A positive decimal number of servings produced by a recipe or requested in a recipe selection.
_Avoid_: Portion count, people count

**Shopping List**:
The transient combined ingredient requirements calculated from a set of recipe selections, assuming no pantry stock and no price data. Lines are grouped by store and section order, sorted deterministically by ingredient within each section, and not retained unless explicitly saved.
_Avoid_: Item list, grocery plan

**Calculation Problem**:
A recoverable issue that prevents Shopping Generation from producing any Shopping List, identifying the affected recipe, ingredient, quantity, unit, and reason so the source definition can be corrected before retrying.
_Avoid_: Partial shopping list, warning-only line

**Saved Shopping List**:
A read-only generation-history snapshot deliberately retained by the user and identified only by its generation timestamp. It preserves output and provenance without recalculation, but any family member may delete it because it is reference data rather than an audit record.
_Avoid_: Shopping plan, generated list

**Required Quantity**:
The exact combined amount of an ingredient needed by the selected recipes after serving adjustments.
_Avoid_: Purchase quantity

**Purchase Quantity**:
The whole package count obtained by rounding the combined package requirement upward, together with the package contents being purchased.
_Avoid_: Required quantity, ingredient amount

**Surplus**:
The difference between the total purchase quantity and the required quantity caused by buying whole purchasable units.
_Avoid_: Waste, remainder

**Shopping List Line**:
One aggregated ingredient requirement whose primary instruction is a whole package count. It exposes required, purchased, and surplus amounts in every configured unit plus a breakdown of contributing recipes.
_Avoid_: Recipe ingredient, checklist item

**Nutrition Profile**:
Energy in kilocalories and the amounts of fat, protein, and carbohydrates for a stated quantity and unit. Ingredient profiles may use bases such as 100 g, 100 ml, one piece, or one package.
_Avoid_: Nutritional values, macros

**Recipe Nutrition Override**:
An explicitly entered complete per-serving nutrition profile—kcal, fat, protein, and carbohydrates—that replaces the profile calculated from a recipe's ingredients. Calendar-day totals use the override while it is present.
_Avoid_: Nutrition adjustment, extra nutrition

**Incomplete Nutrition Profile**:
A sum of the nutrition values that can be calculated, visibly marked as incomplete and accompanied by the ingredients whose nutrition or conversions are missing.
_Avoid_: Estimated nutrition, zero nutrition

**Meal Label**:
One of the five fixed labels used to group recipe selections within a calendar day: snídaně, dopolední svačina, oběd, odpolední svačina, or večeře. A label does not limit how many recipes a day may contain.
_Avoid_: Recipe tag, meal slot, time slot

**Calendar Entry**:
A live reference to a recipe with a serving count, concrete date, and optional meal label. It does not recur, uses the recipe's current definition, and a recipe may appear at most once for the same date and label combination.
_Avoid_: Calendar slot, meal

**Calendar Day**:
A non-persisted grouping of calendar entries for one date. The five meal-label groups use their conventional order, unlabeled entries appear last, and order within each group has no meaning.
_Avoid_: Daily menu

**Calendar Selection**:
An arbitrary set of calendar dates chosen as the source for shopping-list generation. Range selection is a convenience, not a requirement that dates be contiguous.
_Avoid_: Shopping period, date range

**Simple Plan**:
A temporary, unordered set of unique recipe selections assembled solely to generate a shopping list. It is not retained after generation.
_Avoid_: Saved plan, ad hoc calendar
