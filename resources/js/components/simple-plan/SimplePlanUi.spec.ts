/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import type { Component } from 'vue';
import { renderToString } from 'vue/server-renderer';
import ShoppingListView from './ShoppingListView.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');
const render = (component: Component, props = {}): Promise<string> =>
    renderToString(createSSRApp(component, props));

describe('Simple Plan UI', () => {
    it('uses generated Wayfinder actions for every plan intent and sidebar navigation', () => {
        const builder = readSource('./SimplePlanBuilder.vue');
        expect(builder).toContain('store.form()');
        expect(builder).toContain('destroy.form(');
        expect(builder).toContain('selection.recipeId');
        expect(builder).toContain('generate.form()');
        expect(builder).not.toContain('import SimplePlanController from');
        expect(readSource('../../pages/simple-plan/Index.vue')).toContain(
            "from '@/routes/simple-plan'",
        );
        expect(readSource('../AppSidebar.vue')).toContain(
            "title: 'Rychlý plán'",
        );
        expect(readSource('../AppSidebar.vue')).toContain(
            'href: simplePlanIndex()',
        );
    });

    it('keeps selection intent accessible and does not reproduce shopping arithmetic', () => {
        const builder = readSource('./SimplePlanBuilder.vue');
        expect(builder).toContain('for="simple-plan-recipe"');
        expect(builder).toContain('name="serving_count"');
        expect(builder).toContain('step="0.000001"');
        expect(builder).toContain('default-value="1"');
        expect(builder).not.toMatch(/\svalue="1"/);
        expect(builder).toContain('Opakované přidání stejného receptu');
        expect(builder).toContain('Celkem');
        expect(builder).not.toContain('Math.ceil');
        expect(builder).not.toContain('baseServings');
        expect(readSource('./ShoppingListLineCard.vue')).not.toContain('Math.');
    });

    it('renders responsive grouped package-first output from server presentation values', async () => {
        const html = await render(ShoppingListView, {
            problems: [],
            shoppingList: {
                storeGroups: [
                    {
                        storeId: 1,
                        storeName: 'Albert',
                        sections: [
                            {
                                sectionId: 2,
                                sectionName: 'Pečení',
                                lines: [
                                    {
                                        ingredientId: 3,
                                        ingredientName: 'Mouka',
                                        purchasePackages: '2',
                                        quantities: [
                                            {
                                                kind: 'grams',
                                                required: {
                                                    label: '175 g',
                                                    value: '175',
                                                    unit: 'g',
                                                    approximate: false,
                                                },
                                                purchased: {
                                                    label: '300 g',
                                                    value: '300',
                                                    unit: 'g',
                                                    approximate: false,
                                                },
                                                surplus: {
                                                    label: '125 g',
                                                    value: '125',
                                                    unit: 'g',
                                                    approximate: false,
                                                },
                                            },
                                        ],
                                        contributions: [
                                            {
                                                recipeId: 4,
                                                recipeName: 'Lívance',
                                                originalIngredientName: 'Mouka',
                                                required: {
                                                    label: '175 g',
                                                    value: '175',
                                                    unit: 'g',
                                                    approximate: false,
                                                },
                                            },
                                        ],
                                        eligibleAlternatives: [
                                            {
                                                ingredientId: 5,
                                                ingredientName:
                                                    'Špaldová mouka',
                                            },
                                        ],
                                        alternativeChoices: [],
                                    },
                                ],
                            },
                        ],
                        unsectionedLines: [],
                    },
                ],
                unplacedLines: [],
            },
        });

        expect(html).toContain('Albert');
        expect(html).toContain('Pečení');
        expect(html).toContain('Mouka');
        expect(html).toContain('2 balení');
        expect(html).toContain('Potřeba');
        expect(html).toContain('175 g');
        expect(html).toContain('300 g');
        expect(html).toContain('125 g');
        expect(html).toContain('Dostupné alternativy');
        expect(html).toContain('Špaldová mouka');
        expect(html).toContain('Použít alternativu Špaldová mouka');
        const lineCard = readSource('./ShoppingListLineCard.vue');
        expect(lineCard).toContain('storeAlternative.form()');
        expect(lineCard).toContain('destroyAlternative.form(');
        expect(readSource('./ShoppingListView.vue')).toContain(
            'sm:grid-cols-2 xl:grid-cols-3',
        );
        expect(readSource('./ShoppingListLineCard.vue')).toContain(
            'Příspěvky receptů',
        );
    });

    it('renders every calculation problem and explains that the transient plan is preserved', async () => {
        const html = await render(ShoppingListView, {
            shoppingList: null,
            problems: [
                {
                    recipeId: 1,
                    recipeName: 'Omáčka',
                    ingredientId: 2,
                    ingredientName: 'Mouka',
                    quantityLabel: '50 ml',
                    message: 'Balení neobsahuje požadované množství.',
                },
                {
                    recipeId: 3,
                    recipeName: 'Těsto',
                    ingredientId: 4,
                    ingredientName: 'Vejce',
                    quantityLabel: '100 g',
                    message: 'Balení neobsahuje požadované množství.',
                },
            ],
        });

        expect(html).toContain('Nákupní seznam nelze úplně vypočítat');
        expect(html).toContain('Rychlý plán zůstal zachovaný');
        expect(html).toContain('Omáčka');
        expect(html).toContain('Těsto');
        expect(html).toContain('Mouka');
        expect(html).toContain('Vejce');
        expect(html).toContain('Zadáno: 50 ml.');
        expect(html).toContain('Upravit recept');
        expect(html).toContain('Upravit surovinu');
        const view = readSource('./ShoppingListView.vue');
        expect(view).toContain("from '@/routes/recipes'");
        expect(view).toContain("from '@/routes/ingredients'");
        expect(view).not.toContain('problem.unit');
    });

    it('follows shadcn composition and spacing contracts', () => {
        const sources = [
            readSource('./SimplePlanBuilder.vue'),
            readSource('./ShoppingListLineCard.vue'),
            readSource('./ShoppingListView.vue'),
        ].join('\n');
        expect(sources).not.toMatch(/space-y-/);
        expect(readSource('./SimplePlanBuilder.vue')).toContain(
            '<SelectGroup>',
        );
        expect(sources).not.toMatch(/<Spinner(?![^>]*data-icon)/);
    });
});
