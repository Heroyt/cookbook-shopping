# Documentation Decisions

Record substantive authoring decisions, evidence conflicts, omissions, attestations, version choices, and review dispositions. Do not record routine wording or formatting.

## Decision template

### DOC-NNNN — Short decision

- Date: YYYY-MM-DD
- Mode: Create | Refresh | Verify
- Status: Proposed | Approved | Superseded | Review exception
- Affects: Specification or guide sections
- Evidence: Repository paths, tests, observed UI, official sources, or user attestation
- Decision: What was chosen
- Rationale: Why
- Follow-up or review date: None

### DOC-0001 — Exclude the connected Laravel Boost database from documentation evidence

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification source authority and both guides
- Evidence: `CONTEXT.md`, repository migrations, routes, models, and Laravel Boost database schema inspection
- Decision: Treat repository code, tests, migrations, and observed application behavior as authoritative; do not use the currently connected Laravel Boost database as evidence.
- Rationale: The connected database contains livestock-management tables unrelated to this cookbook and shopping-planning repository.
- Follow-up or review date: Reassess after Laravel Boost is connected to this application's database.

### DOC-0002 — Defer documentation-tool installation

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Publication contract and mechanical/PDF validation
- Evidence: GitHub release `0.6.3` metadata and the HTTP 404 returned by the documented `install.sh` URL
- Decision: Continue specification work without recreating or substituting the missing installer; leave `docs/documentation` and `docs/documentation.toml` absent.
- Rationale: The latest release does not contain the documented installer asset, and the documentation skill prohibits reconstructing the renderer or project tool interface.
- Follow-up or review date: Retry after a release publishes `install.sh`.

### DOC-0003 — Separate current user behavior from planned developer context

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification source authority, scope, and both guides
- Evidence: `CONTEXT.md`, repository routes, controllers, pages, migrations, tests, and user approval on 2026-08-09
- Decision: Document only implemented behavior in the user guide. Permit intended cookbook design in the developer/operator guide only through clearly marked **Planned** callouts.
- Rationale: The repository currently implements an authenticated application shell and settings, while the cookbook and shopping-planning domain remains unimplemented. The distinction prevents readers from treating intended behavior as available.
- Follow-up or review date: Reassess planned callouts as each domain capability is implemented.

### DOC-0004 — Serve Users, developers, operators, and AI coding agents

- Date: 2026-08-09
- Mode: Create
- Status: Superseded
- Affects: Specification audiences and developer/operator guide structure
- Evidence: `CONTEXT.md`, `docs/adr/`, repository implementation, and user approval on 2026-08-09
- Decision: Write the user guide for nontechnical household Users. Write the developer/operator guide for human developers and operators as well as AI coding agents, and make it the navigable domain reference while preserving `CONTEXT.md` as the canonical implementation-free glossary.
- Rationale: Maintainers and AI agents need a shared reference for both system operation and domain reasoning; Users need task-oriented instructions without implementation detail.
- Follow-up or review date: Superseded by DOC-0016 after the Family ownership boundary was added to `CONTEXT.md`.

### DOC-0005 — Use Czech for Users and English for developers

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification status, audiences, terminology, and both guides
- Evidence: Repository interface labels, `CONTEXT.md`, and user approval on 2026-08-09
- Decision: Write the user guide in Czech. Write the developer/operator guide in English while retaining approved Czech domain-language terms.
- Rationale: Users need localized task guidance, while developers and AI coding agents benefit from English technical documentation without losing business-specific Czech vocabulary.
- Follow-up or review date: None

### DOC-0006 — Use CONTEXT.md as the primary terminology authority

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification terminology and developer/operator guide
- Evidence: `CONTEXT.md` and user approval on 2026-08-09
- Decision: Use the canonical English terms from `CONTEXT.md` primarily in the developer/operator guide. Preserve Czech language where the context defines it, including the Meal Label values, and link back to the canonical glossary instead of creating a separate vocabulary.
- Rationale: A single terminology authority keeps human developers and AI coding agents aligned while retaining business-specific Czech labels.
- Follow-up or review date: Reassess whenever `CONTEXT.md` changes.

