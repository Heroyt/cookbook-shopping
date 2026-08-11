# Calendar planning

Persistent weekly Calendar Entries, derived Calendar Days, arbitrary Calendar Selection, and transient Simple Plans are implemented. Both planning modes adapt into the same persistence-independent service described in [ADR 0002](../adr/0002-keep-shopping-list-generation-persistence-independent.md) and [Shopping List generation](shopping-generation.md).

## Persistent Calendar Entries

Each Calendar Entry belongs to one Family and contains a live same-Family Recipe reference, concrete date, positive decimal Serving Count, and optional Meal Label. It has no recurrence rule or meaningful display position.

A Recipe may appear at most once for the same date and Meal Label. Persistence stores an absent Meal Label as the non-null internal `unlabeled` key, and the composite unique constraint permits one occurrence in both SQLite and MariaDB. Creating an existing combination atomically adds the submitted positive Serving Count to its existing count. Editing onto an existing combination adds the edited entry's submitted Serving Count to the target and removes the source. Every accepted request applies, including a repeated transport request, and returns Czech feedback with the resulting total. Overflow or another failure rolls back all affected rows. A disposable MariaDB concurrency test proves simultaneous duplicate creates converge on one exact total. See [ADR 0019](../adr/0019-persist-an-unlabeled-calendar-key.md), [ADR 0024](../adr/0024-accumulate-duplicate-calendar-serving-counts.md), and [ADR 0029](../adr/0029-apply-every-calendar-accumulation-request.md).

## Meal Labels and Calendar Days

The five fixed Meal Labels are `snídaně`, `dopolední svačina`, `oběd`, `odpolední svačina`, and `večeře`. They are grouping labels rather than capacity-limited slots or Recipe Tags. A date may contain any number of Recipes under each label, and order within a label has no meaning.

Calendar Day is a non-persisted Current-Family read model. It groups entries for one date, presents the five labels in conventional order and unlabeled entries last, and calculates exact Recipe and daily nutrition from live Recipe definitions. Removing the last Calendar Entry leaves no empty-day row.

## Weekly planner

The responsive Calendar page derives a visible week from the requested week anchor and keeps arbitrary selected dates separately. Members can create, edit, and delete Current-Family entries through generated Wayfinder actions and Czech shadcn-vue forms. A manually selected date outside the visible week remains a valid generation source.

Calendar Entries are live references. Recipe or Ingredient changes affect later Calendar display, nutrition, and generation. Archived Recipes remain visible in existing entries but are unavailable for new entries. An entry backed by an archived Recipe may change only Serving Count or be deleted; changing its date, Meal Label, or Recipe requires restoration first. [ADR 0027](../adr/0027-restrict-edits-for-archived-recipe-calendar-entries.md) records this boundary.

## Calendar Selection and generation state

Shopping generation accepts any unique canonical set of selected dates; dates need not be contiguous. Range selection is a convenience, and members may add or remove individual dates. The adapter gathers every Current-Family entry on the final dates, accumulates repeated Recipe selections, and builds the same generator input as Simple Plan.

The canonical date set, Alternative choices, and latest generated presentation are stored in Current-Family-namespaced session state. Reordering the same dates preserves valid Alternative choices. Changing the selection retains the new dates but clears choices and invalidates the prior result before regeneration. Every explicit attempt invalidates the previous generated presentation first, so a failed request cannot later display an old list with new provenance. Calculation Problems preserve the selected dates for correction and explicit retry.

## Simple Plan

A Simple Plan is a transient, unordered set with at most one Recipe Selection per Recipe. Adding the same active Current-Family Recipe increases its exact positive Serving Count and reports the resulting total. Removing a selection does not affect Recipe persistence, and the plan has no database table.

The session stores the plan and generated state under the Current Family. The adapter rejects archived or foreign Recipes, builds a refresh-safe generator request, presents every Calculation Problem with an exact correction link, and preserves the plan for explicit retry. Valid direct Alternative choices recalculate globally and can be reverted. A stale choice becomes a Czech field error or is safely reset during regeneration with accurate combined feedback.

Equivalent Calendar and Simple Plan inputs produce equal pure generator input and output. The Calendar adapter includes existing archived Recipes because their persisted entries remain valid; Simple Plan admits only active Recipes.

## Nutrition and history

The weekly planner shows per-Recipe and Calendar-day nutrition, including explicit incomplete-state warnings. Generated lists remain transient until a member chooses the save action. A Calendar snapshot freezes the selected dates and output; a Simple Plan snapshot freezes Recipe identities and requested Serving Counts. Snapshot behavior is documented in [Shopping List generation](shopping-generation.md#transience-and-saved-snapshots).
