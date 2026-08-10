# Frontend Architecture and Navigation

## Current frontend shell

The implemented frontend is an Inertia 3 and Vue 3 application. [`resources/js/app.ts`](../../resources/js/app.ts) resolves server-selected pages, assigns layouts by page name, initializes appearance and flash-toast behavior, and displays Inertia visit progress. The public welcome page has no application layout, authentication pages use the authentication layout, settings pages combine the application and settings layouts, and other pages use the main application layout.

The default application layout currently uses a responsive, collapsible sidebar. It provides a header with breadcrumbs and a global toast host; the main navigation contains the placeholder Dashboard plus Families and Stores management entries. A global Current Family switcher lists only the authenticated User's memberships. Profile, security, appearance, and logout actions are reached through the User menu. The settings layout supplies its own Profile, Security, and Appearance navigation. See the [application layout](../../resources/js/layouts/AppLayout.vue), [sidebar layout](../../resources/js/layouts/app/AppSidebarLayout.vue), [sidebar](../../resources/js/components/AppSidebar.vue), [Family switcher](../../resources/js/components/families/FamilySwitcher.vue), [User navigation](../../resources/js/components/NavUser.vue), and [settings layout](../../resources/js/layouts/settings/Layout.vue).

An alternative header layout is also present. It renders desktop navigation, a mobile sheet, breadcrumbs, and a search icon, but it is not the default application layout and the search button has no implemented search behavior. See the [header layout](../../resources/js/layouts/app/AppHeaderLayout.vue) and [header component](../../resources/js/components/AppHeader.vue).

Navigation uses Inertia `Link` components and generated Wayfinder route functions. Active-link behavior comes from the shared URL composable. Family and Store creation and management use Inertia forms or router visits with generated Wayfinder actions. The pages compose shadcn-vue Cards, Fields, Inputs, Tables, Empty states, AlertDialog, and Dialog primitives. Validation errors remain associated with inputs, and forms preserve user input on recoverable validation failures. Generated modules under `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` are build artifacts and must be regenerated rather than edited by hand.

The Stores page is the only Cookbook navigation and currently supports creation, listing, renaming through a Dialog, and deletion through a consequence-stating AlertDialog. No Store Section maintenance, Ingredient maintenance, Recipe search, weekly planner, Simple Plan, or Shopping List interface exists yet. Those workflows described below are approved intent from the [domain glossary](../../CONTEXT.md), not current behavior.

## Planned application navigation

> **Planned**
>
> Keep one responsive Inertia application rather than separate desktop and mobile clients. Desktop use should make Recipe, Ingredient, Store, and Store Section maintenance efficient. Mobile use should make weekly planning, date selection, and generated Shopping List reading equally complete; neither viewport may be treated as an unsupported fallback.
>
> Add primary destinations for Recipes, Ingredients, Stores, the weekly planner, Simple Plan, and Shopping List history. Store Section management belongs with Store management because Sections are reusable entities whose traversal position is configured per Store. Recipe Tags belong with Cookbook maintenance rather than becoming a competing top-level taxonomy.
>
> Preserve account settings in the User menu. Use generated Wayfinder functions for application routes and Inertia navigation for page transitions. Domain calculations and authorization remain on the Laravel side; navigation state must never be treated as proof that a record belongs to the Current Family.

## Planned management workflows

> **Planned**
>
> Recipe, Ingredient, Store, and Store Section screens should favor desktop density while remaining fully operable on smaller screens. List pages need clear create and edit paths, visible archived state where relevant, and Family-scoped empty states. Forms must preserve the domain's validation rules rather than allowing incomplete records that later fail generation.
>
> Ingredient maintenance presents one concrete purchasable package. It must allow at least one positive configured unit quantity, optional description and photo, optional Store Placement, optional Nutrition Profile, and direct Alternative Ingredients. Store Placement can select at most one Store and optionally one Section associated with that Store. Section order is edited in the context of a Store; deleting a Store or removing a Section association must make the resulting placement change explicit before confirmation.
>
> Recipe maintenance supports its unique name, positive base Serving Count, one ordered ingredient list, ordered Recipe Steps, Recipe Notes, tags, optional cover photo and source URL, preparation and cooking durations, and an optional complete per-serving Nutrition Override. Recipe Ingredient rows may repeat the same Ingredient and use fractional quantities, but they do not add preparation-note or ingredient-group fields. Preparation detail belongs in Recipe Step text.

## Planned Recipe discovery

> **Planned**
>
> Cookbook search uses one query across Recipe names, Recipe Tags, and referenced Ingredient names. Present results in visibly separate layers so the reason for inclusion is apparent.
>
> A Recipe that matches more than one layer appears only once, in its strongest matching layer. Show reason indicators for every match, such as a name match, matching Recipe Tag, or matching Ingredient, rather than duplicating the Recipe card. The interface must not imply a match source that the server did not return.
>
> Search and filtering remain Family-scoped and exclude archived Recipes from new planning selections. Archived Recipes may still be discoverable in an explicitly historical or maintenance context, but the approved model does not require a separate global archive destination.

## Planned weekly planner

