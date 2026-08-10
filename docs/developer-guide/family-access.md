# Family access

Family Access now implements the complete collaboration workflow needed before Family-owned domain records are introduced. An authenticated User can create a Family, switch among their Families, add an existing User by email, leave or remove a member, and delete the Current Family after exact-name confirmation. Every Family Membership is ordinary and roleless. See [Current application](current-application.md) for the wider implemented boundary and the final Domain Glossary chapter for canonical terms.

The ownership decision is recorded in [ADR 0003](../adr/0003-scope-domain-data-to-families.md). The relational constraints are described in [Data structure](data-structure.md), and the Store and Store Section tracers prove the reusable Family-owned authorization boundary in the [Implementation roadmap](implementation-roadmap.md).

## Account provisioning

Public self-registration remains disabled. An operator creates an account interactively from the application environment:

```bash
php artisan user:create user@example.com "Example User"
```

The command prompts for a password and confirmation without placing the password in shell history, normalizes the email and name, applies the same password rules as the application, and rejects an existing email case-insensitively. Its output never includes the password. The development seeder remains test-fixture convenience rather than an account-provisioning workflow.

## Membership workflow

Family creation accepts a required name of at most 255 characters and normalizes repeated whitespace. In one transaction it locks the User, creates the Family and first Family Membership, and selects the new Family as Current Family. The Families page lists only the Current Family's members and provides the creation and management controls.

Any member can add an already registered User by email, remove another membership, or leave. Add-by-email is immediate; there is no invitation, pending, or email-delivery state. An unknown email or an email already belonging to the Current Family returns a validation error and does not create a User.

A Family must retain at least one member. Removing or leaving the final membership is rejected; the member must explicitly delete the Family instead. Any member may delete the Current Family after entering its exact, case-sensitive name. Database cascades remove its memberships, while each affected User's Current Family preference becomes null; the acting User then falls back to another membership when one exists. Future Family-owned tables must add matching cascade behavior before their deletion workflow is complete.

Membership mutations resolve the target Family from the authenticated User's validated Current Family rather than accepting a Family identifier in the route. They lock affected User rows in identifier order before the Family row. This ordering coordinates add, remove, leave, delete, Family creation, and account deletion without granting special rights to the creator.

## Current Family

`users.current_family_id` stores the last selected Family as a nullable preference. The sidebar switcher shows only the authenticated User's memberships. Selection is accepted only when the User is currently a member.

The server validates the preference on every authenticated Inertia request. A stale selection is replaced with the lowest-identifier remaining membership or cleared when none remains. Removing a membership and deleting a Family apply the same fallback rule. The preference is not an ownership field and is never sufficient authorization by itself.

`CurrentFamilyScope` is the reusable authorization interface for Family-owned modules. It requires the membership-validated Current Family and applies it to Eloquent queries through the owned model's `family` relationship. Cookbook uses it for Store listing, creation, rename resolution, and deletion resolution; Store Section listing and creation; and resolution of both records for association, removal, and reorder operations. Route parameters, client-provided Family identifiers, cookies, and stale preferences do not select ownership.

## Account lifecycle

Account deletion is blocked while the User is the final member of any Family. The validation response leaves the User authenticated and preserves every affected Family and membership. The management UI tells the User to add another member or delete the Family first. If every Family has another member, deletion succeeds and the database removes only the departing User's memberships while retaining the Families and their other members.

## Authorization verification

Focused PHPUnit tests cover operator provisioning, hidden-password handling, case-insensitive duplicate email rejection, authentication, atomic Family creation, name validation, Current Family selection and stale fallback, add-by-email, duplicate and unknown member rejection, Current-Family-only listing, leave and removal fallback, final-member protection, exact destructive confirmation, cross-Family membership isolation, and account-deletion behavior. A focused Vitest contract verifies that the Vue forms and switcher call generated Wayfinder actions and that the sidebar exposes the Current Family selector.

Store and Store Section feature tests use multiple Users and two Families to prove equal member access, Current-Family-only reads and writes, foreign-Family isolation, and that a client-supplied cross-Family ownership identifier is ignored rather than redirecting a write. The Store Section tests additionally prove scoped normalized uniqueness and database-race conversion to a field error. Association tests resolve both Store and Section inside the Current Family and reject cross-Family attach, removal, and reorder targets. Every later Family-owned aggregate must reuse the same scope and add record-specific cross-Family tests. The [Security and observability](security-observability.md) chapter provides the wider control baseline.
