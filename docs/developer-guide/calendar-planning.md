# Calendar planning

Calendar planning and Simple Plans are not implemented. [`CONTEXT.md`](../../CONTEXT.md) defines Calendar Entry, Calendar Day, Calendar Selection, Meal Label, Recipe Selection, Serving Count, and Simple Plan. Both planning modes adapt into the persistence-independent service described in [ADR 0002](../adr/0002-keep-shopping-list-generation-persistence-independent.md) and [Shopping List generation](shopping-generation.md).

## Persistent Calendar Entries

> **Planned**
>
> Persist Calendar Entries only. Each entry belongs to one Family and contains a live Recipe reference from that Family, a concrete date, a positive decimal Serving Count, and an optional Meal Label. It has no recurrence rule and no meaningful display position.
>
> A Recipe may appear at most once for the same date and Meal Label. Persist an absent Meal Label as the non-null internal `unlabeled` key so the same composite unique constraint permits only one unlabeled occurrence of that Recipe on the date in both SQLite and MariaDB. Creating an existing combination atomically adds the submitted positive Serving Count to its existing count and leaves one row; editing onto an existing combination adds the edited entry's submitted Serving Count to the target and removes the source. Concurrent unique-key collisions must converge on the same update. Every accepted command applies, including a repeated transport request, and returns an explicit UI notice showing the new total. Domain and UI adapters continue to expose Meal Label as optional. See [ADR 0019](../adr/0019-persist-an-unlabeled-calendar-key.md), [ADR 0024](../adr/0024-accumulate-duplicate-calendar-serving-counts.md), [ADR 0029](../adr/0029-apply-every-calendar-accumulation-request.md), and [Data structure](data-structure.md#recipe-and-calendar-integrity).

## Meal Labels and Calendar Days

> **Planned**
>
> The five fixed Meal Labels are `snídaně`, `dopolední svačina`, `oběd`, `odpolední svačina`, and `večeře`. They are predefined grouping labels, not capacity-limited slots and not Recipe Tags. A date may contain any number of Recipes under each label. Order within a label has no meaning.
>
> Calendar Day is a non-persisted read model grouping entries for one date. It presents the five labels in conventional order and an Unlabeled group last. Removing the last Calendar Entry for a date leaves no empty-day record.

## Weekly planner

> **Planned**
>
> The primary Calendar interface is a responsive weekly planner. Desktop may present the week spatially, while mobile uses a layout that remains practical without compressing seven columns. Both expose the same date, Meal Label, Recipe, and fractional Serving Count behavior.
>
> Calendar Entries are live references. Editing a Recipe or its Ingredients changes later Calendar display, nutrition, and Shopping List generation. Archived Recipes remain valid in existing entries but cannot be selected for new ones. An entry backed by an archived Recipe may change only Serving Count or be deleted; changing its date, Meal Label, or Recipe requires restoration first. [ADR 0027](../adr/0027-restrict-edits-for-archived-recipe-calendar-entries.md) records this boundary.

## Calendar Selection

> **Planned**
>
> Shopping generation accepts any set of selected Calendar dates; dates need not be contiguous. Range selection is a UI convenience, and Users may remove individual dates before generation. The Calendar adapter gathers every entry on the final selected dates and maps each Recipe plus Serving Count into a Recipe Selection.
>
> Meal Labels and within-day grouping do not alter aggregation. All entries on selected dates contribute, including unlabeled entries.

## Simple Plan

> **Planned**
>
> A Simple Plan is a transient, unordered set containing at most one Recipe Selection per Recipe. Adding the same Recipe again increases the existing row by the submitted positive decimal Serving Count and shows the resulting total rather than replacing, rejecting, or creating a duplicate row. It is not saved after generation. [ADR 0031](../adr/0031-accumulate-duplicate-simple-plan-selections.md) records the interaction.
>
> The Simple Plan adapter produces the same generator input as the Calendar adapter. It cannot include archived Recipes when creating new selections. Equivalent Calendar and Simple Plan inputs must produce equivalent Shopping List output.

## Nutrition and history

> **Planned**
>
> The weekly planner shows Recipe and Calendar-day nutrition, including clear incomplete-state warnings, according to [Nutrition](nutrition.md). Generating a Shopping List is transient; explicitly saving its result records provenance for the selected dates without persisting a Calendar Day or Simple Plan. Snapshot behavior is documented in [Shopping List generation](shopping-generation.md#transience-and-saved-snapshots).
