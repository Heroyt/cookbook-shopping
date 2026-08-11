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
- Evidence: User direction on 2026-08-10; `RenameStore`; Store update request, route, controller, and Wayfinder binding; focused PHPUnit/Vitest coverage; successful full migration and rename-inclusive 13-test/109-assertion Store suite against disposable MariaDB 11.8
- Decision: Describe Store rename as implemented behavior after the tested tracer resolves the Store through `CurrentFamilyScope`, reuses ADR 0007 normalization, converts database uniqueness collisions to field validation errors, and exposes the operation through an Inertia/shadcn-vue Dialog. Keep Store deletion, logos, Sections, Ingredients, ordering, and all later Slice 2 capabilities planned. Keep Slice 0's external Komodo/MariaDB recreation gate incomplete and unverified. Retain documentation version `0.3.0` until the User explicitly approves a version change.
- Rationale: The refresh follows verified repository behavior without broadening the tracer, inventing lifecycle decisions, or converting local database checks into production evidence. The approved documentation policy prohibits an unapproved version bump.
- Follow-up or review date: Propose the next documentation version for explicit approval; collect Slice 0 external acceptance evidence separately.

### DOC-0036 — Document Current-Family-scoped Store deletion

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification and developer/operator guide Store, architecture, frontend, data, and roadmap chapters
- Evidence: User direction to continue implementation on 2026-08-10; `DeleteStore`; Store destroy request, route, controller, and generated Wayfinder binding; shadcn-vue AlertDialog composition; deletion-inclusive 15-test/132-assertion PHPUnit Store suite and five-test Vitest Store UI contract
- Decision: Describe Store deletion as implemented after the tested tracer resolves the Store exclusively through `CurrentFamilyScope`, ignores a client-supplied Family identifier, returns not found for a foreign-Family Store, hard-deletes the resolved Store, and exposes a consequence-stating confirmation AlertDialog. Keep logos, Store Sections, Ingredients, ordering, and future Ingredient placement-clearing behavior planned. Do not invent the reusable Store Section deletion policy. Keep Slice 0's external Komodo/MariaDB recreation gate incomplete and unverified. A new user-facing workflow would ordinarily justify documentation version `0.4.0`; retain `0.3.0` until the User explicitly approves a version change.
- Rationale: The refresh follows verified repository behavior while distinguishing the currently executable Store deletion from placement effects that cannot exist before Ingredients and Store Sections are persisted. It preserves the Family-scoped modular dependency and the pure Shopping Generation boundary.
- Follow-up or review date: Ask for explicit approval before changing the documentation version; collect Slice 0 external acceptance evidence separately; resolve the reusable Store Section deletion policy only when that entity is designed.

### DOC-0037 — Close Store deletion documentation review findings

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Developer/operator guide security, Family Access, Store workflow, and frontend-verification guidance
- Evidence: Independent correctness and completeness reviews of the Store deletion refresh; `CurrentFamilyScope`, `DeleteStore`, `StoreController`, Store feature tests, Store UI source-contract tests, and the configured guide
- Decision: Expand current authorization inventories to include Store deletion, document missing/obsolete Store and missing Current Family failure outcomes, and distinguish direct endpoint coverage from source-only AlertDialog inspection. Keep confirmation, cancellation, processing, failure, focus, keyboard, and toast-announcement behavior in the planned rendered/browser test list until such tests exist.
- Rationale: The dispositions remove stale authorization language and avoid implying browser-level evidence that the current PHPUnit and source-contract Vitest suites do not provide.
- Follow-up or review date: Add rendered-component and browser coverage when the project introduces the required DOM/browser harness; retain the separate live Komodo acceptance and Store Section deletion-policy gaps.

