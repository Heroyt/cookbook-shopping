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
- Status: Superseded
- Affects: Publication contract and mechanical/PDF validation
- Evidence: GitHub release `0.6.3` metadata and the HTTP 404 returned by the documented `install.sh` URL
- Decision: Continue specification work without recreating or substituting the missing installer; leave `docs/documentation` and `docs/documentation.toml` absent.
- Rationale: The latest release does not contain the documented installer asset, and the documentation skill prohibits reconstructing the renderer or project tool interface.
- Follow-up or review date: Superseded first by DOC-0021's temporary installation and finally by the official `0.6.5` upgrade in DOC-0024.

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
- Status: Superseded
- Affects: Managed documentation tooling, renderer trust, validation, and publication contract
- Evidence: User approval on 2026-08-09; release `0.6.3`; tag source commit `7aa33237bbe3a84c7505cc101bd4a466a66c5c21`; installer blob `c3c2a0bdb114b7092d4f490abb20e93d6515f959`; the published `release.env`; immutable Scaleway installer digest `sha256:c895b325eaa5884f09e836fd6ca5a24f20ca988ee8cec9454275e182cd42803b`; immutable Scaleway renderer digest `sha256:927cb33b8c8e44ce3f161e7d541f9d054caad71394288ebfd3d987ede2a580b3`; successful `doctor` and `validate` runs
- Decision: Temporarily install the verified `0.6.3` source installer by supplying the missing `CONFIG_SCHEMA_VERSION=1` value in an otherwise unchanged local copy of the release manifest. Keep the remote profile pinned to the approved Scaleway renderer. Locally patch the managed launcher to reuse that exact allowlisted digest when it is already cached, because OrbStack/Docker 29 rejects a redundant pull with `cannot overwrite digest`; preserve the normal pull path when the image is absent and record the patched launcher checksum in the managed-file manifest.
- Rationale: The User and tooling authors explicitly trust this release and approved a temporary manual compatibility path. The workaround preserves immutable image pinning, renderer allowlisting, managed-file integrity checks, container isolation, and the stable `docs/documentation` command while avoiding the two confirmed bootstrap/runtime defects.
- Follow-up or review date: Superseded by DOC-0024 after the official `0.6.5` upgrade removed the temporary compatibility path.

### DOC-0022 — Use MariaDB in production and SQLite for development and tests

- Date: 2026-08-09
- Mode: Refresh
- Status: Approved
- Affects: Developer/operator guide data, local-development, infrastructure, and roadmap chapters
- Evidence: User attestation on 2026-08-09; `.env.example`; `.env.production.example`; `phpunit.xml`; `config/database.php`; `docs/adr/0005-use-mariadb-in-production-and-sqlite-locally.md`
- Decision: Use MariaDB for production persistence while native development, Docker development, and automated tests use SQLite.
- Rationale: This is the production database boundary explicitly selected by the User. The repository must continue to verify database-specific constraints and queries across both engines where their behavior differs.
- Follow-up or review date: Reassess only if the production database engine changes.

### DOC-0023 — Resolve the Slice 0 refresh review and release version 0.1.1

- Date: 2026-08-09
- Mode: Refresh
- Status: Approved
- Affects: Developer/operator guide and documentation publication metadata
- Evidence: Independent repository-correctness and specification-completeness reviews; verified clean-checkout setup, production-image build, managed documentation validation, PDF build, and visual inspection; User approval of version `0.1.1` on 2026-08-10
- Decision: Correct the stale tooling status, planned/current Slice 0 split, mail scheme, local/production log and mail distinctions, migration concurrency risk, SQLite-only verification scope, setup key-rotation warning, environment placeholders, database transport-security gate, and Docker environment-file wording. Apply the approved patch version `0.1.1` consistently to the specification, guide index, and renderer configuration.
- Rationale: Every actionable review finding is corrected in the Markdown or environment contract. The User supplied the explicit approval required by DOC-0013 before the version metadata changed.
- Follow-up or review date: None

