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
A family-defined label with a case-insensitively unique name that may classify many recipes, while each recipe may have multiple tags.
_Avoid_: Recipe category, folder

**Archived Recipe**:
A recipe no longer available for new calendar entries or simple plans but retained for existing calendar entries and saved shopping-list snapshots.
_Avoid_: Deleted recipe, inactive meal

**Ingredient**:
A concrete purchasable package with a case-insensitively unique name across active and archived ingredients in its family. It must define at least one positive unit quantity; each configured quantity, such as 150 g and 6 pieces, describes the full contents of one package.
_Avoid_: Generic ingredient, product

**Store**:
An editable place where ingredients are bought, identified by a case-insensitively unique name and optional logo. A store maintains its own ordered list of store sections.
_Avoid_: Shop, retailer

**Store Section**:
A reusable shopping-area classification with a case-insensitively unique name, colour, and optional icon, such as vegetables, fruit, or pasta. Different stores may include the same section at different positions in their traversal order.
_Avoid_: Category, aisle

**Store Placement**:
An ingredient's optional assignment to at most one store and one of that store's sections. Removing a section clears that section from affected ingredients, while deleting a store clears their entire placement; unplaced ingredients remain valid and appear after placed ingredients.
_Avoid_: Ingredient category, store availability

**Alternative Ingredient**:
Another concrete ingredient connected through a symmetric, non-transitive relationship that the user may manually choose for a shopping-list line. Replacement uses the alternative's store placement, recalculates and globally re-aggregates compatible lines, otherwise requires a manual quantity, and does not modify source recipes.
_Avoid_: Automatic substitute, product variant

**Ingredient Conversion**:
A conversion derived from the equivalent quantities that describe one ingredient package. Weight and volume remain distinct unless that ingredient explicitly describes its package in both units.
_Avoid_: Global unit conversion, assumed density

**Measurement Unit**:
A supported metric weight unit (mg, g, kg), metric volume unit (ml, cl, l), or an ingredient-specific count unit such as piece or slice. Metric units convert within their dimension; count-unit conversion depends on the ingredient package.
_Avoid_: Free-form amount, cup, spoon

**Archived Ingredient**:
An ingredient no longer available for new recipe lines but retained for existing recipes and saved shopping-list snapshots.
_Avoid_: Deleted ingredient, inactive product

**Recipe Ingredient**:
An ingredient and positive decimal culinary quantity at a defined position in a recipe's single ingredient list. Count units may be fractional; generation combines repeated occurrences using the current package definition.
_Avoid_: Ingredient, shopping-list line

**Recipe Selection**:
A recipe paired with the number of servings the user intends to prepare. One or more recipe selections form the input for shopping-list generation.
_Avoid_: Food, food item

**Serving Count**:
A positive decimal number of servings produced by a recipe or requested in a recipe selection.
_Avoid_: Portion count, people count

**Shopping List**:
The transient combined ingredient requirements calculated from a set of recipe selections, assuming no pantry stock and no price data. Lines are grouped by store and section order, sorted alphabetically by ingredient within each section, and not retained unless explicitly saved.
_Avoid_: Item list, grocery plan

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
