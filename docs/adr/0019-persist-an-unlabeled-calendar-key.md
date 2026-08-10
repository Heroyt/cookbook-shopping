# ADR 0019 — Persist an unlabeled Calendar key

Calendar Entries persist a non-null internal `unlabeled` key when the domain Meal Label is absent, alongside the five allowed label keys, and a composite unique constraint enforces one `(family, date, meal-label-key, recipe)` occurrence. Persistence adapters map `unlabeled` to and from the domain's absent Meal Label. This avoids the differing and unsuitable uniqueness behavior of nullable SQL values while preserving the optional domain concept consistently in SQLite and MariaDB.
