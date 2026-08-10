# ADR 0030 — Derive metric display units

Metric input units and preferences are not persisted. Weight is stored in grams and volume in millilitres; presentation displays values below 1000 as `g` or `ml` and values of at least 1000 as `kg` or `l`, applying the approved two-fractional-digit formatting after conversion. Thus input `0.5 kg` is stored and displayed as `500 g`, while `1100 g` is stored as grams and displayed as `1.1 kg`. Piece counts remain unitless under the internal canonical kind `piece`; the Czech presentation label is `ks`.
