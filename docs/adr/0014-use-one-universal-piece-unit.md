# Use one universal piece unit

Count quantities use one universal piece count rather than Ingredient-owned custom units or a Family catalogue. Persistence and domain contracts store the positive count under the canonical kind `piece` without a selectable unit identity. Presentation localizes that kind: the Czech interface renders `ks`, and snapshots freeze both the internal kind and the rendered label. Optional descriptive words never participate in calculation or create unit identities. This keeps count explicit while avoiding custom-unit identity and lifecycle complexity.
