/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import ShoppingListView from '@/components/simple-plan/ShoppingListView.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Saved Shopping List UI', () => {
    it('wires explicit processing-locked saves from both transient generated pages', () => {
        const saveButton = readSource('./SaveShoppingListButton.vue');
        expect(saveButton).toContain('storeSimplePlan.form()');
        expect(saveButton).toContain('storeCalendar.form()');
        expect(saveButton).toContain('v-slot="{ errors, processing }"');
        expect(saveButton).toContain(':disabled="processing"');
        expect(saveButton).toContain(
            '<FieldError :errors="[errors.snapshot]" />',
        );
        expect(saveButton).toContain('Každé uložení vytvoří nový záznam');
        const simplePlanPage = readSource(
            '../../pages/simple-plan/Generated.vue',
        );
        const calendarPage = readSource('../../pages/calendar/Generated.vue');
        expect(simplePlanPage).toContain('<SaveShoppingListButton');
        expect(simplePlanPage).toContain('source="simple-plan"');
        expect(calendarPage).toContain('<SaveShoppingListButton');
        expect(calendarPage).toContain('source="calendar"');
    });

    it('provides generated navigation and a consequence-stating deletion dialog', () => {
        const list = readSource('./SavedShoppingListHistory.vue');
        const deletion = readSource('./DeleteSavedShoppingListDialog.vue');
        expect(readSource('../AppSidebar.vue')).toContain(
            "title: 'Historie nákupů'",
        );
        expect(readSource('../AppSidebar.vue')).toContain(
            'href: shoppingListHistoryIndex()',
        );
        expect(list).toContain('show(snapshot.id)');
        expect(list).toContain('<Badge variant="secondary">');
        expect(list).toContain('<DeleteSavedShoppingListDialog');
        expect(list).toContain('Stránkování historie nákupů');
        expect(list).toContain('pagination.previousUrl');
        expect(list).toContain('pagination.nextUrl');
        expect(list).toContain(
            'focus-target-id="shopping-list-history-heading"',
        );
        const indexPage = readSource(
            '../../pages/shopping-list-history/Index.vue',
        );
        expect(indexPage).toContain('id="shopping-list-history-heading"');
        expect(indexPage).toContain('focus-visible:ring-2');
        expect(deletion).toContain('<AlertDialogTitle>');
        expect(deletion).toMatch(
            /Tato akce nepřepočítá\s+ani nevrátí žádné změny/,
        );
        expect(deletion).toContain('destroy(snapshot.id).url');
        expect(deletion).toContain('router.delete');
        expect(deletion).toContain(
            'document.getElementById(focusTargetId)?.focus()',
        );
        expect(deletion).toContain(':disabled="processing"');
    });

    it('renders frozen output as read-only without any Alternative mutation controls', async () => {
        const html = await renderToString(
            createSSRApp(ShoppingListView, {
                readOnly: true,
                problems: [],
                shoppingList: {
                    storeGroups: [],
                    unplacedLines: [
                        {
                            ingredientId: 2,
                            ingredientName: 'Historická mouka',
                            package: {
                                grams: '1000',
                                millilitres: null,
                                piece: null,
                            },
                            purchasePackages: '2',
                            quantities: [
                                {
                                    kind: 'grams',
                                    required: {
                                        exact: '133333/100',
                                        label: '1,33 kg',
                                        value: '1.33',
                                        unit: 'kg',
                                        approximate: true,
                                    },
                                    purchased: {
                                        exact: '2000',
                                        label: '2 kg',
                                        value: '2',
                                        unit: 'kg',
                                        approximate: false,
                                    },
                                    surplus: {
                                        exact: '66667/100',
                                        label: '666,67 g',
                                        value: '666.67',
                                        unit: 'g',
                                        approximate: false,
                                    },
                                },
                            ],
                            contributions: [],
                            eligibleAlternatives: [
                                {
                                    ingredientId: 3,
                                    ingredientName: 'Nová mouka',
                                },
                            ],
                            alternativeChoices: [
                                {
                                    originalIngredientId: 1,
                                    originalIngredientName: 'Původní mouka',
                                    alternativeIngredientId: 2,
                                    alternativeIngredientName:
                                        'Historická mouka',
                                },
                            ],
                        },
                    ],
                },
            }),
        );

        expect(html).toContain('Historická mouka');
        expect(html).toContain('2 bal.');
        expect(html).toContain('1,33 kg');
        expect(html).toContain('Použita alternativa');
        expect(html).not.toContain('Použít alternativu Nová mouka');
        expect(html).not.toContain('Vrátit původní surovinu');
        const showPage = readSource(
            '../../pages/shopping-list-history/Show.vue',
        );
        expect(showPage).toContain(':read-only="true"');
        expect(showPage).toContain('Verze záznamu');
        expect(showPage).toContain('snapshot.source.dates');
        expect(showPage).toContain('snapshot.source.recipes');
    });

    it('uses shadcn composition and stable keys without reproducing arithmetic', () => {
        const sources = [
            readSource('./SaveShoppingListButton.vue'),
            readSource('./SavedShoppingListHistory.vue'),
            readSource('./DeleteSavedShoppingListDialog.vue'),
            readSource('../../pages/shopping-list-history/Index.vue'),
            readSource('../../pages/shopping-list-history/Show.vue'),
        ].join('\n');
        expect(sources).not.toMatch(/space-[xy]-/);
        expect(sources).not.toContain('Math.');
        expect(sources).not.toMatch(/<Spinner(?![^>]*data-icon)/);
        expect(readSource('./SavedShoppingListHistory.vue')).toContain(
            ':key="snapshot.id"',
        );
    });
});