### DOC-0007 — Start the User guide with implemented access and settings workflows

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification scope and user guide structure
- Evidence: Application routes, Fortify configuration, Vue pages and components, feature tests, and user approval on 2026-08-09
- Decision: Cover login, password recovery, passkeys, profile, security, and appearance in the initial Czech User guide. Add Cookbook and Shopping List workflows only after they are implemented and verified. Do not present the placeholder dashboard as a workflow.
- Rationale: The guide remains useful for current behavior without overstating the planned product domain.
- Follow-up or review date: Reassess after each user-facing domain feature is implemented.

### DOC-0008 — Use separate multi-page guide sets

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification guide structures and future documentation configuration
- Evidence: Approved audiences, AI-agent reference requirement, and user approval on 2026-08-09
- Decision: Root the Czech User guide at `docs/user-guide/index.md` and the English developer/operator guide at `docs/developer-guide/index.md`, with each workflow or concern in a focused chapter file.
- Rationale: Focused pages improve navigation, targeted maintenance, direct linking, and retrieval by human readers and AI agents.
- Follow-up or review date: None

### DOC-0009 — Split domain documentation by capability

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Developer/operator guide structure
- Evidence: `CONTEXT.md`, approved AI-agent audience, and user approval on 2026-08-09
- Decision: Create separate developer-guide domain chapters for Recipes and Ingredients, Nutrition, Calendar planning, and Shopping List generation. Each chapter covers relationships, invariants, workflows, evidence links, and implemented or **Planned** status.
- Rationale: Capability-focused chapters make the domain model easier to navigate, maintain, and retrieve without duplicating the canonical glossary.
- Follow-up or review date: Reassess when a new domain capability does not fit an existing chapter.

### DOC-0010 — Use selective local screenshots with per-image approval

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification screenshot policy and Czech User guide
- Evidence: Documentation skill screenshot-safety requirements and user approval on 2026-08-09
- Decision: Publish only screenshots that materially clarify an implemented workflow. Capture locally with synthetic fixtures, manifest and inspect every image, require explicit approval before publication, and never capture production by default.
- Rationale: Selective images can clarify interface workflows while minimizing drift, privacy exposure, review burden, and accessibility risk.
- Follow-up or review date: Reassess before any non-local capture or screenshot-policy expansion.

### DOC-0011 — Use selective accessible Mermaid diagrams

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification diagram policy and developer/operator guide
- Evidence: Documentation skill accessibility requirements and user approval on 2026-08-09
- Decision: Use Mermaid only when it materially clarifies architecture, domain relationships, or multi-step data flows. Require a `diagram-alt` text alternative and complete surrounding prose for every diagram.
- Rationale: Diagrams can improve comprehension without becoming an inaccessible or renderer-dependent source of essential information.
- Follow-up or review date: None

### DOC-0012 — Track Markdown and generate separate neutral PDFs

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification publication contract and future documentation configuration
- Evidence: Project ignore policy, approved languages and audiences, and user approval on 2026-08-09
- Decision: Commit Markdown as the source of truth. Generate separate ignored PDFs for the Czech User guide and English developer/operator guide with the neutral `default` theme once project tooling is available.
- Rationale: Separate outputs suit their distinct audiences and languages while keeping reproducible build artifacts out of version control.
- Follow-up or review date: Reassess only if the project needs committed or branded deliverables.

### DOC-0013 — Start independent documentation versioning at 0.1.0

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification status, publication contract, and future refreshes
- Evidence: Documentation skill versioning guidance and user approval on 2026-08-09
- Decision: Version the guide set independently using semantic versioning, starting at `0.1.0`. Propose future bumps according to change impact and apply them only after explicit approval.
- Rationale: Independent semantic versions identify documentation releases clearly even when the application has no explicit release version.
- Follow-up or review date: None

### DOC-0014 — Approve documentation specification 0.1.0

- Date: 2026-08-09
- Mode: Create
- Status: Superseded
- Affects: Entire documentation specification and subsequent authoring
- Evidence: User approval on 2026-08-09 and `docs/documentation-spec.md`
- Decision: Approve documentation specification `0.1.0` as the authoring and publication contract for the Czech User guide and English developer/operator guide.
- Rationale: The specification resolves audiences, languages, scope, current-versus-planned behavior, information architecture, terminology, screenshots, diagrams, outputs, review gates, and versioning.
- Follow-up or review date: Reopened on 2026-08-09 after `CONTEXT.md` and new ADRs introduced the Family ownership boundary, Stores, and related domain scope.

