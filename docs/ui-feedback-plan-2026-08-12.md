# UI feedback implementation plan

Date: 2026-08-12

This plan records the supplied UI findings, their dependencies, and the order in which they will be addressed. It is the working reference for implementation and the later `grill-with-docs` decision rounds. Application-documentation verification, rendering, and publication are intentionally deferred.

## Delivery principles

- Start with independent, low-risk improvements that do not require unresolved product decisions.
- Add or update the narrowest PHPUnit or Vitest coverage for every implementation change.
- After each meaningful change, run focused tests and relevant static checks before continuing.
- Exercise each completed slice in the local Docker environment and in a real browser, including console checks and the affected interaction.
- Preserve the established domain model unless a decision is explicitly changed during the grill.
- Commit focused logical slices using the repository's gitmoji subject convention.

## Workstream A: independent improvements

These findings can be implemented before the design interview.

### A1. Ingredient form and presentation

- [x] **Finding 6 — optional package piece count.** The ingredient create/edit form now labels package piece count as optional and explains that metric-only packages should leave it blank. `piece_count` remains nullable; no artificial value of one is persisted.
- [x] **Finding 7 — concise decimal input values.** Editable Ingredient package/nutrition values, Recipe serving/ingredient/nutrition values, and Calendar Serving Counts now trim unnecessary trailing zeroes while preserving the stored precision and up to six decimal places.
- [x] **Finding 13 — section colour ownership.** Store Section colour editing has been removed from ingredient create/edit. Colour and icon management remain on Store Section management only.
- [ ] **Finding 4 — richer placement options.** Show a small Store logo in Store options and Store Section colour/icon in Section options. This depends on exposing the existing protected media/icon metadata in the ingredient-management projection but not on relation-search architecture.
- [ ] **Finding 5 — ingredient image drop target.** Make the entire ingredient image area, including an existing preview, clickable and drag-and-drop capable. Accept one JPEG, PNG, or static WebP source while retaining the existing stored image until the explicit upload succeeds. Ingredient creation commits first, keeps the form open, and then performs the independent retryable upload.

### A2. Navigation

- [x] **Finding 14 — agent pages in profile menu.** **Přístupy agentů** and **Historie změn agentů** have been removed from primary navigation and placed beneath **Nastavení** in the bottom-left User menu. They remain hidden until a Current Family exists and use generated Wayfinder destinations.

### A3. Agent credential validity

- [ ] **Finding 1a — credential validity presets.** Replace the ordinary credential-expiry date input with a select containing useful durations (from one day through one year) plus **Vlastní datum**. Show a shadcn-vue date picker only for the custom choice. Preserve server-side expiry validation and timezone semantics.

## Workstream B: compact catalogue views

These are visually substantial but bounded once detail-surface behavior is agreed.

- [ ] **Finding 11 — compact Recipe catalogue.** Use narrower cards with a taller image ratio; place Tags at the top; keep name, nutrition, and icon actions in the summary; move Ingredients and Steps into an accessible detail Dialog or Collapsible surface.
- [ ] **Finding 12 — compact Ingredient catalogue.** Put the optional image before the name without reserving an empty column; omit a one-piece package label; move nutrition and full Alternatives management to detail/edit surfaces; show Alternative count in the list with a HoverCard/Popover summary.

## Workstream C: shopping-list redesign

Findings 2 and 3 form one component redesign and should be delivered together.

- [ ] **Finding 2 — compact Shopping List lines.** Replace large line cards with collapsible rows whose summary contains Ingredient name, whole package count, and Required Quantity; keep purchased/surplus/contribution/Alternative details inside the expansion.
- [ ] **Finding 3 — Store and Section hierarchy.** Render Stores as the primary cards with optional Store logos. Nest Store Sections with their icon and colour; use the Section colour for a non-colour-only border/accent treatment on its item group.

## Workstream D: calendar planning and generation

These findings share calendar state, selection, and collision semantics and require the grill before implementation.

- [ ] **Finding 8 — inline Calendar Entry creation.** Add a plus action to each day/Meal Label group. Open a compact Recipe search, Serving Count, and consecutive-day count form seeded from that day and label. Decide duplicate/collision behavior for the multi-day operation and whether it remains one request/transaction.
- [ ] **Finding 9 — generation action and range Dialog.** Move generation to a top-right action opening a Dialog with a calendar-style range picker. Reconcile the requested contiguous range with the current `Calendar Selection` domain concept, which explicitly allows arbitrary non-contiguous dates.
- [ ] **Finding 10 — compact Calendar Entry summaries.** Remove the repeated nutrition/override heading; show compact Recipe name, Serving Count, small nutrition overview, and icon-only actions positioned in the corner with accessible names and tooltips.

## Workstream E: dates and shared relation infrastructure

- [ ] **Finding 1b — general date pickers.** Inventory all remaining native date inputs and use a shadcn-vue Calendar/Popover date picker where calendar context improves the interaction. Keep native input only where it is demonstrably better for the field.
- [ ] **Finding 15 — deferred layered relation creation.** Record only for now: relation selectors should eventually offer full entity creation in a lazy-loaded layered modal without losing the current form state.
- [ ] **Finding 16 — deferred lazy relation search.** Record as the intended foundation for Finding 15: dedicated reusable Family-scoped search endpoints, configurable initial result size (about 20), and fresh entities available without a page reload. Where current work creates a new searchable relation selector, avoid designs that would block this migration.

## Grill decision tree

The `grill-with-docs` session uses the domain glossary and code evidence. The initial decision frontier is resolved as follows:

