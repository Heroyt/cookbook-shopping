# Frontend Architecture and Navigation

## Current frontend shell

The implemented frontend is an Inertia 3 and Vue 3 application. [`resources/js/app.ts`](../../resources/js/app.ts) resolves server-selected pages, assigns layouts by page name, initializes appearance and flash-toast behavior, and displays Inertia visit progress. The public welcome page has no application layout, authentication pages use the authentication layout, settings pages combine the application and settings layouts, and other pages use the main application layout.

The default application layout currently uses a responsive, collapsible sidebar. It provides a header with breadcrumbs and a global toast host; the main navigation contains the placeholder Dashboard plus Families and Stores management entries. A global Current Family switcher lists only the authenticated User's memberships. Profile, security, appearance, and logout actions are reached through the User menu. The settings layout supplies its own Profile, Security, and Appearance navigation. See the [application layout](../../resources/js/layouts/AppLayout.vue), [sidebar layout](../../resources/js/layouts/app/AppSidebarLayout.vue), [sidebar](../../resources/js/components/AppSidebar.vue), [Family switcher](../../resources/js/components/families/FamilySwitcher.vue), [User navigation](../../resources/js/components/NavUser.vue), and [settings layout](../../resources/js/layouts/settings/Layout.vue).

An alternative header layout is also present. It renders desktop navigation, a mobile sheet, breadcrumbs, and a search icon, but it is not the default application layout and the search button has no implemented search behavior. See the [header layout](../../resources/js/layouts/app/AppHeaderLayout.vue) and [header component](../../resources/js/components/AppHeader.vue).

Navigation uses Inertia `Link` components and generated Wayfinder route functions. Active-link behavior comes from the shared URL composable. Family and Store creation and management use Inertia forms or router visits with generated Wayfinder actions. The pages compose shadcn-vue Cards, Fields, Inputs, Tables, Empty states, AlertDialog, and Dialog primitives. Validation errors remain associated with inputs, and forms preserve user input on recoverable validation failures. Generated modules under `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` are build artifacts and must be regenerated rather than edited by hand.

Every user-facing string is Czech, including page titles, navigation, form copy, placeholders, dialogs, toasts, loading and empty states, and screen-reader-only labels. Backend validation, authentication, and package feedback is supplied through `lang/cs`. Keep TypeScript and Vue identifiers in English, but treat newly introduced English interface copy as a defect and extend the localization source-contract coverage when adding a workflow.

The Stores page is the only Cookbook navigation and currently supports creation, listing, renaming through a Dialog, and deletion through a consequence-stating AlertDialog. No Store Section maintenance, Ingredient maintenance, Recipe search, weekly planner, Simple Plan, or Shopping List interface exists yet. Those workflows described below are approved intent from the final Domain Glossary chapter, not current behavior.

## Planned application navigation

> **Planned**
>
> Keep one responsive Inertia application rather than separate desktop and mobile clients. Desktop use should make Recipe, Ingredient, Store, and Store Section maintenance efficient. Mobile use should make weekly planning, date selection, and generated Shopping List reading equally complete; neither viewport may be treated as an unsupported fallback.
>
> Add primary destinations for Recipes, Ingredients, Stores, the weekly planner, Simple Plan, and Shopping List history. Store Section management belongs with Store management because Sections are reusable entities whose traversal position is configured per Store. Recipe Tags belong with Cookbook maintenance rather than becoming a competing top-level taxonomy.
>
> Preserve account settings in the User menu. Add Current Family Agent Access and Agent Change History destinations when Agent Integration is delivered; these are Family management surfaces, not global account settings. Use generated Wayfinder functions for application routes and Inertia navigation for page transitions. Domain calculations and authorization remain on the Laravel side; navigation state must never be treated as proof that a record belongs to the Current Family.

## Planned management workflows

> **Planned**
>
> Recipe, Ingredient, Store, and Store Section screens should favor desktop density while remaining fully operable on smaller screens. List pages need clear create and edit paths, visible archived state where relevant, and Family-scoped empty states. Forms must preserve the domain's validation rules rather than allowing incomplete records that later fail generation.
>
> Ingredient maintenance presents one concrete purchasable package. It accepts either positive weight input or positive volume input, never both, may additionally accept a positive piece count, and requires at least one quantity. Explicit input units normalize to persisted grams or millilitres without retaining a preference; display selects `g`/`kg` or `ml`/`l` using the 1000 threshold. Pieces have no selectable unit and render as `ks` in the Czech interface. The form also supports optional description and photo, optional Store Placement, optional Nutrition Profile, and direct Alternative Ingredients. Store Placement can select at most one Store and optionally one Section associated with that Store. Section order is edited in the context of a Store; deleting a Store or removing a Section association must make the resulting placement change explicit before confirmation.
>
> Recipe maintenance supports its unique name, positive base Serving Count, one ordered ingredient list, ordered Recipe Steps, Recipe Notes, tags, optional cover photo and source URL, preparation and cooking durations, and an optional complete per-serving Nutrition Override. Recipe Ingredient rows may repeat the same Ingredient and use fractional quantities, but they do not add preparation-note or ingredient-group fields. Preparation detail belongs in Recipe Step text. The form submits the complete aggregate with its version; a stale edit is rejected with fresh state for review rather than overwriting another member's changes.
>
> Recipe Tag deletion requires consequence confirmation, detaches the Tag from every Recipe, leaves Recipes otherwise unchanged, and releases the Tag name for reuse.

## Planned Recipe discovery

