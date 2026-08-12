# Documentation Specification

## Status

- Mode: Refresh
- Approval: Approved on 2026-08-09 after Family-scope revision; versions 0.1.1, 0.1.2, 0.2.0, and 0.3.0 approved on 2026-08-10; version 0.4.0 approved on 2026-08-11
- Primary languages: Czech for the deferred user guide; English for the configured developer/operator guide and ADR compendium
- Documentation version: 0.4.0

## Application and evidence

### Product boundary

The product boundary is this family-scoped cookbook and shopping-planning web application. A User may participate in multiple Families and works within one Current Family at a time. Each Family exclusively owns its Cookbook, Ingredients, Stores, Store Sections, Store Placements, Calendar Entries, and Saved Shopping Lists. The currently implemented boundary contains authentication, password recovery, passkey management, profile and security settings, appearance settings, operator-only User provisioning, complete Family Access collaboration workflows, and reusable Current Family record scoping. Cookbook implements Store and reusable Store Section management with ordered associations and an icon catalogue; concrete packaged Ingredients with normalized quantities, placement, archival/restoration, direct Alternatives, Nutrition Profiles, and private normalized images; and complete versioned Recipe aggregates with repeated ordered Ingredients, Steps, approved metadata, Tags, search, archive/restore, nutrition calculation/overrides, and private cover images. Meal Planning implements transient Simple Plans, persistent weekly Calendar Entries, Calendar nutrition, arbitrary date selection, and both generation adapters. Shopping Generation implements persistence-independent exact calculation, grouping, alternatives, typed all-or-nothing problems, refresh-safe generated results, and immutable saved history.

All user-facing application copy is Czech, including visible interface text, page titles, placeholders, validation and authentication errors, flash messages and toasts, loading and empty states, and accessible-only labels. Source-code identifiers and the developer/operator guide remain English. Backend and package messages use the Czech resources under `lang/cs`; Czech is both the application and fallback locale so an English fallback is not an acceptable user-facing state.

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
- On 2026-08-11 the User approved JPEG and PNG uploads up to 5 MB with decode checks, no initial pixel-dimension limit, configurable normalized WebP variants, deterministic entity filenames, replacement, and entity-deletion cleanup. DOC-0055 adds pre-decode dimension and total-pixel safety limits for both UI and Agent callers after security review demonstrated that encoded byte limits alone do not bound GD memory allocation.
- On 2026-08-11 the User approved documentation version 0.4.0 and requested validation and publication.

### Intended-versus-implemented mismatches

Slices 1 through 7 are implemented: Family Access; Stores, Store Sections, the icon catalogue, packaged Ingredients and private media; Recipes and nutrition; pure Shopping Generation; Simple Plan; weekly Calendar; and immutable Saved Shopping List history. Media storage normalizes accepted JPEG/PNG uploads into configured private WebP variants and retains Recipe and Ingredient media on archive, while hard Store, Store Section, or Family deletion removes the affected files transactionally. Slice 8 Agent Integration remains planned and has no Agent Credential, Agent API, Change Set, Sanctum, Scramble, or OpenAPI implementation. Slice 0's live Komodo/MariaDB recreation evidence remains incomplete even though the repository and disposable MariaDB compatibility gates have been exercised.

- Omit unimplemented behavior from the user guide.
- The developer/operator guide may describe intended design only in visually distinct **Planned** callouts, separated from current setup and operational instructions.

## Audiences

### User guide

The Czech User guide remains an approved future deliverable, but it is deferred and not configured in the current documentation release. The repository does not yet contain its required `docs/user-guide/index.md` source or a User-guide PDF target. The requirements below govern its eventual authoring; they are not a claim that the guide or PDF currently exists.

Nontechnical Users who participate in one or more Families and manage the Current Family's Cookbook and shopping plans. Readers are assumed to know how to use a modern web browser but are not expected to understand the application's implementation. The guide helps them access the application, manage personal and security settings, select and collaborate within a Family, and complete every implemented cookbook and shopping-planning workflow.

The user guide excludes developer setup, architecture, deployment, internal domain design, and unimplemented workflows.

Write the user guide in Czech and preserve the exact Czech interface labels. If observed interface copy is English, treat it as an application defect rather than preserving it in the guide.

### Developer/operator guide

Human developers, operators, and AI coding agents that develop, deploy, operate, troubleshoot, or reason about the application. Readers may need both implementation guidance and stable domain context.

The guide serves as the main navigable developer reference for:

- Architecture, system boundaries, local development, configuration, testing, deployment, operations, and troubleshooting.
- Domain concepts, relationships, invariants, and workflows, grounded in the canonical glossary in `CONTEXT.md` and substantive decisions in `docs/adr/`.
- Clearly marked **Planned** domain design that must not be mistaken for implemented behavior.

