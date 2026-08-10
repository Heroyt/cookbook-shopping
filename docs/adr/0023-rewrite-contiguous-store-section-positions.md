# Rewrite contiguous Store Section positions

Each Store–Section association carries a contiguous integer traversal position unique within its Store. Reordering submits the complete associated Section identifier sequence, locks that Store's associations, validates exact membership and uniqueness, and rewrites all positions in one transaction. Small household lists favor this simple invariant over sparse or fractional ranking schemes, while the same reusable Section may still hold a different position in every Store.