### DOC-0038 — Resolve the first Cookbook design frontier

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Canonical glossary, Cookbook and Shopping Generation plans, Store Section lifecycle, media lifecycle, quantity arithmetic, Measurement Units, Alternative Ingredients, ADRs 0012–0014, and the developer/operator guide
- Evidence: User answers to grill round one on 2026-08-10 (`Q1 A`, `Q2 B` with a UI colour picker, `Q3 A`, `Q4 A`, clarified `Q5 A`, and `Q6 A`)
- Decision: Delete a reusable Store Section by transactionally removing all of its Store associations and clearing the Section from affected Ingredient placements while retaining their Store assignments. Give Sections a User-selected validated six-digit hexadecimal colour and optional allowlisted icon. Keep one original entity-owned private media file, remove superseded files after commit, retain files on archive, and remove them on hard or Family deletion. Persist finite quantities as `DECIMAL(20,6)`, reject greater input scale, and calculate with exact rational intermediates. Use one universal `piece` unit with Ingredient-package-specific piece counts; any alternate wording is presentation only. Persist each direct Alternative Ingredient relationship once as a canonical ordered, unique, same-Family self-referential many-to-many edge.
- Rationale: These choices close the immediate lifecycle and representation blockers without adding custom unit identities, media variants, or persistence dependencies to Shopping Generation. They preserve Store placement when only a Section disappears, make package rounding deterministic, and keep symmetric alternatives structurally unambiguous.
- Follow-up or review date: Resolve the newly unblocked placement-integrity, alternative-eligibility and conversion, generator-result, grouping, Calendar uniqueness, presentation-quantization, and snapshot-storage decisions before implementing the affected slices. All behavior in this decision remains planned until verified in code; the separate Slice 0 Komodo/MariaDB recreation gate remains incomplete.

### DOC-0039 — Resolve the persistence and calculation frontier

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Store Placement integrity, Alternative eligibility and replacement, Shopping Generation contracts and grouping, Calendar uniqueness, quantity presentation, Saved Shopping List encoding, ADRs 0015–0020, and the developer/operator guide
- Evidence: User answers to grill round two on 2026-08-10 (`Q7 A`, strict exact-unit Q8 with no automatic conversion, `Q9 A`, `Q10 A`, `Q11 A`, `Q12 A`, `Q13 A` limited to two fractional digits, and `Q14 A`)
- Decision: Constrain an Ingredient's optional Store–Section pair through the Store–Section association. Preserve Alternative edges on archive but offer only active Ingredients for new choices; automatically replace only when the Alternative package defines every exact Recipe contribution unit, without metric or package-equivalence conversion. Return either a complete grouped Shopping List or typed structured Calculation Problems, with grouping performed by a dedicated pure collaborator behind the persistence-independent generator facade. Persist absent Meal Labels using the internal non-null `unlabeled` key. Render secondary quantities with at most two fractional digits using half-up rounding and mark changed values approximate. Store Saved Shopping Lists using relational headers plus one immutable versioned JSON payload containing lossless exact values and frozen display values.
- Rationale: These choices make placement and Calendar uniqueness database-backed across SQLite and MariaDB, keep substitutions explicit, prevent partial purchase plans from appearing valid, retain grouping as domain behavior without coupling it to arithmetic, and keep immutable history simple and reproducible.
- Follow-up or review date: Resolve only the remaining dependent lifecycle, input, and concurrency questions exposed by these decisions before implementing the affected slices. All behavior remains planned until verified in code; documentation version remains `0.3.0` pending explicit approval, and the live Slice 0 Komodo/MariaDB recreation gate remains incomplete.

### DOC-0040 — Resolve Cookbook lifecycle and interaction semantics

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Alternative eligibility and provenance, Recipe and Ingredient lifecycle, Store Section ordering, Store-group presentation, Calendar duplicate creation, Saved Shopping List creation, ADRs 0016 and 0021–0025, and the developer/operator guide
- Evidence: User clarification of grill round three on 2026-08-10: Q15 confirms that no manual replacement quantity exists and permits only Alternatives with every required exact unit; `Q16 A`, `Q17 A`, `Q18 A`, `Q19 A`, `Q20 C` with explicit UI notice, and `Q21 B`
- Decision: Clarify DOC-0039's Alternative eligibility boundary: an ineligible Alternative is not selectable, there is no manual quantity fallback, and every accepted Alternative uses the normal package-count calculation. Keep replacement single-hop per originally generated Ingredient and retain independently reversible provenance after merged results. Make Recipe and Ingredient archival reversible and omit individual hard deletion in the MVP. Persist contiguous per-Store Section positions and rewrite the complete order under a transactional lock. Sort Store groups by normalized name with stable identity as a tie-breaker. Atomically add a duplicate Calendar create's Serving Count to the existing entry and explicitly disclose the resulting total. Create a separate Saved Shopping List for every accepted save, including retries and identical content.
- Rationale: The clarified Alternative workflow avoids introducing a second quantity-entry model. The remaining decisions favor reversible content lifecycle, simple household-scale ordering, deterministic output, visible additive Calendar behavior, and literal save-event history over deduplication.
- Follow-up or review date: Resolve the final Recipe, package-definition, Calendar-edit, and concurrent interaction frontier before declaring the design grill complete. All behavior remains planned until verified in code; documentation version remains `0.3.0` pending explicit approval, and the live Slice 0 Komodo/MariaDB recreation gate remains incomplete.

