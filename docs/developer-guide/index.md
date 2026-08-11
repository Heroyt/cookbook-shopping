# Developer and Operator Guide

Documentation version: **0.3.0**

This guide is the main technical entry point for developing and operating the cookbook and shopping-planning application. It describes the application that exists in the repository today and the approved domain design that has not yet been implemented.

## Status language

Unmarked statements describe current repository evidence. A blockquote beginning with **Planned** describes approved intent rather than available behavior.

The canonical implementation-free vocabulary is published as the final Domain Glossary chapter in this guide. Architectural trade-offs are published in the companion [Architecture Decision Record compendium](file:architecture-decisions-en.pdf).

## Guide map

1. [Current application](current-application.md) — implemented routes, authentication, frontend shell, and persistence.
2. [Architecture and system boundaries](architecture.md) — current request flow and planned modular-monolith boundaries.
3. [Local development](local-development.md) — workstation and Docker workflows, generated route bindings, and verification.
4. [Domain model](domain-model.md) — module boundaries, ADR map, and capability navigation.
5. [Family access](family-access.md) — implemented provisioning, creation, Current Family, membership, deletion, and account-lifecycle workflows.
6. [Recipes and Ingredients](recipes-ingredients.md) — implemented packaged Ingredient editing, unit normalization, archival, placement, Alternatives, and planned Recipe composition.
7. [Nutrition](nutrition.md) — implemented Ingredient profiles plus planned Recipe calculation, overrides, incomplete profiles, and daily totals.
8. [Stores and shopping order](stores-shopping-order.md) — implemented Store, reusable Section, association/order, and Ingredient placement lifecycles plus planned grouping.
9. [Calendar planning](calendar-planning.md) — Calendar Entries, derived days, Meal Labels, weekly planning, and Simple Plans.
10. [Shopping-list generation](shopping-generation.md) — service boundary, conversions, aggregation, alternatives, and output.
11. [Data structure](data-structure.md) — implemented persistence plus the planned relational model, ownership keys, constraints, and snapshots.
12. [Frontend architecture and navigation](frontend-navigation.md) — current shell and planned responsive workflows.
13. [Infrastructure and deployment](infrastructure-deployment.md) — current Docker/Jenkins delivery path, selected external production profile, and remaining acceptance evidence.
14. [Security and observability](security-observability.md) — implemented controls, Family authorization requirements, secrets, health, and telemetry.
15. [Agent integrations](agent-integrations.md) — planned Agent Credentials, Catalog, atomic Change Sets, API contract, and implementation sequence.
16. [Implementation roadmap](implementation-roadmap.md) — dependency-ordered vertical slices and completion gates.
17. Domain glossary — canonical implementation-free terms and avoided synonyms, published as chapter 18 after this introduction.

## Evidence hierarchy

When sources disagree, use this order:

1. Code, tests, migrations, configuration, and observed behavior for implemented functionality.
2. The [approved documentation specification](../documentation-spec.md), the final Domain Glossary chapter, and the companion [Architecture Decision Record compendium](file:architecture-decisions-en.pdf) for intended behavior.
3. Official third-party documentation for framework and platform contracts.
4. Existing prose as supporting evidence that must still be verified.

The connected Laravel Boost database is explicitly excluded as evidence because the approved [documentation decision](../documentation-decisions.md#doc-0001--exclude-the-connected-laravel-boost-database-from-documentation-evidence) identifies it as belonging to another application.

## Publication verification

The Markdown guide is the source of truth. The repository uses the official
managed documentation tooling recorded in
[DOC-0024](../documentation-decisions.md#doc-0024--upgrade-managed-documentation-tooling-to-065).
Run its doctor, validation, PDF build, and visual inspection gates for each
revision. The generated PDF remains an ignored build artifact and must be
regenerated from the Markdown sources for publication.
