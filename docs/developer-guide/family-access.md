# Family access

Family Access has a narrow implemented tracer. An authenticated User can open the Family creation page and create a named Family. The transaction also creates that User's first ordinary, roleless Family Membership. The database rejects a duplicate membership for the same User and Family. Current Family selection, member management, Family deletion, and Family-owned authorization are not implemented. See [Current application](current-application.md) for current behavior and [`CONTEXT.md`](../../CONTEXT.md) for canonical terms.

The ownership decision is recorded in [ADR 0003](../adr/0003-scope-domain-data-to-families.md). The planned relational constraints are described in [Data structure](data-structure.md), and Slice 1 of the [Implementation roadmap](implementation-roadmap.md) orders the work.

## Ownership boundary

> **Planned**
>
> A Family is the exclusive owner of its Cookbook, Recipe Tags, Ingredients, Stores, Store Sections, Calendar Entries, and Saved Shopping Lists. Each owned query and command resolves one Current Family and rejects identifiers belonging to any other Family. Shopping List generation and cross-record relationships never span Families.
>
> A User may belong to multiple Families through roleless Family Memberships. Membership grants equal rights to all Family data; there is no Owner or administrator role in the MVP. Creating a Family creates its first ordinary membership automatically.

## Membership workflow

Family creation accepts a required name of at most 255 characters and normalizes repeated whitespace before persistence. It creates the Family and initial Family Membership atomically, then returns the User to the dashboard with success feedback. The current sidebar's Families entry opens this creation workflow; it is not yet a Family list or switcher.

> **Planned**
>
> Any Family member can add an already registered User by email, remove another membership, or leave the Family. Add-by-email creates membership immediately and has no invitation, pending, or email-delivery state. An unknown email returns a neutral not-found result.
>
> A Family must retain at least one member. Removing or leaving the final membership is rejected; the member must explicitly delete the Family instead. Any member may delete the Family and its owned data after strong confirmation by entering the Family name.

## Current Family

> **Planned**
>
> A User works inside one explicitly selected Current Family at a time. A family switcher exposes only the User's memberships and remembers the last valid selection. Switching changes query and command scope; it does not transfer, merge, or copy records.
>
> Current Family is a preference rather than an ownership field. If its membership disappears, the application clears it or selects another valid membership before accepting Family-owned requests. Authorization must derive from current membership on every request rather than trusting a route parameter, client-provided identifier, cookie, or stale preference alone.

## Account lifecycle

Account deletion is blocked while the User is the final member of any Family. The validation response leaves the User authenticated and preserves every affected Family and membership. If every Family has another member, deletion succeeds and the database removes only the departing User's memberships while retaining the Families and their other members.

> **Planned**
>
> Define how additional Users become registered. Adding a member by email intentionally accepts existing Users only, while the current application has no self-registration workflow. Account provisioning may be resolved separately, but Family onboarding must not imply that entering an unknown email creates an account.

## Authorization verification

Focused feature tests cover authentication on both Family-creation endpoints, creation of exactly one initial membership, name validation and normalization, duplicate-membership database enforcement, final-member account-deletion rejection, successful deletion when every Family retains another member, and atomic rejection across multiple Families.

> **Planned**
>
> Test every Family-owned aggregate with at least two Users and two Families. Cover allowed access by each equal member, rejected cross-Family reads and writes, stale Current Family selection, duplicate membership, member removal, final-member protection, and destructive Family confirmation. The [Security and observability](security-observability.md) chapter provides the wider control baseline.
