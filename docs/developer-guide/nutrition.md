# Nutrition

Nutrition profiles and calculations are not implemented. The canonical concepts are defined in the final Domain Glossary chapter. Ingredient package quantities used for conversion are covered by [Recipes and Ingredients](recipes-ingredients.md), and persistence options are outlined in [Data structure](data-structure.md).

## Ingredient nutrition basis

> **Planned**
>
> An Ingredient may define one Nutrition Profile containing energy in kilocalories and fat, protein, and carbohydrates for an explicit positive basis quantity normalized as grams, millilitres, piece count, or one whole package. Valid Czech displays include per `100 g`, `100 ml`, `1 ks`, or one package. A number without its basis is not usable nutrition data.
>
> Input adapters normalize explicit metric units before persistence without retaining the input-unit preference. Display derives grams/kilograms or millilitres/litres using the same 1000 threshold as shopping quantities. Calculation uses the Ingredient's own package equivalence between its mutually exclusive metric quantity and optional piece count; it never treats grams and millilitres as interchangeable.

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

> **Planned**
>
> Cover same-dimension metric conversion, package/count equivalents, fractional Recipe and planned servings, complete overrides, missing profiles, missing conversions, repeated Ingredient lines, and propagation of incompleteness into Calendar-day totals. Keep these tests independent from Shopping List package-rounding tests.