### DOC-0015 — Reopen the specification after concurrent domain changes

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification approval, audiences, scope, and guide structures
- Evidence: Updated `CONTEXT.md`, `docs/adr/0002-keep-shopping-list-generation-persistence-independent.md`, and `docs/adr/0003-scope-domain-data-to-families.md`
- Decision: Reopen the approved specification and incorporate the new Family ownership boundary, Current Family scoping, Stores, and persistence-independent Shopping List generation before authoring.
- Rationale: The documentation skill requires renewed approval when authoritative domain language or scope changes, even when those changes appear concurrently during preparation.
- Follow-up or review date: Obtain explicit approval of the revised specification.

### DOC-0016 — Serve Users in a Family-scoped product

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Specification product boundary, audiences, scope, and both guide structures
- Evidence: Updated `CONTEXT.md` and `docs/adr/0003-scope-domain-data-to-families.md`
- Decision: Write for Users who may participate in multiple Families and operate within one Current Family. Add dedicated developer domain coverage for Family ownership and Stores, and add corresponding Czech User-guide chapters only when those workflows are implemented.
- Rationale: Family is now the canonical ownership and collaboration boundary for all cookbook and shopping-planning data.
- Follow-up or review date: Reassess whenever the Family ownership model changes.

### DOC-0017 — Approve the Family-scoped specification revision

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Entire documentation specification and subsequent authoring
- Evidence: Revised `docs/documentation-spec.md`, updated `CONTEXT.md`, ADRs 0002 and 0003, and user approval on 2026-08-09
- Decision: Approve documentation specification `0.1.0` after incorporating Family ownership, Current Family scoping, Stores, and persistence-independent Shopping List generation.
- Rationale: The revision aligns the documentation contract with the current authoritative domain glossary and ADRs while preserving the previously approved audience, language, safety, publication, and versioning policies.
- Follow-up or review date: Reopen the specification when a refresh changes one of its governing assumptions.

### DOC-0018 — Publish an evidence-separated developer implementation guide

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Developer/operator guide architecture, data, infrastructure, deployment, and roadmap chapters
- Evidence: User request on 2026-08-09, approved `docs/documentation-spec.md`, `CONTEXT.md`, `docs/adr/`, and repository implementation/configuration
- Decision: Document the implemented Laravel application and delivery assets as current behavior, while presenting the approved domain model, conceptual data structure, production baseline, and dependency-ordered implementation roadmap only in visually distinct **Planned** content.
- Rationale: Developers need an actionable design and deployment path, but the repository currently implements only the authenticated application shell and does not prove the intended domain or external production topology exists.
- Follow-up or review date: Convert each planned section to current documentation only after its implementation and operational evidence are verified.

### DOC-0019 — Resolve independent developer-guide review findings

- Date: 2026-08-09
- Mode: Create
- Status: Approved
- Affects: Entire developer/operator guide
- Evidence: Independent repository-correctness, specification-completeness, and domain-consistency reviews; repository code/configuration; `CONTEXT.md`; `docs/adr/`
- Decision: Correct all actionable semantic findings before committing the guide. Preserve unresolved implementation choices and external infrastructure gaps as explicit decision gates rather than inventing deployed behavior. Keep the accepted documentation-tool/PDF exception open.
- Rationale: The reviews found real clean-checkout, CI, authentication, unit-conversion, lifecycle, planned-label, information-architecture, and accessibility issues. Resolving them makes the Markdown an evidence-backed development reference without overstating implementation or publication readiness.
- Follow-up or review date: Re-run all publication gates after the managed documentation tool becomes available and after planned capabilities become implemented behavior.

### DOC-0020 — Keep managed tooling deferred after verifying the 0.6.3 source installer

