# Nutrition

Ingredient Nutrition Profile persistence, exact Recipe calculation, complete Recipe overrides, explicit incomplete profiles, and Calendar-day totals are implemented. Nutrition remains separate from package generation: missing nutrition never blocks Shopping Generation or becomes zero.

## Ingredient nutrition basis

An active Ingredient may define one complete Nutrition Profile containing energy in kilocalories and fat, protein, and carbohydrates for an explicit positive basis in canonical grams, millilitres, piece count, or exactly one whole package. All six values are supplied together or the profile is absent. Database checks restrict the basis kind, require positive basis quantity, require package basis quantity one, and reject negative energy or macros.

The chosen grams, millilitres, or piece basis must exist on the Ingredient package. Editing cannot remove the depended-on package kind while retaining that profile. Clearing the profile removes that dependency, subject to any Recipe Ingredient that still uses the package kind.

## Recipe calculation

`RecipeNutritionCalculator` scales every usable Ingredient Nutrition Profile to its Recipe Ingredient quantity using exact rational arithmetic, sums kcal and all macros for the base batch, and divides by the positive base Serving Count. The Recipe projection exposes the canonical per-serving result.

Profiles based on package, grams, millilitres, or pieces use only the package equivalences explicitly stored on that Ingredient. No density or cross-Ingredient conversion is inferred. Repeated Recipe Ingredient lines contribute independently and are summed.

## Recipe Nutrition Override

A Recipe may provide a complete manual per-serving override containing kcal, fat, protein, and carbohydrates together. While present, this profile replaces the calculated per-serving result. Partial overrides are rejected at validation and persistence boundaries because mixing manual and calculated fields would present an inconsistent profile.

## Incomplete profiles

If any Recipe Ingredient lacks a Nutrition Profile or a usable conversion to its profile basis, the calculator preserves the sum of known values, marks the result incomplete, and identifies every missing Ingredient. It never presents the partial sum as exact or silently substitutes zero. A complete Recipe Nutrition Override makes the Recipe result complete without requiring Ingredient-level data; removing the override exposes the current calculated state again.

## Calendar totals

Calendar Day nutrition is derived from live Calendar Entries. Each entry multiplies the Recipe's current per-serving calculated profile or override by its planned Serving Count, then the day projection sums all entries. When any contribution is incomplete, the projection retains known totals, marks the day incomplete, and surfaces the missing Ingredient names.

Meal Labels affect display grouping only; labeled and unlabeled Calendar Entries all contribute to the same daily total. Simple Plan and Shopping List output do not use nutrition in their package arithmetic.

## Verification focus

Focused PHPUnit coverage proves Ingredient create/list/remove, all-or-none validation, basis/package compatibility, quantity-kind dependency protection, exact scaling across supported bases, repeated lines, complete override precedence, preservation of known totals in incomplete results, and Calendar propagation. Generator tests separately prove that missing nutrition has no effect on package calculation.