### DOC-0041 — Normalize package quantities and settle concurrent interactions

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Ingredient and Recipe Ingredient quantity representation, Alternative eligibility, Calendar collision and archived-entry behavior, Store Section reorder concurrency, ADRs 0014, 0016, 0024, and 0026–0029, and the developer/operator guide
- Evidence: User answers to grill round four on 2026-08-10: Q22 requires persisted and calculated grams, millilitres, and unitless piece counts with other metric units limited to input/display; Q23 selects one mutually exclusive metric quantity plus a separate optional piece count; `Q24 A`, `Q25 A`, `Q26 A`, and `Q27 B` with an explicit preference against over-engineering
- Decision: Normalize weight to grams and volume to millilitres before persistence and calculation. Let an Ingredient define weight or volume, never both, and optionally piece count, while requiring at least one quantity; treat `piece` as a canonical display marker rather than a selectable stored unit. Compare canonical quantity kinds for Alternative eligibility. Merge a collision-producing Calendar edit into its target and remove the source with an explicit resulting-total notice. Limit archived-Recipe Calendar Entry changes to Serving Count or deletion. Reject stale Store Section reorders using optimistic versioning. Apply every accepted Calendar accumulation request, including a retry, without idempotency infrastructure.
- Rationale: Canonical quantities eliminate metric-unit identity and density ambiguity while retaining convenient explicit input/display units. The interaction choices keep additive Calendar and ordering behavior visible and predictable without introducing retry infrastructure the User does not value.
- Follow-up or review date: Verify that no unresolved product decision remains in the approved MVP frontier, then run the documentation correctness, completeness, mechanical, and PDF publication gates. All behavior remains planned until verified in code; documentation version remains `0.3.0` pending explicit approval, and the live Slice 0 Komodo/MariaDB recreation gate remains incomplete.

### DOC-0042 — Close the aggregate and display decision frontier

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Metric display derivation, Simple Plan duplicate behavior, Recipe aggregate persistence and concurrency, Recipe Tag deletion, ADRs 0026 and 0030–0033, and the developer/operator guide
- Evidence: User answers to grill round five on 2026-08-10: Q28 rejects stored display preferences and derives the higher metric unit from canonical values at 1000; `Q29 A`, `Q30 A`, and `Q31 A`
- Decision: Store only canonical grams and millilitres, never an input-unit preference. Display values below 1000 as `g` or `ml` and values from 1000 as `kg` or `l`, then apply the approved two-fractional-digit formatting. Accumulate a duplicate Simple Plan Recipe addition into its existing transient Serving Count with a notice. Save complete Recipe aggregates with contiguous child positions, transactional validation, and optimistic version rejection for stale edits. Hard-delete Recipe Tags after consequence confirmation, detach their assignments, preserve Recipes, and release normalized names for reuse.
- Rationale: Derived display units keep canonical values authoritative and predictable. The aggregate choices align transient planning with additive interactions, protect collaborative Recipe edits without automatic merging, and treat Tags as removable classification metadata rather than historical entities.
- Follow-up or review date: User confirmation was recorded in DOC-0045 on 2026-08-10. Revalidate each Planned behavior during implementation; documentation version remains `0.3.0` pending explicit approval, and the live Slice 0 Komodo/MariaDB recreation gate remains incomplete.