> **Planned**
>
> Cookbook search uses one query across Recipe names, Recipe Tags, and referenced Ingredient names. Present results in visibly separate layers so the reason for inclusion is apparent.
>
> A Recipe that matches more than one layer appears only once, in its strongest matching layer. Show reason indicators for every match, such as a name match, matching Recipe Tag, or matching Ingredient, rather than duplicating the Recipe card. The interface must not imply a match source that the server did not return.
>
> Search and filtering remain Family-scoped and exclude archived Recipes from new planning selections. Recipe and Ingredient lists provide `Active`, `Archived`, and `All` status filters rather than a separate global archive destination. Archived rows are read-only apart from **Restore**; editing requires restoration first. Archive actions state the consequence before confirmation, while restoration needs no destructive confirmation and returns visible success feedback.

## Planned weekly planner

> **Planned**
>
> The primary Calendar interface is weekly. It derives each Calendar Day from persisted Calendar Entries and must not create records for empty dates. Within each day, show the five fixed Meal Labels in their conventional order—`snídaně`, `dopolední svačina`, `oběd`, `odpolední svačina`, and `večeře`—followed by unlabeled entries. Each label may contain any number of Recipes, and order within a label has no meaning.
>
> Adding or editing an entry selects a saved Recipe and positive fractional Serving Count plus an optional Meal Label. Creating a duplicate Recipe for the same date and label combination atomically adds the submitted Serving Count to the existing entry and shows an explicit notice with the resulting total rather than rendering another row. Editing an entry onto another existing combination adds the edited entry's submitted Serving Count to the target and removes the source with the same notice. Every accepted request applies, including a repeated transport request. Archived Recipes remain visible in existing Calendar Entries; their existing entries may change Serving Count or be deleted, but date, Meal Label, or Recipe changes require restoration first.
>
> Desktop presentation may use the available width to compare days. Mobile presentation must preserve readable day and Meal Label groupings instead of compressing seven columns beyond use. Both presentations expose the same week navigation, entry management, per-Recipe nutrition, and daily nutrition totals, including Incomplete Nutrition Profile warnings.
>
> Shopping generation accepts an arbitrary set of Calendar dates. A range-selection interaction may accelerate selection, but the final state must allow non-contiguous dates and clearly show which dates will contribute before generation.

## Planned Simple Plan and generation flow

> **Planned**
>
> Simple Plan is a temporary, unordered collection with at most one Recipe Selection per Recipe. Adding an existing Recipe increases its Serving Count and shows the resulting total. It allows positive fractional Serving Counts and generates through the same Shopping Generation service used by Calendar selection. Leaving or completing the flow must not imply that the Simple Plan was saved.
>
> Both Calendar and Simple Plan flows resolve their selections inside the Current Family, then navigate to the same generated Shopping List presentation. The frontend sends intent and displays the result; it must not reproduce serving scaling, unit conversion, aggregation, package rounding, nutrition, or alternative-substitution calculations.

## Planned Shopping List presentation

> **Planned**
>
> Show each Shopping List Line's whole package Purchase Quantity as the primary instruction. Secondary details show Required Quantity, purchased quantity, and Surplus in every canonical quantity kind configured for the final Ingredient, rendered using explicit display units where helpful. A collapsible contribution breakdown identifies the source Recipes without overwhelming the primary list.
>
> Group lines by Store and then by the Store's Section traversal order. Put Store-assigned lines without a Section after that Store's Sections and Ingredients without Store Placement after all Store groups. Ingredient names use the application-normalized UTF-8 byte key and stable identity comparator within a Section; the interface must not imply locale-aware dictionary sorting or that Store group order has domain meaning.
>
> Alternative selection offers only direct active Alternative Ingredients whose package configures every canonical quantity kind used by the replaced Recipe contributions. Explicit metric input and display units have already normalized to grams or millilitres, while pieces remain a count; an Alternative missing any required kind is not selectable, and there is no manual quantity fallback. Permit one choice per originally generated Ingredient rather than chaining substitutions from merged output. A valid choice recalculates and globally re-aggregates the result, moves the line to the Alternative's Store Placement, and preserves separately editable or reversible source-choice provenance.
>
> Derive weight display as grams below 1000 and kilograms from 1000, and volume as millilitres below 1000 and litres from 1000, without retaining the User's input-unit preference. Present required, purchased, and Surplus amounts with at most two fractional digits, strip trailing zeroes, and visibly distinguish a rounded approximation from an exact value. Purchase package counts remain whole numbers. The frontend renders the grouping and display values returned by Shopping Generation rather than recalculating them.
>
> If Shopping Generation returns Calculation Problems, show no Shopping List Lines. Present every affected Recipe, Ingredient, quantity, unit, and reason in one accessible problem state, link each item to the relevant Recipe or Ingredient editor, preserve the Calendar Selection or Simple Plan input, and announce that corrections are required before retrying. Returning from a correction must allow the preserved selection to be regenerated explicitly; the frontend must not silently retry or hide later problems.
>
> Generation is transient. Provide an explicit save action for the read-only timestamped history snapshot, distinguish the transient result from a successfully saved snapshot, and disable the control while its current request is processing. Every accepted request creates a new snapshot, including a later retry or an identical save. The generated page is intended to be copied or rewritten into another checklist application; do not add checked-item state or imply external synchronization in the MVP.

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
> Add rendered-component Vitest coverage when the frontend gains a DOM test harness, plus focused browser-level coverage for complete navigation workflows. At minimum, verify Current Family switching and member-management dialog behavior; Store rename success and validation; Store deletion confirmation text, cancellation without deletion, processing lock, success and failure outcomes, focus restoration, keyboard operation, and toast announcement; layered search deduplication and reasons; responsive access to all primary destinations; Calendar duplicate accumulation and arbitrary-date selection; Simple Plan transience; generated grouping and contribution disclosure; saved-state feedback; and accessible names.
>
> Keep package and nutrition arithmetic out of Vue tests. Those values come from the backend's tested domain and application services; frontend tests verify that the supplied states and quantities are represented accurately.