> **Planned**
>
> The primary Calendar interface is weekly. It derives each Calendar Day from persisted Calendar Entries and must not create records for empty dates. Within each day, show the five fixed Meal Labels in their conventional order—`snídaně`, `dopolední svačina`, `oběd`, `odpolední svačina`, and `večeře`—followed by unlabeled entries. Each label may contain any number of Recipes, and order within a label has no meaning.
>
> Adding or editing an entry selects a saved Recipe and positive fractional Serving Count plus an optional Meal Label. Prevent a duplicate Recipe for the same date and label combination by updating or rejecting the existing entry rather than rendering indistinguishable duplicates. Archived Recipes remain visible in existing Calendar Entries but are unavailable for new entries.
>
> Desktop presentation may use the available width to compare days. Mobile presentation must preserve readable day and Meal Label groupings instead of compressing seven columns beyond use. Both presentations expose the same week navigation, entry management, per-Recipe nutrition, and daily nutrition totals, including Incomplete Nutrition Profile warnings.
>
> Shopping generation accepts an arbitrary set of Calendar dates. A range-selection interaction may accelerate selection, but the final state must allow non-contiguous dates and clearly show which dates will contribute before generation.

## Planned Simple Plan and generation flow

> **Planned**
>
> Simple Plan is a temporary, unordered collection with at most one Recipe Selection per Recipe. It allows a positive fractional Serving Count and generates through the same Shopping Generation service used by Calendar selection. Leaving or completing the flow must not imply that the Simple Plan was saved.
>
> Both Calendar and Simple Plan flows resolve their selections inside the Current Family, then navigate to the same generated Shopping List presentation. The frontend sends intent and displays the result; it must not reproduce serving scaling, unit conversion, aggregation, package rounding, nutrition, or alternative-substitution calculations.

## Planned Shopping List presentation

> **Planned**
>
> Show each Shopping List Line's whole package Purchase Quantity as the primary instruction. Secondary details show Required Quantity, purchased quantity, and Surplus in every unit configured for the final Ingredient. A collapsible contribution breakdown identifies the source Recipes without overwhelming the primary list.
>
> Group lines by Store and then by the Store's Section traversal order. Put Store-assigned lines without a Section after that Store's Sections and Ingredients without Store Placement after all Store groups. Ingredient names sort alphabetically within a Section; the interface must not imply that Store group order has domain meaning.
>
> Alternative selection offers only direct Alternative Ingredients. A compatible choice recalculates and globally re-aggregates the result and moves the line to the alternative's Store Placement. An incompatible choice requires an explicit manual replacement quantity rather than silently guessing a conversion.
>
> Generation is transient. Provide an explicit save action for the read-only timestamped history snapshot, and distinguish the transient result from a successfully saved snapshot. The generated page is intended to be copied or rewritten into another checklist application; do not add checked-item state or imply external synchronization in the MVP.

## Planned accessibility and state behavior

> **Planned**
>
> Use semantic navigation landmarks, headings that preserve page hierarchy, programmatically associated form labels, descriptive control names, visible keyboard focus, and an exposed current-page state. Icon-only controls need accessible names; colour must not be the only signal for a Store Section, match reason, selection, nutrition warning, archive state, error, or success.
>
> All navigation, Family switching, Recipe search layers, Calendar date selection, dialogs, alternative selection, and expandable contribution details must be operable by keyboard. Reuse the existing accessible UI primitives where appropriate, but verify focus trapping, focus restoration, escape behavior, and screen-reader announcements in the composed workflow rather than assuming the primitive guarantees the entire page.
>
> Each asynchronous screen needs a deliberate loading, empty, validation-error, authorization-error, and retry state. When Inertia deferred props are used, show a meaningful skeleton or progress state. Preserve user input after recoverable validation failures. A Family-scope failure must stop the operation and return the User to a valid Family context without exposing the inaccessible record.
>
> Announce the outcome of generation, saving, alternatives, and destructive actions through visible text and an appropriate live region or the existing toast infrastructure. Strong destructive confirmation is required before Family deletion. Ordinary archive, placement-clearing, and history-deletion confirmations must state the consequence without presenting the history as an audit log.

## Frontend verification expectations

Laravel feature tests cover the Family and Store HTTP/Inertia boundaries, validation, transaction behavior, Current Family fallback, member lifecycle, success feedback, persisted results, equal Store create/rename/delete rights, and cross-Family Store isolation. They call the Store delete endpoint directly and therefore do not exercise the browser confirmation. Focused Vitest source-contract tests inspect typed Family and Store create/rename/delete action wiring, the Store AlertDialog composition and destructive action, page composition, navigation, and account-resolution messaging without rendering a browser DOM. The full frontend suite and production build verify that the composed Vue components type-check and bundle. Confirmation text and cancellation, processing and failure states, focus restoration, keyboard behavior, and toast announcement are not yet exercised in a rendered component or browser workflow test.

> **Planned**
>
> Add rendered-component Vitest coverage when the frontend gains a DOM test harness, plus focused browser-level coverage for complete navigation workflows. At minimum, verify Current Family switching and member-management dialog behavior; Store rename success and validation; Store deletion confirmation text, cancellation without deletion, processing lock, success and failure outcomes, focus restoration, keyboard operation, and toast announcement; layered search deduplication and reasons; responsive access to all primary destinations; Calendar duplicate prevention and arbitrary-date selection; Simple Plan transience; generated grouping and contribution disclosure; saved-state feedback; and accessible names.
>
> Keep package and nutrition arithmetic out of Vue tests. Those values come from the backend's tested domain and application services; frontend tests verify that the supplied states and quantities are represented accurately.
