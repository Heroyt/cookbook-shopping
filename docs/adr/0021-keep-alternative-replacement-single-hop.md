# ADR 0021 — Keep Alternative replacement single-hop

Each originally generated Ingredient may be replaced at most once and only by one of its direct active Alternative Ingredients; an already substituted or merged output line cannot be substituted again. When several replacements resolve to the same final Ingredient, Shopping Generation retains separate original contribution and choice provenance so each choice can be changed or reverted independently. This preserves the explicitly non-transitive relationship instead of turning a sequence of direct edges into an implicit A-to-C substitution.
