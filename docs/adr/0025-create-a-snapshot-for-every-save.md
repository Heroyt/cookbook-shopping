# ADR 0025 — Create a Saved Shopping List for every save

Every accepted save command creates a new timestamped Saved Shopping List, even when it repeats an earlier request or has identical content. The UI should lock the control while its current request is processing, but the domain does not use idempotency tokens or content deduplication; generation history records saves as distinct user actions. Timestamps therefore need sufficient precision and an internal identity or tie-breaker even though the timestamp remains the only user-facing identifier.
