# Documentation Specification

## Status

- Mode: Create
- Approval: Approved on 2026-08-09 after Family-scope revision; versions 0.1.1, 0.1.2, 0.2.0, and 0.3.0 approved on 2026-08-10
- Primary languages: Czech for the user guide; English for the developer/operator guide
- Documentation version: 0.3.0

## Application and evidence

### Product boundary

The product boundary is this family-scoped cookbook and shopping-planning web application. A User may participate in multiple Families and works within one Current Family at a time. Each Family exclusively owns its Cookbook, Ingredients, Stores, Store Placements, Calendar Entries, and Saved Shopping Lists. The currently implemented boundary contains authentication, password recovery, passkey management, profile and security settings, appearance settings, a placeholder authenticated dashboard, operator-only User provisioning, complete Family Access collaboration workflows, reusable Current Family record scoping, and a narrow Store create/list/rename tracer. Store deletion and the remaining cookbook, recipe, ingredient, section, meal-calendar, nutrition, and shopping-list workflows remain intended functionality that is not yet implemented.

### Source authority

Use evidence in this order:

1. Application code, tests, migrations, configuration, and observed user interface for implemented behavior.
2. The approved documentation specification and exact user attestations for intent.
3. Official external documentation for framework, browser, passkey, and other third-party contracts.
4. Existing project documentation as supporting evidence that must still be verified.

Do not use the currently connected Laravel Boost database as evidence because it belongs to an unrelated application.

### Evidence gaps and attestations

- Laravel Boost is connected to an unrelated livestock-management database. Repository migrations and code remain authoritative until that connection is corrected.
- On 2026-08-10 the User selected operator-only Artisan provisioning with public registration disabled, and persisted nullable Current Family selection validated against membership.
- On 2026-08-10 the User selected application-generated normalized name keys with database-backed Family-scoped uniqueness and approved documentation version 0.3.0.

### Intended-versus-implemented mismatches

Family persistence, operator User provisioning, Family collaboration, reusable Current Family scoping, and Store creation/listing/renaming are implemented. Store deletion and the remaining Recipe, Ingredient, Store Section, meal-calendar, nutrition, and Shopping List domain described in `CONTEXT.md` are not implemented in the current application.

- Omit unimplemented behavior from the user guide.
- The developer/operator guide may describe intended design only in visually distinct **Planned** callouts, separated from current setup and operational instructions.

## Audiences

### User guide

Nontechnical Users who participate in one or more Families and manage the Current Family's Cookbook and shopping plans. Readers are assumed to know how to use a modern web browser but are not expected to understand the application's implementation. The guide helps them access the application, manage personal and security settings, select and collaborate within a Family, and complete every implemented cookbook and shopping-planning workflow.

The user guide excludes developer setup, architecture, deployment, internal domain design, and unimplemented workflows.

Write the user guide in Czech and preserve exact interface labels when they differ from the surrounding Czech prose.

### Developer/operator guide

Human developers, operators, and AI coding agents that develop, deploy, operate, troubleshoot, or reason about the application. Readers may need both implementation guidance and stable domain context.

The guide serves as the main navigable developer reference for:

- Architecture, system boundaries, local development, configuration, testing, deployment, operations, and troubleshooting.
- Domain concepts, relationships, invariants, and workflows, grounded in the canonical glossary in `CONTEXT.md` and substantive decisions in `docs/adr/`.
- Clearly marked **Planned** domain design that must not be mistaken for implemented behavior.

`CONTEXT.md` remains the implementation-free canonical glossary rather than being duplicated or replaced by the guide.

Write the developer/operator guide in English and use the canonical terms from `CONTEXT.md` primarily. Preserve Czech domain language where `CONTEXT.md` defines it, including the five Meal Label values: snídaně, dopolední svačina, oběd, odpolední svačina, and večeře. Link to the canonical glossary rather than maintaining a separate competing vocabulary.

## Scope

### Included

User guide:

- Login and logout.
- Password recovery and reset.
- Passkey registration, authentication, and management where supported by the implemented interface.
- Profile updates and profile deletion.
- Password and security settings.
- Appearance settings.
- Family creation only after its user-facing guide is separately refreshed and verified; Family selection and membership management only after those workflows are implemented and verified.
- Stores, Store Sections, and Store Placements only after they are implemented and verified.
- Cookbook and Shopping List workflows only after they are implemented and verified.

Developer/operator guide:

- Current application architecture, development, testing, operation, and troubleshooting.
- The canonical domain model and its supporting ADRs.
- Approved planned domain design when clearly marked **Planned**.

### Excluded

- Unimplemented behavior in the user guide.
- The placeholder dashboard as a user workflow.
- Claims derived from the unrelated Laravel Boost database.
- Production screenshots or sensitive personal data unless separately approved.