### DOC-0043 — Define the API-first Agent Integration boundary

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Canonical glossary, Agent Integration architecture, Family authorization seam, credential security, persistence, frontend navigation, implementation roadmap, API documentation, ADRs 0008–0011 and 0034–0037, and the developer/operator guide
- Evidence: User answers to Agent Integration grill questions Q1–Q66 and explicit shared-understanding confirmation at Q67 on 2026-08-10; official Laravel 13 Sanctum, authentication, and MCP documentation; current MCP authorization and transport specifications; OpenAPI specification; Scramble installation, authentication, multiple-document, caching, and export documentation; verified absence of an implemented API, Sanctum, Scramble, or MCP boundary in the repository
- Decision: Use a versioned REST/JSON Agent API with a Scramble-generated OpenAPI 3.1 contract instead of MCP for v1. Authenticate manually configured trusted agents with expiring Sanctum Agent Credentials fixed to one issuer and Family. Derive an Authorized Family Context without using Current Family state, and reuse the same explicit Cookbook and Meal Planning actions as the web interface. Expose complete Catalog reads and atomic, digest-bound preview/apply Agent Change Sets for Stores, Store Sections, Ingredients, Recipe Tags, Recipes, and Calendar Entries after those entities are implemented. Keep source extraction, media, Simple Plans, Shopping Generation, Saved Shopping Lists, Family Access, credential management through the API, and MCP outside v1. Retain applied Change Set provenance until a Family member deletes it. Generate and cache the public runtime contract without committing an artifact. Implement the integration as the final planned slice after its domain dependencies.
- Rationale: The API is simpler to authenticate, host, document, test, and consume than a first-class MCP server for this personal deployment. The preview/apply protocol preserves agent autonomy while making multi-record effects inspectable, idempotent, warning-aware, and atomic. The shared authorization and action seams prevent Current Family mutation and avoid a second set of domain rules.
- Follow-up or review date: Implement only after the v1 Cookbook and Meal Planning aggregates and actions are complete; reconsider a thin MCP adapter only when a concrete MCP client need exists. The new substantial planned chapter would ordinarily justify a minor documentation version, but retain `0.3.0` until the User explicitly approves a version change.

### DOC-0044 — Establish Czech as the exclusive user-interface language

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Application locale, frontend copy, backend and package messages, accessibility labels, documentation specification, developer/operator guide, and future implementation rules
- Evidence: User direction on 2026-08-10 to make all user-facing UI Czech and require the same convention for future development; repository-wide UI string audit; focused PHPUnit and Vitest localization coverage
- Decision: Use Czech for every user-facing application string, including page metadata, visible copy, forms, dialogs, validation and authentication errors, flash messages and toasts, loading and empty states, and accessible-only labels. Configure `cs` as both the Laravel locale and fallback locale, maintain backend and package translations under `lang/cs`, and keep code identifiers and developer/operator documentation in English. Treat newly introduced English interface copy as a defect that requires test coverage.
- Rationale: One explicit language boundary prevents mixed-language workflows and makes both visual and assistive-technology output predictable while preserving English as the implementation and operator-documentation language.
- Follow-up or review date: Apply the rule to every new user-facing workflow and extend translation resources and localization coverage when new framework or package messages become reachable; retain documentation version `0.3.0` until the User explicitly approves a version change.

