# Nutrition

Ingredient Nutrition Profile persistence and editing are implemented. Recipe calculation, overrides, incomplete profiles, and Calendar totals remain planned. The canonical concepts are defined in the final Domain Glossary chapter.

## Ingredient nutrition basis

An active Ingredient may define one complete Nutrition Profile containing energy in kilocalories and fat, protein, and carbohydrates for an explicit positive basis in canonical grams, millilitres, piece count, or exactly one whole package. All six values are supplied together or the profile is absent. Database checks restrict the basis kind, require positive basis quantity, require package basis quantity one, and reject negative energy or macros.

The chosen grams, millilitres, or piece basis must exist on the Ingredient package. Editing cannot remove the depended-on package kind while retaining that profile. Clearing the profile removes the dependency. Recipe scaling and package/count conversion are not implemented yet.

## Recipe calculation

> **Planned**
>
> Calculate the Recipe's base-batch nutrition by scaling each usable Ingredient Nutrition Profile to its Recipe Ingredient quantity and summing kcal and all macros. Divide the batch result by the positive base Serving Count to produce the Recipe Nutrition Profile per serving. Recipe views may derive the full batch total from the same values, but per serving is the canonical Recipe result used by planning.
>
> Scaling a Recipe Selection multiplies that per-serving profile by its requested decimal Serving Count. Nutrition calculation is separate from Shopping List package generation; missing nutrition never blocks ingredient aggregation or purchase counts.

## Recipe Nutrition Override

> **Planned**
>
> A Recipe may provide a complete manual per-serving override containing kcal, fat, protein, and carbohydrates together. While present, this profile replaces the entire calculated per-serving result. Partial overrides are invalid because mixing manual and calculated fields could present an internally inconsistent profile.

## Incomplete profiles

> **Planned**
>
> If any Recipe Ingredient lacks a Nutrition Profile or a usable conversion to that profile's basis, preserve the sum of known values but mark the Recipe result as an Incomplete Nutrition Profile. Identify the missing Ingredients and never present the partial sum as exact or silently substitute zero.
>
> A complete Recipe Nutrition Override makes the Recipe result complete without requiring Ingredient-level data. Removing the override exposes the current calculated status again.

## Calendar totals

> **Planned**
>
> Calendar Day nutrition is derived from its Calendar Entries rather than persisted as a daily record. For each entry, multiply the Recipe's current per-serving profile or override by its planned Serving Count, then sum the entries. If any contribution is incomplete, retain known totals, mark the day incomplete, and surface the missing Ingredient details.
>
> Meal Labels affect display grouping only; labeled and unlabeled Calendar Entries all contribute to the same daily nutrition total. See [Calendar planning](calendar-planning.md) for day construction and live Recipe references.

## Verification focus

Current PHPUnit coverage proves create/list/remove, all-or-none validation, basis/package compatibility, quantity-kind removal protection, and database checks independently from Shopping Generation. Future Recipe and Calendar tests must cover scaling, overrides, missing conversions, repeated lines, and propagation of incompleteness without coupling them to package-rounding tests.
