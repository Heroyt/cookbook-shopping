# ADR 0039 — Keep entity saves independent from media uploads

Cookbook entity creation and editing commit independently from their optional media upload. The interface may sequence an entity save followed by a photo or logo upload, but a media failure does not roll back or hide a successfully saved entity and instead remains retryable through the existing private media workflow. This avoids pretending that database persistence and filesystem or future object storage share one transaction, at the cost of allowing a valid entity to exist temporarily without its selected image.