1. **Calendar selection semantics:** retain arbitrary Calendar Selection, make a contiguous range the primary interaction, and hide individual-date selection by default.
2. **Repeated Calendar Entry write semantics:** use one atomic multi-day command; each date follows the established duplicate-accumulation rule and any failure rolls back the entire command.
3. **Catalogue detail surface:** consistently use modal detail for Recipe and Ingredient catalogues rather than inline expansion.
4. **Ingredient create-time photo:** save the Ingredient independently and upload its optional photo separately; upload failure must never block or roll back the save.
5. **Agent Credential validity:** offer exact 1-, 7-, 30-, 90-, 180-, and 365-day presets, default to 90 days, and reveal a date picker for a custom valid-until date.
6. **Date-picker coverage:** replace remaining native date controls with consistent calendar-based components, including Agent Change Set History filters.
7. **Lazy relation search contract:** keep layered entity creation deferred; decide the reusable search foundation when implementing the new Calendar Recipe selector and richer Ingredient placement selectors.

The next frontier covers interaction details that depend on these choices: hidden individual-date mode behavior, consecutive-addition limits and feedback, modal trigger/action placement, save-then-upload recovery, custom valid-until time semantics, and how much of lazy relation search to introduce in the affected selectors.

That dependent frontier is resolved as follows:

1. **Hidden individual dates:** the generation Dialog defaults to contiguous range mode. Revealing individual-date mode copies the current range into a multi-date selection that can then be edited independently.
2. **Consecutive additions:** accept one through 31 days and report created versus accumulated entries in one Czech success summary, without interrupting the atomic operation per date.
3. **Modal detail trigger:** clicking the compact Recipe or Ingredient summary outside its action buttons is the primary detail action. Edit, archive, restore, and other actions remain independent.
4. **Alternative preview:** provide the requested hover preview on pointer-based layouts. Mobile users reach the same information through the Ingredient detail modal, so no additional tap-only Popover behavior is required.
5. **Unassigned Shopping List lines:** use a neutral **Bez obchodu** primary card for Ingredients without a Store and a neutral **Nezařazené** section for Store placements without a Section. Do not invent Store or Section colours.
6. **Photo recovery:** after Ingredient creation, keep the form open while the independent upload runs. On failure, state that the Ingredient is already saved, retain the selected file, and offer immediate retry.
7. **Custom credential validity:** label the field **Platný do data včetně**; the selected local day is fully valid and expiry occurs at the following local midnight.
8. **Lazy search scope:** introduce the reusable Family-scoped lazy-search foundation for the Calendar Recipe and Ingredient Store/Section selectors in this delivery. Layered entity creation remains deferred.

The remaining frontier covers the reusable search result/pagination contract, modal URL/history behavior, initial Shopping List expansion state, image replacement behavior inside the drop target, and shared date-picker interaction details.

That final interaction frontier is resolved as follows:

1. **Lazy-search contract:** expose dedicated Current-Family Recipe, Store, and Store Section endpoints with one `q`, cursor, and configurable-limit contract. Load 20 results on first open, debounce text searches, support further pages, and constrain Sections by the selected Store.
2. **Modal loading and history:** represent Recipe and Ingredient detail in the URL, lazily load and cache the full detail, close through browser Back, and restore the modal on refresh. Initial catalogue payloads contain summaries only.
3. **Detail-to-edit transition:** include edit or restore actions in modal detail, close detail before opening edit, and never stack the two modals.
4. **Shopping List expansion:** start every compact item row collapsed and allow several rows to remain independently expanded.
5. **Image drop target:** accept one pending file, replace only its local preview when another is selected, and retain the existing stored image until explicit upload succeeds. During creation, begin the independent upload after the Ingredient save.
6. **Shared date pickers:** use Czech locale and Monday-first calendars throughout. Provide applicable clear/today actions, show one month on mobile and two for range selection on larger screens, preserve explicit filter submission, and update ordinary form dates immediately.

The WebP addition leaves one final media-contract decision: whether static WebP support applies consistently to every existing entity-image upload while animated WebP receives explicit Czech rejection feedback.

Resolved domain terms will be written into `CONTEXT.md` immediately. An ADR will be offered only for decisions that are hard to reverse, surprising, and involve a real trade-off.

## Verification and completion log

Each completed slice will record its focused tests, Docker scenario, browser scenario, console result, and commit below. Final frontend checks are `pnpm eslint`, `pnpm prettier`, `pnpm tsc`, and the affected Vitest tests. PHP slices additionally require Pint, `composer cs`, `composer phpstan`, and affected PHPUnit tests.

| Slice                 | Focused automated checks                                                | Docker/browser evidence                                                                                                                                                                  | Commit(s)                                                                                |
| --------------------- | ----------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Plan recorded         | Not applicable                                                          | Not applicable                                                                                                                                                                           | `de18807` `:memo: [ui] record feedback implementation plan`                              |
| Findings 6, 7, and 13 | 24 Ingredient/Recipe/Calendar/decimal Vitest assertions; focused ESLint | Disposable SQLite in rebuilt Compose stack; created a metric-only Ingredient; edit showed `1100`, an empty optional piece count, Czech optional guidance, and no colour editor           | `535c83ec` implementation; `bdec179a` tests                                              |
| Finding 14            | 12 navigation/Agent-page Vitest assertions; focused ESLint              | Rebuilt Compose stack; User menu hid Agent links with no Current Family, showed both after creating a disposable Family, and primary navigation omitted both; no console warnings/errors | `535c83ec` implementation; `bdec179a` tests                                              |
| Full frontend gate    | `pnpm eslint`, `pnpm prettier`, `pnpm tsc`, 94 Vitest assertions        | Same rebuilt Compose stack and browser session; browser console remained clean                                                                                                           | `535c83ec` `:lipstick: [ui] improve forms and profile navigation`; `bdec179a` test suite |