### DOC-0024 — Upgrade managed documentation tooling to 0.6.5

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Managed documentation tooling, renderer trust, validation, and publication records
- Evidence: User request on 2026-08-10; official GitHub release `0.6.5`; release source commit `e95adf78a0111200b26e5904a50e8de46b859f62`; published `install.sh` SHA-256 `3d9f6f067f78a1d9f336a4298d53e103cf5f2415b2d029203e46fa5e7f50b2f4`; published `release.env` SHA-256 `d6f4eb6f327b401b7ce84f7c5d14c6528f82e07417a05fde26f3c2ca594adfd0`; immutable Scaleway installer digest `sha256:bd6e30ba5c820815e7324f845c39683e22843178a1b4fe20595eaf6e4b016809`; immutable Scaleway renderer digest `sha256:b1804236503c31bf73d559848e03582dc9843fe12bfe784c87bced4639bf134a`; reviewed upgrade preview; successful post-upgrade `doctor`; deterministic platform-mismatch reproduction; successful `validate` after removing the stale `linux/amd64` override
- Decision: Replace the temporary `0.6.3` installation and local launcher compatibility patch with the official remote-profile `0.6.5` managed tooling. Preserve project-owned guide sources and publication settings, update the pinned renderer digest, clear the obsolete `pdf.platform = "linux/amd64"` override so Docker selects the native image, and use the standard GitHub release bootstrap for future upgrades.
- Rationale: Release `0.6.5` publishes a compatible installer and manifest, provides a native arm64 renderer, and natively handles Docker's failed redundant digest pull only when the exact immutable image is inspectable locally. The official upgrade restores the managed-tool trust and lifecycle contract without changing documentation scope or configuration schema.
- Follow-up or review date: Reassess through the standard upgrade preview when a newer official release is requested.

### DOC-0025 — Use the personal single-host production profile

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Production runtime, persistence, health, recovery, observability, and Slice 0 completion criteria
- Evidence: User attestation on 2026-08-10; `.env.production.example`; production Dockerfile and entrypoint; `bootstrap/app.php`; `routes/console.php`; repository search showing no queued jobs or application-specific schedules; ADR 0006
- Decision: Record the user's exact selections: keep the Komodo stack only on the server, run one image/container with startup migrations and same-host MariaDB, use filesystem media with S3 as a future option, require no automated recovery or current observability integration, and retain the existing OpenTelemetry stack only as a future option. Derive the repository contract separately: mount `/var/www/storage/app` for Laravel's private local disk, use synchronous production jobs while no worker is required, retain the image's existing cron process, and recommend shallow `/up` health polling. These derived values are not user attestations or evidence of live Komodo configuration. Workers, additional scheduler roles, dependency-aware readiness, backups, and telemetry may be introduced later when operational needs justify them.
- Rationale: This is the smallest production profile that matches the application's current behavior and personal-project risk tolerance. It avoids an idle worker and dependency-driven restart loops while preserving a migration path to S3, asynchronous jobs, and stronger operations.
- Follow-up or review date: Reassess before adding replicas, queued work, application-specific schedules, valuable irreplaceable data, or externally committed availability objectives.

### DOC-0026 — Publish the 0.1.1 developer guide after resolving production-profile review findings

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Developer/operator guide publication readiness
- Evidence: Successful managed-tool `doctor`, `validate`, and `build` runs; structural PDF inspection; every-page visual inspection; independent correctness, completeness, standards, and specification reviews
- Decision: Publish the version 0.1.1 developer/operator guide after correcting every actionable review finding. Keep live Komodo configuration, `/up` polling, MariaDB connectivity, and database/private-file survival across application-container recreation as external evidence still required for Slice 0 implementation completion, not as documentation publication blockers.
- Rationale: The Markdown and generated PDF now satisfy the approved mechanical, visual, privacy, accessibility, correctness, and completeness gates without overstating the unobserved production deployment.
- Follow-up or review date: Collect and record the external acceptance evidence before marking Slice 0 complete.