### DOC-0045 — Confirm the Cookbook design and close publication review

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: DOC-0038 through DOC-0042, ADRs 0012–0033, canonical quantity presentation, Calendar collision wording, archive workflow, deterministic Shopping List ordering, roadmap gates, the documentation publication contract, and the developer/operator guide
- Evidence: User statement `Confirmed. Commit all your work.` on 2026-08-10 after reviewing the complete Q1–Q31 decision summary; independent correctness and completeness reviews of the resulting documentation; repository Czech locale, Store UI, translation, and localization-test evidence; successful configured documentation validation and 86-page developer-guide PDF publication checks
- Decision: Accept the recorded Cookbook design as the implementation baseline while keeping every unimplemented capability marked **Planned**. Clarify that `piece` is an internal canonical quantity kind rendered as `ks` in the Czech interface; snapshots retain both the internal kind and frozen localized presentation. Use the edited entry's submitted Serving Count for Calendar edit collisions. Use normalized UTF-8 name bytes plus stable identity for deterministic Store and Ingredient output ordering rather than database or platform collation. Expose archived Recipes and Ingredients through list status filters, require restoration before ordinary editing, and provide visible archive/restore feedback. Treat concrete media upload limits as a named prerequisite and exclude media from the first Slice 2/3 tracers. Defer the Czech User-guide source and PDF target explicitly; the currently configured and verified publication artifact is the English developer/operator guide.
- Rationale: These corrections make the confirmed semantics implementable without changing their substance, prevent English presentation or collation-dependent behavior from leaking into the Czech product, and ensure completion gates cannot pass while approved high-risk invariants remain unproved. The User-guide deferral aligns the specification with the actual configured release instead of claiming a nonexistent artifact.
- Follow-up or review date: Resolve the MIME allowlist, upload byte and dimension limits, corrupt/decode behavior, and temporary-file cleanup before media implementation. Revalidate every Planned behavior through TDD before converting it to current documentation. The live Slice 0 Komodo/MariaDB recreation gate remains incomplete, and documentation version remains `0.3.0` pending explicit User approval.

### DOC-0046 — Publish the canonical glossary and ADR compendium

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation information architecture, configured PDF outputs, `CONTEXT.md` title, developer-guide map, documentation specification, and ADR navigation
- Evidence: User approval on 2026-08-10 to prepare the proposed publication set: include `CONTEXT.md` in the generated developer guide and publish the complete ADR collection separately
- Decision: Append the original `CONTEXT.md` source to the Developer and Operator Guide as its Domain Glossary, avoiding any copied glossary. Add a separate Architecture Decision Record compendium sourced from one short status introduction and every numbered ADR in identifier order. Keep the compendium separate from the main developer guide so complete rationale remains available offline without overwhelming its workflow and operations narrative. Retain ignored generated PDFs as disposable outputs. Use filename-only `file:` links at the two publication entry points as a narrow exception so the rendered companion PDFs link to each other without embedding repository paths.
- Rationale: The developer guide becomes self-contained for canonical vocabulary, while the ADR compendium preserves full decision rationale and a single source of truth without adding 37 decision chapters to the main guide.
- Follow-up or review date: Refresh verification passed on 2026-08-10: tooling doctor reported zero errors and warnings; validation passed with 112 intentional link notices and zero warnings; Dockerized builds verified the 90-page developer guide and 42-page ADR compendium; both A4 PDFs have embedded Unicode fonts, searchable text, no encryption, and working sibling-document actions; every rendered page passed visual inspection; and independent correctness and completeness reviews closed with zero actionable findings. This substantial publication-structure addition would ordinarily justify a minor documentation version, but retain `0.3.0` until the User explicitly approves a version change. Generated PDFs remain ignored and must be rebuilt after any authoritative source change; this refresh was committed before its final clean-HEAD publication build so the embedded commit identifier resolves to a revision containing every rendered source and configuration file.

### DOC-0047 — Document the reusable Store Section tracer

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Current application boundary, Cookbook architecture and data structure, Family authorization evidence, Store workflow, frontend navigation, implementation roadmap, local verification guidance, and developer/operator PDF publication
- Evidence: Store Section migration, model, factory, request, action, controller, routes, Czech translations, Inertia/Vue components, pure PHPUnit feature tests, Vitest SSR rendering tests, and successful PHP, frontend, migration, documentation, and PDF publication gates on 2026-08-10
- Decision: Describe reusable Store Section creation and listing as current behavior. Each Store Section belongs to the authenticated User's Current Family derived through `CurrentFamilyScope`, has an ADR 0007-normalized case-insensitively unique name within that Family, and requires a six-digit hexadecimal colour exposed through an accessible Czech colour picker. The list shows both a colour swatch and its hexadecimal value. Continue to mark Store–Section association and ordering, Store Section deletion, optional icons, media, Ingredients, Store Placement, Shopping List grouping, and Shopping Generation persistence as Planned or unimplemented. Retain documentation version `0.3.0`.
- Rationale: The refresh makes the publication match the verified vertical tracer without implying adjacent Store Section lifecycle or placement capabilities. Treating colour as presentation metadata and showing a textual value preserves the domain boundary and avoids colour-only communication.
- Follow-up or review date: Revalidate ADR 0012 deletion cleanup and ADRs 0015, 0023, and 0028 when their separate increments begin. Keep the Slice 0 live Komodo/MariaDB recreation gate explicitly incomplete; the current refresh provides no production boot, health, persistence, backup, restore, or telemetry evidence.

