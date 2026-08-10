# ADR 0017 — Return an all-or-nothing typed generation result

The pure Shopping Generation boundary returns either one complete Shopping List or a typed collection of structured Calculation Problems that identify the affected Recipe, Ingredient, quantity, unit, and reason. It collects recoverable input problems rather than failing on the first one, never returns a partial Shopping List, and reserves exceptions for programming errors or violated internal invariants. This makes correction actionable without allowing omitted contributions to look like a valid purchase plan.
