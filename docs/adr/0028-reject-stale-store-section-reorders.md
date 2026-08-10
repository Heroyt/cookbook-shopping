# ADR 0028 — Reject stale Store Section reorders

Store Section ordering uses optimistic versioning in addition to the transactional association lock. A reorder command carries the version of the complete order the User saw; if another accepted reorder changed it, the stale command is rejected and returns the fresh order for review instead of silently overwriting it or attempting an ambiguous merge. This preserves equal-member collaboration with a simple conflict model.