### DOC-0048 — Document Store Section association and ordering

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Current application boundary, Cookbook architecture and data structure, Family authorization evidence, Store workflow, frontend navigation, Slice 2 roadmap evidence, local verification guidance, and developer/operator PDF publication
- Evidence: Ordered Store–Section association migration and Store order version; attach, detach, and reorder actions, requests, controller, and routes; Czech translations; Inertia/Vue association controls; pure PHPUnit feature tests; Vitest SSR/source-contract tests; and successful PHP, frontend, migration, documentation, and PDF publication gates on 2026-08-10
- Decision: Describe per-Store association, association removal, and optimistic contiguous ordering of reusable Store Sections as current behavior. Derive both Store and Store Section exclusively from the authenticated User's Current Family through `CurrentFamilyScope`; never accept a Family identifier. Require a complete exact Section sequence and matching order version for reorder, lock the Store, and reject stale changes without overwriting them. Removing an association retains the reusable Store Section entity. Continue to mark Store Section entity deletion, optional icons, media, Ingredients, Store Placement, Shopping List grouping, and Shopping Generation persistence as Planned or unimplemented. Retain documentation version `0.3.0`.
- Rationale: The refresh makes the publication match the verified vertical tracer while keeping ADR 0012 deletion cleanup and Ingredient placement outside this increment. The explicit lock, complete-order rewrite, and optimistic version describe the implemented collaboration boundary without broadening Cookbook's dependency direction or the pure Shopping Generation boundary.
- Follow-up or review date: Revalidate ADRs 0012 and 0015 when Store Section entity deletion and Ingredient Store Placement are implemented. Keep the Slice 0 live Komodo/MariaDB recreation gate explicitly incomplete; this refresh provides no production boot, health, persistence, backup, restore, or telemetry evidence.

### DOC-0049 — Document reusable Store Section deletion

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, current application boundary, Cookbook architecture and data structure, Family authorization evidence, Store workflow, frontend navigation, Slice 2 roadmap evidence, local verification guidance, and developer/operator PDF publication
- Evidence: `DeleteStoreSection`; Store Section destroy request, controller, route, and generated Wayfinder binding; association-count query; Czech translation; shadcn-vue destructive AlertDialog; pure PHPUnit deletion feature tests; Vitest source/SSR contract tests; and successful PHP, frontend, documentation, and PDF publication gates on 2026-08-10
- Decision: Describe reusable Store Section deletion as current behavior. Derive the Section exclusively from the authenticated User's Current Family, disclose its Store-association count and the necessarily zero Ingredient-placement count, remove every association, close each affected Store's positions, increment affected order versions, delete the Section, and release its normalized name in one transaction. Keep Ingredient-placement clearing Planned until Ingredients and Store Placement persistence exists. Do not add icons, media, uploads, or Shopping Generation persistence. Retain documentation version `0.3.0`.
- Rationale: The refresh converts only the verified portion of ADR 0012 from Planned to current behavior. It preserves the Cookbook-to-Family-Access dependency direction, leaves Shopping Generation persistence-independent, and avoids inventing placement records or production evidence.
- Follow-up or review date: Extend the same transaction and confirmation count when Ingredient Store Placement is implemented, then revalidate ADRs 0012 and 0015. Keep the Slice 0 live Komodo/MariaDB recreation gate explicitly incomplete; this refresh provides no production boot, health, persistence, backup, restore, or telemetry evidence.

### DOC-0050 — Document the concrete packaged Ingredient tracer

