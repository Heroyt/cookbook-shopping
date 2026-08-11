# Frontend Architecture and Navigation

## Current frontend shell

The frontend is an Inertia 3 and Vue 3 application. [`resources/js/app.ts`](../../resources/js/app.ts) resolves server-selected pages, assigns layouts, initializes appearance and flash-toast behavior, and displays visit progress. Authentication pages use the authentication layout; settings pages nest within the application layout; the placeholder Dashboard and domain pages use the responsive main layout. The root route redirects guests to login and authenticated Users to the Dashboard.

The sidebar provides Families, Recipes, Ingredients, Stores, Calendar, Simple Plan, and Shopping List history plus a Current Family switcher containing only live memberships. Profile, security, appearance, and logout remain in the User menu. Navigation uses Inertia `Link`, `Form`, or router visits with generated Wayfinder actions/routes; generated modules are build artifacts and must never be hand-edited.

Vue components use `<script setup lang="ts">`, strict TypeScript, Tailwind utilities, and installed shadcn-vue primitives. Every user-facing string is Czech, including metadata, navigation, fields, feedback, loading/empty states, dialogs, toasts, and accessible labels. Backend validation and package copy comes from `lang/cs`.

## Cookbook management

Stores and Ingredients use responsive list/card pages with create and edit Dialogs, consequence-stating AlertDialogs, associated inline actions, and protected image previews/uploads. Store management includes colour and icon selection, Store–Section association/order, stale-order feedback, and placement counts. Ingredient management includes canonical metric inputs, dependent placement, nutrition, direct Alternatives, image, archive/filter/restore, and restore-before-edit behavior.

Recipe management uses create/edit Dialogs for the complete aggregate, dynamic repeatable Ingredient and Step rows with stable keys, metadata, Tags, optional nutrition override, image, status filters, and layered search. The query projection returns each result once with every name/Tag/Ingredient reason. Archived rows expose restoration rather than edit.

## Planning and generation

The responsive Calendar page groups one visible week into days and Meal Labels without making a seven-column desktop grid mandatory on small screens. Generated Wayfinder forms create, edit, and delete entries; duplicate and collision results are announced with exact totals. Dialogs close on success and restore focus. Archived-Recipe entries expose only Serving Count editing and deletion.

Calendar Selection accepts non-contiguous dates, including manually selected dates outside the visible week. The selection persists through correction flows. Simple Plan presents one transient row per Recipe, accumulates repeated additions, and never implies persistence.

Both sources render the same Shopping List presentation. Whole package count is primary; grouped required, purchased, surplus, contribution, and Alternative details are secondary. Every Alternative form displays Czech stale/invalid field errors. Calculation Problems use stable occurrence identifiers, present every affected line, and link to the exact active or archived Recipe/Ingredient management state. A failed retry does not leave a stale generated result under changed provenance.

## Saved history

A complete generated result exposes a distinct save action. Successful feedback differentiates transient generation from an immutable save. History lists bounded newest-first cards with generated Previous/Next navigation, opens timestamped read-only detail without consulting live records, and shows an intentional Czech unavailable state for unsupported/corrupt payloads.

Deletion uses a consequence-stating AlertDialog. Cancellation restores focus to the invoking timestamp-labelled trigger. Confirmation removes the card and moves focus to the visible history heading with an explicit focus ring rather than targeting a removed control.

## Accessibility and state behavior

The composed workflows use semantic landmarks and headings, associated Fields and errors, descriptive names, visible keyboard focus, exposed current-page/filter state, and non-colour-only status. Disabled shadcn Fields expose `data-disabled`; dialogs and alerts use the library primitives but also wire success closure and stable focus recovery at page level.

Forms preserve recoverable input and show Czech inline errors. Current Family failure stops the operation without exposing foreign records. Generation, Alternative, save, and destructive outcomes use visible text plus the existing toast/live-region infrastructure. Buttons lock only for their current processing request.

## Verification

Laravel feature tests cover the HTTP/Inertia boundary and exact presented props for every Family, Cookbook, Calendar, generation, and history workflow. Vitest covers generated action usage, SSR/presentation contracts, stable repeatable-row keys, errors, correction links, dialog success wiring, cursor links, and focus orchestration. Pure arithmetic stays out of Vue tests.

Recorded fresh-bundle browser runs additionally exercise the complete Simple Plan and Calendar generation paths and the save → history → read-only detail → delete flow. They verify responsive access, keyboard/dialog behavior, cancellation focus, post-delete heading focus, visible feedback, and no browser console warnings/errors. These local browser observations are application evidence, not production deployment evidence.

> **Planned**
>
> Add responsive Current Family Agent Access and Agent Change History destinations only when Slice 8 is implemented. They must use the same Czech, Wayfinder, shadcn-vue, focus, and Current-Family conventions; no placeholder Agent API UI exists today.
