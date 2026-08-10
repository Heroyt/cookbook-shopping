# Accumulate duplicate Simple Plan selections

A Simple Plan contains at most one row per Recipe. Adding a Recipe already present atomically increases that transient row by the submitted positive Serving Count and shows the resulting total instead of replacing or rejecting it. The plan remains unordered and unpersisted; this mirrors the additive Calendar interaction without introducing durable Simple Plan records.