- Date: 2026-08-10
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, current application boundary, Cookbook architecture and data structure, Family authorization evidence, Recipes and Ingredients, frontend navigation, Slice 2 roadmap evidence, local verification guidance, and developer/operator PDF publication
- Evidence: Ingredient migration, model, factory, package-quantity value object, request, action, controller and routes; Czech translations; generated Wayfinder binding; Inertia/Vue form, list, page and navigation; nine-test/100-assertion pure PHPUnit feature suite; three-case exact presentation unit suite; five-test Vitest SSR/source-contract suite; and successful full PHP, frontend, and production-build gates on 2026-08-10
- Decision: Describe concrete packaged Ingredient creation and listing as current behavior. Derive ownership exclusively from the authenticated User's Current Family through `CurrentFamilyScope`; apply ADR 0007 normalized Family-scoped name uniqueness and race conversion; persist either positive canonical grams or positive canonical millilitres, never both, plus an optional positive piece count while requiring at least one quantity; declare `DECIMAL(20,6)`, reject greater input scale, and enforce package combinations with a database check; and derive two-fractional-digit `g`/`kg`, `ml`/`l`, and Czech `ks` presentation without storing an input-unit preference. Keep editing, unit selectors and conversion, description, Store Placement, media, nutrition, alternatives, archival, Recipes, and Shopping Generation unimplemented. Retain documentation version `0.3.0`.
- Rationale: This is the smallest verified Ingredient vertical tracer. It establishes the Family-owned package and presentation boundary without importing future lifecycle, relationship, media, Recipe, or generator behavior, reversing the Cookbook-to-Family-Access dependency, or introducing persistence into Shopping Generation.
- Follow-up or review date: Extend the Ingredient aggregate only through separately tested increments for editing, placement, archival, alternatives, nutrition, description, and media. Keep the Slice 0 live Komodo/MariaDB recreation gate explicitly incomplete; this refresh provides no production boot, health, persistence, backup, restore, or telemetry evidence.

### DOC-0051 — Document the non-media Slice 2 completion

- Date: 2026-08-11
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, current application, architecture, domain model, persistence, Recipes and Ingredients, Stores and shopping order, Nutrition, frontend navigation, and the Slice 2 roadmap
- Evidence: Ingredient edit, Store Placement, archive/restore, direct Alternative, Alternative eligibility, and Nutrition Profile migrations, actions, requests, controllers, Czech Inertia/Vue interfaces, database constraints, and pure PHPUnit/Vitest coverage; full PHP and frontend gates and production build; successful full migration and 79-test/680-assertion Cookbook suite against a disposable MariaDB 11.8 database; and deterministic normalized UTF-8 byte-order coverage on 2026-08-11
- Decision: Describe the approved non-media Slice 2 behavior as current. Ingredient metric input accepts `mg`, `g`, `kg`, `ml`, `cl`, and `l` and normalizes to canonical grams or millilitres; Ingredients support optional description, valid association-backed Store Placement, reversible archival with restore-before-edit, canonical direct symmetric/non-transitive Alternative edges, and one complete optional Nutrition Profile. Store, association, and reusable Section removal apply ADR 0012 placement cleanup. Keep media and the future Recipe Ingredient dependency extension outside this increment, preserve the Cookbook-to-Family-Access dependency direction and persistence-independent Shopping Generation boundary, and retain documentation version `0.3.0`.
- Rationale: The refresh converts only verified persistence and interface behavior from Planned to current while distinguishing stored direct Alternative relationships from future Shopping Generation replacement. It avoids inventing upload or icon policy and does not claim Recipe dependencies before their records exist.
- Follow-up or review date: Approve concrete MIME, byte, dimension, decode/corruption, temporary-file cleanup, and icon-catalogue policies before implementing Store logos, Store Section icons, or Ingredient media. Extend quantity-kind dependency checks when Slice 3 introduces Recipe Ingredients. Keep the Slice 0 live Komodo/MariaDB recreation gate explicitly incomplete; no production boot, health, persistence, backup, restore, or telemetry evidence was created.

### DOC-0052 — Refresh implemented Slices 2 through 7 as version 0.4.0