`CONTEXT.md` remains the implementation-free canonical glossary rather than being duplicated or replaced by the guide.

Write the developer/operator guide in English and use the canonical terms from `CONTEXT.md` primarily. Preserve Czech domain language where `CONTEXT.md` defines it, including the five Meal Label values: snídaně, dopolední svačina, oběd, odpolední svačina, and večeře. Publish the canonical glossary as the guide's final chapter rather than maintaining a separate competing vocabulary.

Publish `CONTEXT.md` itself as the final Domain Glossary appendix so the developer PDF remains self-contained without duplicating or transforming the canonical vocabulary.

### Architecture Decision Record compendium

Developers, operators, and AI coding agents that need the complete rationale behind accepted application decisions. Publish a separate English PDF directly from `docs/adr/index.md` and every numbered ADR in identifier order. The compendium is a decision record, not an implementation-status guide; its introduction must direct readers to the Developer and Operator Guide for current versus **Planned** behavior.

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
- The planned Agent API, credential, OpenAPI, Change Set, and operational-history boundary when clearly marked **Planned**.

### Excluded

- Unimplemented behavior in the user guide.
- The placeholder dashboard as a user workflow.
- Claims derived from the unrelated Laravel Boost database.
- Production screenshots or sensitive personal data unless separately approved.

## Guide structures

### User guide

When the deferred User guide is resumed, use a multi-page guide rooted at `docs/user-guide/index.md` with focused chapters for:

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
- Agent integrations: Agent Credentials, Family Catalog, atomic Agent Change Sets, generated OpenAPI contract, and history
- Architecture and system boundaries
- Local development and configuration
- Authentication, authorization, and security
- Frontend architecture and navigation
- Persistence and data flows
- Testing and quality gates
- Deployment, operations, recovery, and scaling
- Troubleshooting and command reference
- Domain Glossary appendix sourced directly from `CONTEXT.md`

Keep the documentation specification, documentation decisions, and other planning or review records outside rendered source lists. Publish ADRs only through the dedicated compendium, and link to those sources from the developer guide where they provide useful evidence.

### Architecture Decision Record compendium

Use `docs/adr/index.md` as a short status and navigation introduction, followed by every numbered file in `docs/adr/` in identifier order. Render the original ADR sources directly; do not copy their text into a parallel appendix that could drift.

Every domain chapter must explain relationships, invariants, and workflows; direct readers to the final Domain Glossary chapter for canonical definitions; link to relevant ADRs; and identify each capability as implemented or **Planned**. Keep `CONTEXT.md` as the authoritative repository source, but do not expose its repository path as reader navigation inside the self-contained PDF.

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
- The current configured publication targets generate the English developer/operator-guide PDF and the English Architecture Decision Record compendium under ignored `docs/pdf/`.
- At the two publication entry points, use `file:` links containing only the sibling PDF filename so the renderer emits portable relative cross-document actions between `developer-guide-en.pdf` and `architecture-decisions-en.pdf`. This is a narrow output-navigation exception; ordinary source navigation continues to use relative Markdown links.
- When the deferred Czech User guide is authored and added to `documentation.toml`, generate its separate Czech PDF under ignored `docs/pdf/` and run the same publication gates before calling it publish-ready.
- Use the neutral `default` PDF theme rather than eSoul client branding.
- Do not commit generated PDFs unless a later project decision explicitly changes the publication policy.
- Treat the Markdown sources as authoritative when generated artifacts disagree.
- Require specification approval, traceable decisions, Markdown and asset validation, successful Dockerized PDF builds, structural PDF checks, every-page visual inspection, privacy and source-level accessibility review, and correctness and completeness reviews before calling any configured guide publish-ready.

Version documentation independently using semantic versioning, beginning at `0.1.0`. During refreshes, propose patch versions for corrections, minor versions for new workflows or substantial sections, and major versions for materially incompatible audience or scope changes. Never apply a version change without explicit approval.

## Acceptance criteria

- [x] Specification approved before substantial authoring.
- [x] Domain language is reflected in the appropriate `CONTEXT.md` files or in every context referenced by `CONTEXT-MAP.md`.
- [x] All material claims have evidence or explicit user attestation.
- [x] Every published screenshot is manifested and approved; the configured developer guide publishes no screenshots.
- [x] Version 0.4.0 developer-guide and ADR-compendium Markdown and PDFs pass mechanical, visual, privacy, and accessibility checks.
- [x] Version 0.4.0 developer-guide and ADR-compendium correctness and completeness review findings are resolved or explicitly accepted.
- [ ] The deferred Czech User guide has authoritative Markdown, a configured PDF target, and completed publication gates.
