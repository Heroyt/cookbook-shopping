# ADR 0022 — Archive and restore Recipes and Ingredients

Recipes and Ingredients have reversible archival and restoration in the MVP, with no individual hard-delete operation. Archived entities keep their names, media, relationships, and existing live references but are excluded from new selections according to their capability rules; restoration makes them eligible again. Only explicit Family deletion ultimately removes them and their entity-owned files, avoiding reference-dependent deletion behavior while keeping accidental archival recoverable.