- Date: 2026-08-09
- Mode: Refresh
- Status: Superseded
- Affects: Specification evidence gaps and publication contract
- Evidence: Release `0.6.3` metadata and checksum manifest, tag `0.6.3`, source commit `7aa33237bbe3a84c7505cc101bd4a466a66c5c21`, installer blob `c3c2a0bdb114b7092d4f490abb20e93d6515f959`, and the bootstrap error for the missing `CONFIG_SCHEMA_VERSION`
- Decision: Leave `docs/documentation` and `docs/documentation.toml` absent. Do not patch, reconstruct, or substitute the release installer; retry only after the upstream release publishes a compatible bootstrap and manifest.
- Rationale: The tag-pinned source installer is authentic to the recorded release commit, but it cannot consume the published `0.6.3` manifest. Local modification would bypass the skill's managed-tool trust and upgrade contract.
- Follow-up or review date: Superseded by DOC-0021 after the User explicitly approved a temporary trusted manual installation.

### DOC-0021 — Temporarily install trusted 0.6.3 tooling with local compatibility fixes

- Date: 2026-08-09
- Mode: Refresh
- Status: Approved
- Affects: Managed documentation tooling, renderer trust, validation, and publication contract
- Evidence: User approval on 2026-08-09; release `0.6.3`; tag source commit `7aa33237bbe3a84c7505cc101bd4a466a66c5c21`; installer blob `c3c2a0bdb114b7092d4f490abb20e93d6515f959`; the published `release.env`; immutable Scaleway installer digest `sha256:c895b325eaa5884f09e836fd6ca5a24f20ca988ee8cec9454275e182cd42803b`; immutable Scaleway renderer digest `sha256:927cb33b8c8e44ce3f161e7d541f9d054caad71394288ebfd3d987ede2a580b3`; successful `doctor` and `validate` runs
- Decision: Temporarily install the verified `0.6.3` source installer by supplying the missing `CONFIG_SCHEMA_VERSION=1` value in an otherwise unchanged local copy of the release manifest. Keep the remote profile pinned to the approved Scaleway renderer. Locally patch the managed launcher to reuse that exact allowlisted digest when it is already cached, because OrbStack/Docker 29 rejects a redundant pull with `cannot overwrite digest`; preserve the normal pull path when the image is absent and record the patched launcher checksum in the managed-file manifest.
- Rationale: The User and tooling authors explicitly trust this release and approved a temporary manual compatibility path. The workaround preserves immutable image pinning, renderer allowlisting, managed-file integrity checks, container isolation, and the stable `docs/documentation` command while avoiding the two confirmed bootstrap/runtime defects.
- Follow-up or review date: Replace the temporary installation with an unmodified official release as soon as the public installer, release manifest, and redundant digest-pull behavior are corrected; remove the local launcher patch during that upgrade.

### DOC-0022 — Use MariaDB in production and SQLite for development and tests

- Date: 2026-08-09
- Mode: Refresh
- Status: Approved
- Affects: Developer/operator guide data, local-development, infrastructure, and roadmap chapters
- Evidence: User attestation on 2026-08-09; `.env.example`; `.env.production.example`; `phpunit.xml`; `config/database.php`; `docs/adr/0005-use-mariadb-in-production-and-sqlite-locally.md`
- Decision: Use MariaDB for production persistence while native development, Docker development, and automated tests use SQLite.
- Rationale: This is the production database boundary explicitly selected by the User. The repository must continue to verify database-specific constraints and queries across both engines where their behavior differs.
- Follow-up or review date: Reassess only if the production database engine changes.

### DOC-0023 — Resolve the Slice 0 refresh review and propose version 0.1.1

- Date: 2026-08-09
- Mode: Refresh
- Status: Pending version approval
- Affects: Developer/operator guide and documentation publication metadata
- Evidence: Independent repository-correctness and specification-completeness reviews; verified clean-checkout setup, production-image build, managed documentation validation, PDF build, and visual inspection
- Decision: Correct the stale tooling status, planned/current Slice 0 split, mail scheme, local/production log and mail distinctions, migration concurrency risk, SQLite-only verification scope, setup key-rotation warning, environment placeholders, database transport-security gate, and Docker environment-file wording. Retain `0.1.0` in the specification, guide index, and renderer configuration until the User explicitly approves the proposed patch release `0.1.1`.
- Rationale: Every actionable review finding is corrected in the Markdown or environment contract. The remaining version change requires explicit approval under DOC-0013 and must not be applied implicitly during implementation.
- Follow-up or review date: Ask the User to approve or reject documentation version `0.1.1` at the Slice 0 handback.