### DOC-0027 — Keep Jenkins as the sole CI pipeline

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Repository CI configuration and developer/operator infrastructure guide
- Evidence: User direction on 2026-08-10; `Jenkinsfile`; removed alternate CI definition and its dedicated maintenance and export configuration; delivery baseline test
- Decision: Treat Jenkins as the authoritative and only CI pipeline. Remove the alternate pipeline configuration and its documentation and maintenance artifacts, and retain a repository test that enforces that boundary. Record external Jenkins trigger/status-gate configuration and repository-unverified ESLint/Prettier coverage as operational evidence gaps.
- Rationale: A second workflow would duplicate CI ownership and could diverge from the organization-provided Jenkins libraries, registry flow, and Komodo deployment path.
- Follow-up or review date: Reassess only if the User explicitly selects another CI authority.

### DOC-0028 — Release the CI-authority correction as version 0.1.2

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, developer/operator guide, and renderer version metadata
- Evidence: User approval of version `0.1.2` on 2026-08-10; DOC-0027; resolved independent correctness and completeness reviews
- Decision: Release the Jenkins-only CI correction and its operator evidence gaps as documentation version `0.1.2`.
- Rationale: The change corrects CI ownership and operational guidance after publication of `0.1.1`, so it is a backward-compatible patch release under the approved semantic-version policy.
- Follow-up or review date: None

### DOC-0029 — Document the Slice 1 tracer without closing Slice 0

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Developer/operator guide implementation status and roadmap
- Evidence: Family Access migrations, models, actions, HTTP and Inertia surfaces, focused PHPUnit coverage, and User direction on 2026-08-10 to proceed while retaining deployment responsibility
- Decision: Describe Family persistence, Family creation, its initial roleless membership, and final-member account-deletion protection as implemented behavior. Keep provisioning, member management, Current Family selection, reusable Family-scoped authorization, and all later domain modules marked as planned. Preserve the Komodo/MariaDB recreation check as unverified external evidence and keep Slice 0 incomplete despite the explicitly authorized Slice 1 progression.
- Rationale: The guide must follow verified repository behavior without converting a development-sequencing exception into invented production evidence or overstating Slice 1 completion.
- Follow-up or review date: Collect the Slice 0 external acceptance evidence and refresh the guide as later Slice 1 increments become verified.

### DOC-0030 — Release the Family Access tracer as version 0.2.0

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, developer/operator guide, and renderer version metadata
- Evidence: User approval of version `0.2.0` on 2026-08-10; DOC-0029; verified Family Access implementation and tests
- Decision: Release the first implemented Family workflow and its account-lifecycle rule as documentation version `0.2.0`.
- Rationale: Family creation is a new user-facing workflow and the first persisted domain slice, so it is a backward-compatible minor documentation release under the approved semantic-version policy.
- Follow-up or review date: Complete the mechanical, PDF, correctness, and completeness publication gates before calling this revision publish-ready.

### DOC-0031 — Publish the reviewed Family Access tracer guide

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Version 0.2.0 developer/operator guide publication readiness
- Evidence: Successful managed-tool `doctor`, `validate`, and `build` runs; structural and every-page PDF inspection; focused backend and frontend tests; independent correctness, completeness, Standards, and Spec reviews with every actionable finding resolved
- Decision: Publish the version 0.2.0 developer/operator guide with the implemented Family creation tracer and final-member account-deletion rule. Keep Slice 0 incomplete, its live Komodo/MariaDB recreation check external and unverified, and the remaining Slice 1 workflows explicitly planned.
- Rationale: The guide now follows verified repository behavior, the approved version, and the User's development-sequencing direction without inventing production evidence or unresolved provisioning and Current Family decisions.
- Follow-up or review date: Collect the Slice 0 external acceptance evidence and refresh the guide as later Slice 1 increments become verified.