## Guide structures

### User guide

Use a multi-page guide rooted at `docs/user-guide/index.md` with focused chapters for:

- Účel, rozsah a přístup
- Přihlášení a obnovení přístupu
- Profil a zabezpečení
- Přístupové klíče
- Vzhled aplikace
- Rodiny, členství a výběr aktuální rodiny
- Obchody, části obchodů a umístění ingrediencí
- Implementované pracovní postupy Kuchařky a Nákupního seznamu
- Řešení problémů, zabezpečení dat a pojmy

Keep `docs/user-guide/SCREENSHOTS.md` as a planning and approval record outside the rendered source list.

### Developer/operator guide

Use a multi-page guide rooted at `docs/developer-guide/index.md` with focused chapters for:

- Current and planned product boundaries
- Domain model and ADR map
- Family ownership and access: Family Membership, Current Family selection, isolation, and authorization
- Recipes and Ingredients: package quantities, conversions, alternatives, Recipe Ingredients, and Serving Counts
- Stores and shopping order: Stores, Store Sections, Store Placements, and unassigned-item ordering
- Nutrition: Ingredient profiles, calculated Recipe nutrition, overrides, and totals
- Calendar planning: Calendar Entries, Calendar Days, Meal Labels, and Simple Plans
- Shopping List generation: Recipe Selections, quantity aggregation, purchasable units, Surplus, and saved snapshots
- Architecture and system boundaries
- Local development and configuration
- Authentication, authorization, and security
- Frontend architecture and navigation
- Persistence and data flows
- Testing and quality gates
- Deployment, operations, recovery, and scaling
- Troubleshooting and command reference

Keep the documentation specification, documentation decisions, ADRs, and other planning or review records outside the rendered source list. Link to those sources where they provide useful evidence.

Every domain chapter must explain relationships, invariants, and workflows; link to `CONTEXT.md` for canonical definitions; link to relevant ADRs; and identify each capability as implemented or **Planned**.

## Screenshots and diagrams

Use screenshots selectively in the Czech User guide only when an image materially clarifies an implemented workflow.

- Capture only from a local environment using synthetic fixture data.
- Never capture production unless a later decision grants explicit approval.
- Store raw captures only under ignored `docs/.documentation-work/raw-screenshots/`.
- Record every proposed and published image in `docs/user-guide/SCREENSHOTS.md`, including exact state, viewport, dimensions, visible content, sensitivity review, redaction decision, approval, and descriptive alt text.
- Require human inspection and explicit approval for every image before promotion into the published guide.
- Never publish reusable credentials, tokens, secrets, or other live sensitive data.
- Prefer solid redaction for high-risk identifiers. Use mosaic only after explaining that it may reveal approximate length and visual density.
- Provide complete prose instructions so every workflow remains understandable without its screenshot.

Use Mermaid diagrams selectively in the developer/operator guide when they materially clarify architecture, domain relationships, or multi-step data flows.

- Precede every Mermaid fence with a concise `<!-- diagram-alt: ... -->` text alternative.
- Keep all essential relationships and behavior in the surrounding prose.
- Do not use diagrams when a short paragraph or ordinary table communicates the same information more clearly.
- Keep diagrams out of the Czech User guide unless a later implemented workflow genuinely requires one.

## Publication contract

- Commit the portable Markdown sources and their approved publication images.
- Generate a separate Czech User-guide PDF and English developer/operator-guide PDF under ignored `docs/pdf/`.
- Use the neutral `default` PDF theme rather than eSoul client branding.
- Do not commit generated PDFs unless a later project decision explicitly changes the publication policy.
- Treat the Markdown sources as authoritative when generated artifacts disagree.
- Require specification approval, traceable decisions, Markdown and asset validation, successful Dockerized PDF builds, structural PDF checks, every-page visual inspection, privacy and source-level accessibility review, and correctness and completeness reviews before calling either guide publish-ready.

Version documentation independently using semantic versioning, beginning at `0.1.0`. During refreshes, propose patch versions for corrections, minor versions for new workflows or substantial sections, and major versions for materially incompatible audience or scope changes. Never apply a version change without explicit approval.

## Acceptance criteria

- [x] Specification approved before substantial authoring.
- [x] Domain language is reflected in the appropriate `CONTEXT.md` files or in every context referenced by `CONTEXT-MAP.md`.
- [x] All material claims have evidence or explicit user attestation.
- [x] Every published screenshot is manifested and approved; this revision publishes no screenshots.
- [x] Markdown and PDFs pass mechanical, visual, privacy, and accessibility checks.
- [x] Correctness and completeness review findings are resolved or explicitly accepted.
