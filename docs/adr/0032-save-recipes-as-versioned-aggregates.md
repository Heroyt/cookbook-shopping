# ADR 0032 — Save Recipes as versioned aggregates

A Recipe save submits its complete scalar state, ordered Recipe Ingredient list, and ordered Recipe Step list with the Recipe version the User saw. The application locks and validates the aggregate, persists contiguous child positions in one transaction, and rejects a stale version with fresh data instead of overwriting or attempting an automatic merge. This keeps the positive Serving Count, at-least-one-Ingredient, child validity, and ordering invariants atomic under equal-member editing.