- Date: 2026-08-11
- Mode: Refresh
- Status: Approved
- Affects: Documentation specification, configured versions, developer/operator guide, Cookbook media lifecycle, Recipes and nutrition, Shopping Generation, Simple Plan, Calendar, Saved Shopping List history, frontend navigation, data structure, security, and implementation roadmap
- Evidence: User-approved media policy and explicit version/publication direction on 2026-08-11; Slice 2 media and icon configuration, services, routes, UI, and tests; Recipe aggregate, discovery, dependency, nutrition, and media implementation; pure Shopping Generation values/services/tests; Simple Plan and Calendar adapters, session state, UI, SQLite/MariaDB tests, and recorded browser evidence; versioned snapshot serializer/decoder, bounded history, UI, tests, and recorded browser evidence; clean Standards and Spec reviews at each implementation fixed point
- Decision: Release the implemented Slice 2 through Slice 7 boundary as documentation version `0.4.0`. Supersede DOC-0038's earlier original-file media detail with the later User policy: accept only JPEG and PNG up to 5 MB, impose no source pixel-dimension limit, reject structurally incomplete or undecodable data, persist only configurable normalized WebP variants with deterministic entity filenames, serialize replacement writers through the entity lock, restore prior variants if replacement fails, retain Recipe/Ingredient media on archive, and remove affected media on hard Store, Store Section, or Family deletion with rollback-aware cleanup. Document the allowlisted Store Section icon catalogue independently of uploaded images. Convert Recipe, nutrition, pure generation, Simple Plan, Calendar, and immutable history from Planned to current behavior. Keep Agent Integration as Planned and keep Slice 0's live Komodo/MariaDB recreation gate explicitly incomplete.
- Rationale: Version 0.4.0 is an approved backward-compatible minor documentation release covering multiple new user-facing workflows and substantial technical sections. The refresh follows verified repository behavior and the User's exact media choices without inventing production deployment, backup, restore, telemetry, or Slice 8 evidence.
- Follow-up or review date: Complete managed validation, Dockerized PDF builds, structural and every-page visual checks, privacy/accessibility review, and independent correctness/completeness reviews before recording publication readiness.

### DOC-0053 — Publish documentation version 0.4.0

- Date: 2026-08-11
- Mode: Refresh
- Status: Approved
- Affects: Version 0.4.0 publication readiness, configured developer/operator and ADR-compendium PDFs, acceptance criteria, and generated-artifact handling
- Evidence: User publication direction on 2026-08-11; managed documentation tool 0.6.5 doctor with zero errors and warnings; configured validation with 85 intentional rendered-link notices and zero warnings; Dockerized clean-commit builds from `e2c4cb8` producing an 84-page Developer and Operator Guide and 42-page ADR compendium; both A4 PDFs carry version 0.4.0 and the rendered source commit, use embedded Unicode fonts and searchable text, contain no encryption, forms, or JavaScript, and expose reciprocal sibling-document actions; final every-page visual inspection with no clipping, overlap, broken glyphs, or unreadable content; source privacy and Mermaid text-alternative checks; and independent correctness and completeness reviews with every actionable finding resolved
- Decision: Publish the configured English Developer and Operator Guide and Architecture Decision Record compendium as documentation version `0.4.0`. Keep their generated PDFs ignored and disposable; the committed Markdown, configuration, specification, decisions, `CONTEXT.md`, and ADR sources remain authoritative. Keep the Czech User guide deferred and unconfigured. Keep Agent Integration as Planned and keep Slice 0's live Komodo/MariaDB recreation gate explicitly incomplete; this publication creates no production boot, health, persistence, backup, restore, or telemetry evidence.
- Rationale: The publication now matches the implemented Slice 1 through Slice 7 boundary, preserves the approved planned/current separation, and passes the repository's complete documentation contract without converting generated artifacts into source files or overstating external operational evidence.
- Follow-up or review date: Rebuild both ignored PDFs after any rendered source or publication-configuration change. Resume the Czech User guide only through a separately approved source and target. Collect the Slice 0 live deployment evidence independently, and refresh the current/planned boundary when Slice 8 is implemented.
