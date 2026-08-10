# ADR 0033 — Delete Recipe Tags with assignment cleanup

Recipe Tags are hard-deleted rather than archived. After consequence confirmation, deleting a Tag detaches all Recipe assignments transactionally, leaves every Recipe otherwise unchanged, and releases the Tag's normalized Family-scoped name for reuse. Tags are classification metadata rather than historical entities, and immutable Saved Shopping Lists do not rely on live Tag assignments.
