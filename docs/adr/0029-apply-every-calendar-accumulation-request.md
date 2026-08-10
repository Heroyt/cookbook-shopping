# ADR 0029 — Apply every Calendar accumulation request

Every accepted duplicate Calendar create or collision-producing edit adds its submitted Serving Count, including a repeated transport request. The UI disables the action only while its current request is processing and explicitly reports the resulting total, but the domain introduces no idempotency token. This accepts the possibility of a repeated increment in favor of a simpler command boundary consistent with treating every accepted request as intentional.
