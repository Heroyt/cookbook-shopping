# Implementation Roadmap

This roadmap orders the approved MVP by dependency and risk. It is an implementation plan, not a statement of available behavior.

> **Planned**
> Each slice should deliver migrations, domain and application code, authorization, Inertia UI, factories, and focused PHPUnit/Vitest coverage together. Do not build all tables first and postpone the behavior that proves them.

## Slice 0: delivery baseline

> **Planned**
>
> Before adding Family data, make the existing delivery path safe to extend:
>
> - Verify local native and Docker setup from a clean checkout.
> - Decide the production database and durable photo-storage backend.
> - Represent the production ingress, database, volumes/object storage, queue worker, scheduler, and backup jobs in deployable configuration.
> - Stop swallowing migration failures in the production entry point.
> - Move runtime secrets out of image-build inputs.
> - Define readiness checks, backup restoration, and rollback procedures.
>
> **Completion gate:** a production-like deployment can boot, migrate, serve `/up`, run a queue job, persist a test file, back up its database and media, and restore both into an isolated environment.

## Slice 1: Family access

> **Planned**
>
> - Create Family and roleless Family Membership persistence.
> - Decide how Users are provisioned while self-registration is disabled; add-by-email can attach only an already registered User.
> - Let a User create a Family, add a registered User by email, leave, remove another member, switch Current Family, and delete a Family with explicit confirmation.
> - Reconcile account deletion with the no-orphan invariant by blocking deletion or requiring explicit resolution of every Family in which that User is the final member.
> - Persist the last valid Current Family selection without making it an ownership field.
> - Establish reusable Family-scoped authorization and cross-Family test helpers.
>
> **Completion gate:** tests with two Users and two Families demonstrate equal membership rights and prove complete isolation of Family-owned records.

## Slice 2: Stores and packaged Ingredients

> **Planned**
>
> - Add case-insensitively unique Store and reusable Store Section entities within a Family.
> - Maintain each Store's ordered section associations.
> - Add Ingredients as concrete purchasable packages with at least one positive unit quantity, optional Store Placement, media, description, nutrition, and direct symmetric/non-transitive alternatives.
> - Support metric weight and volume units plus Ingredient-specific count units.
> - Archive Ingredients and guard removal of units referenced by Recipe Ingredients.
>
> **Completion gate:** a Family can describe a package such as `150 g = 6 pieces`, place it in a Store Section, reorder Store sections, archive it, and prove that another Family cannot observe it.

## Slice 3: Recipes and nutrition

> **Planned**
>
> - Add uniquely named Recipes with base Serving Count, one ordered ingredient list, optional ordered Recipe Steps and approved metadata.
> - Permit repeated Ingredient lines and fractional count amounts.
> - Scale quantities by `requested servings / base servings` using decimal-safe value objects.
> - Calculate complete or explicitly incomplete per-serving nutrition, with complete Recipe Nutrition Overrides taking precedence.
> - Add Recipe Tags, search result layers, and Recipe archiving.
>
> **Completion gate:** automated tests cover mixed units, repeated lines, fractional servings, missing nutrition, overrides, archive behavior, and Family isolation.

## Slice 4: pure Shopping Generation

> **Planned**
>
> - Implement the persistence-independent generator from Recipe Selections to Shopping List Lines.
> - Aggregate by final Ingredient before ceiling package counts.
> - Express required, purchased, and Surplus quantities in every configured unit.
> - Retain contribution breakdowns by source Recipe.
> - Apply manual Alternative Ingredient choices, regroup by the final Store Placement, and globally re-aggregate.
> - Order output by Store, Store Section traversal order, and Ingredient name, with unassigned groups last.
>
> **Completion gate:** domain tests exercise the examples and invariants in [Shopping-list generation](shopping-generation.md) without HTTP, database, calendar, or Inertia dependencies.

## Slice 5: Simple Plan

> **Planned**
>
> - Build an unordered, temporary set with one Recipe Selection per Recipe.
> - Generate the list through the same service used by Calendar planning.
> - Present responsive desktop and mobile output suitable for copying into another checklist tool.
>
> **Completion gate:** a browser-level test proves that Recipe selection and fractional servings produce the expected package counts without persisting the Simple Plan.

## Slice 6: weekly Calendar

> **Planned**
>
> - Persist Calendar Entries only; derive Calendar Days at read time.
> - Support the five fixed Czech Meal Labels plus unlabeled entries.
> - Prevent duplicate `(Family, date, Meal Label, Recipe)` combinations, including the unlabeled case.
> - Provide a responsive weekly planner and arbitrary multi-date Calendar Selection with range-selection convenience.
> - Show calculated Recipe and daily nutrition, including incomplete-state warnings.
>
> **Completion gate:** selecting non-contiguous dates produces the same generator input and output as an equivalent Simple Plan.

## Slice 7: generation history

> **Planned**
>
> - Let a member explicitly save a read-only Shopping List snapshot identified by generation timestamp.
> - Copy display data, calculated output, applied alternatives, and immutable source provenance into the snapshot.
> - Permit any Family member to delete history entries.
>
> **Completion gate:** later edits or archival of Recipes and Ingredients, and edits or deletion of Stores and Sections, cannot change the rendered snapshot.

## Deferred capabilities

> **Planned**
>
> Do not fold pantry inventory, pricing, allergens, recurring calendar rules, cross-Family copying, checklist state, or external checklist integration into these slices. Each requires a separate domain review before implementation.