### DOC-0032 — Implement operator provisioning and persisted Current Family selection

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Family Access implementation, documentation specification, and developer/operator guide
- Evidence: User selection `A1 + B1` on 2026-08-10; `user:create` command and tests; Current Family migration, resolver, HTTP/Inertia workflow, membership and Family lifecycle actions, and focused PHPUnit/Vitest coverage
- Decision: Keep public registration disabled and provision existing Users through an operator-only interactive Artisan command. Persist the nullable `users.current_family_id` preference, validate it against live membership on each authenticated Inertia request, and clear or replace it when membership disappears. Document member management and Family deletion as implemented while keeping reusable authorization for Family-owned records planned.
- Rationale: The selected paths provide a narrow onboarding mechanism and stable multi-Family context without inventing invitation behavior or treating a preference as authorization. They preserve roleless membership, Current-Family-scoped commands, the modular monolith, and the persistence-independent Shopping Generation boundary.
- Follow-up or review date: Complete the first Family-owned tracer and cross-Family isolation gate in Slice 2; collect Slice 0 external acceptance evidence separately.

### DOC-0033 — Use normalized keys for Family-scoped name uniqueness

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Store persistence, later Family-scoped names, ADR 0007, and developer/operator data guidance
- Evidence: User selection `C1` on 2026-08-10; Store model normalization; composite database constraint; focused duplicate and cross-Family tests passing on SQLite; successful full migration and nine-test/71-assertion Store suite against an ephemeral MariaDB 11.8 database
- Decision: Generate a squished display name and lowercase normalized key in PHP, and enforce `(family_id, normalized_name)` with a database unique constraint. Reuse this strategy for later case-insensitively unique Family-owned names.
- Rationale: The explicit key gives SQLite and MariaDB the same application semantics without relying on their different collation defaults, while the database constraint remains race-safe.
- Follow-up or review date: Repeat against the selected live MariaDB server version before the applicable production migration ships; the local MariaDB check does not close the separate Slice 0 acceptance gap.

### DOC-0034 — Release the Store authorization tracer as version 0.3.0

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, developer/operator guide, and renderer version metadata
- Evidence: User approval of version `0.3.0` and direction to continue into the next slice on 2026-08-10; reusable Current Family scope; Store migration, backend/UI tracer, and focused PHPUnit/Vitest coverage
- Decision: Publish the Family Access completion gate and first Slice 2 Store tracer as documentation version `0.3.0`, while keeping the rest of Slice 2 and the external Slice 0 evidence explicitly incomplete.
- Rationale: Store creation/listing is a new user-facing workflow and establishes the reusable cross-module authorization interface, so it is a backward-compatible minor release under the approved semantic-version policy.
- Follow-up or review date: Complete publication gates before calling version 0.3.0 publish-ready; collect live Komodo/MariaDB recreation evidence separately.

### DOC-0035 — Document Current-Family-scoped Store renaming

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification and developer/operator guide Store, architecture, frontend, data, and roadmap chapters
- Evidence: User direction on 2026-08-10; `UpdateStore`; Store update request, route, controller, and Wayfinder binding; focused PHPUnit/Vitest coverage
- Decision: Describe Store rename as implemented behavior after the tested tracer resolves the Store through `CurrentFamilyScope`, reuses ADR 0007 normalization, converts database uniqueness collisions to field validation errors, and exposes the operation through an Inertia/shadcn-vue Dialog. Keep Store deletion, logos, Sections, Ingredients, ordering, and all later Slice 2 capabilities planned. Keep Slice 0's external Komodo/MariaDB recreation gate incomplete and unverified. Retain documentation version `0.3.0` until the User explicitly approves a version change.
- Rationale: The refresh follows verified repository behavior without broadening the tracer, inventing lifecycle decisions, or converting local database checks into production evidence. The approved documentation policy prohibits an unapproved version bump.
- Follow-up or review date: Propose the next documentation version for explicit approval; collect Slice 0 external acceptance evidence separately.
