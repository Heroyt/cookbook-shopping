/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import type { Component } from 'vue';
import { renderToString } from 'vue/server-renderer';
import RecipeFormFields from './RecipeFormFields.vue';
import RecipeList from './RecipeList.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');
const render = (component: Component, props = {}): Promise<string> =>
    renderToString(createSSRApp(component, props));

describe('Recipe UI', () => {
    it('uses generated Wayfinder actions for complete saves, lifecycle, tags, and search', () => {
        expect(readSource('./CreateRecipeForm.vue')).toContain(
            'RecipeController.store.form()',
        );
        expect(readSource('../../pages/recipes/Index.vue')).toContain(
            '<CreateRecipeDialog',
        );
        expect(readSource('./CreateRecipeDialog.vue')).toContain(
            '@success="open = false"',
        );
        expect(readSource('../../pages/recipes/Index.vue')).toContain(
            '<ManageRecipeTagsDialog :tags="tags" />',
        );
        expect(readSource('./EditRecipeDialog.vue')).toContain(
            'RecipeController.update.form(recipe.id)',
        );
        expect(readSource('./EditRecipeDialog.vue')).toContain(
            'ref(props.openInitially)',
        );
        expect(readSource('./RecipeList.vue')).toContain(
            ':open-initially="editRecipeId === recipe.id"',
        );
        expect(readSource('./RecipeList.vue')).not.toContain(
            '<EntityImageUpload',
        );
        expect(readSource('./EditRecipeDialog.vue')).toContain(
            'media-type="recipe-cover"',
        );
        expect(readSource('./RecipeLifecycleButton.vue')).toContain(
            'RecipeController.archive',
        );
        expect(readSource('./RecipeLifecycleButton.vue')).toContain(
            'RecipeController.restore',
        );
        expect(readSource('./RecipeTagManager.vue')).toContain(
            'RecipeTagController.store.form()',
        );
        expect(readSource('./RecipeTagManager.vue')).toContain(
            'RecipeTagController.destroy.form(tag.id)',
        );
        expect(readSource('../../pages/recipes/Index.vue')).toContain(
            'RecipeController.index.form()',
        );
        expect(readSource('../AppSidebar.vue')).toContain("title: 'Recepty'");
        expect(readSource('../AppSidebar.vue')).toContain(
            'href: recipesIndex()',
        );
    });

    it('renders accessible ordered repeatable aggregate fields and Czech guidance', async () => {
        const html = await render(RecipeFormFields, {
            ingredients: [{ id: 1, name: 'Mouka', kinds: ['grams'] }],
            tags: [{ id: 2, name: 'Rychlé' }],
        });
        expect(html).toContain('Název receptu');
        expect(html).toContain('Počet porcí');
        expect(html).toContain('Suroviny receptu');
        expect(html).toContain('Surovinu můžete v receptu použít opakovaně');
        expect(html).toContain('Postup');
        expect(html).toContain('Zdrojový odkaz');
        expect(html).toContain('Nutriční přepis na porci');
        expect(html).toContain('name="base_servings"');
        expect(html).toContain('step="0.000001"');
        expect(html).toContain('Odebrat surovinu 1');
        expect(readSource('./RecipeFormFields.vue')).toContain(
            '@update:model-value="chooseIngredient(index, $event)"',
        );
    });

    it('shows explicit incomplete nutrition and every search reason', async () => {
        const html = await render(RecipeList, {
            ingredients: [],
            tags: [],
            recipes: [
                {
                    id: 1,
                    name: 'Rajčatová polévka',
                    baseServings: '4.000000',
                    version: 1,
                    sourceUrl: null,
                    preparationMinutes: null,
                    cookingMinutes: null,
                    notes: null,
                    archived: true,
                    ingredients: [
                        {
                            id: 1,
                            ingredientId: 1,
                            ingredientName: 'Rajče',
                            quantity: '100.000000',
                            quantityKind: 'grams',
                        },
                    ],
                    steps: [],
                    tags: [{ id: 1, name: 'Rajčatové' }],
                    matchReasons: [
                        { kind: 'name', label: 'Název receptu' },
                        { kind: 'tag', label: 'Štítek: Rajčatové' },
                        { kind: 'ingredient', label: 'Surovina: Rajče' },
                    ],
                    nutrition: {
                        status: 'incomplete',
                        perServing: null,
                        missingIngredientNames: ['Rajče'],
                    },
                    nutritionOverride: null,
                },
            ],
        });
        expect(html).toContain('Název receptu');
        expect(html).toContain('Štítek: Rajčatové');
        expect(html).toContain('Surovina: Rajče');
        expect(html).toContain('Nelze úplně vypočítat');
        expect(html).toContain('Obnovit');
        expect(html).not.toContain('Upravit');
    });

    it('keeps modal recipe detail in browser history', () => {
        const source = readSource('./RecipeList.vue');

        expect(source).toContain("const detailQueryParameter = 'recipe'");
        expect(source).toContain('window.history.pushState');
        expect(source).toContain('window.history.back()');
        expect(source).toContain("window.addEventListener('popstate'");
        expect(source).toContain('syncDetailFromUrl()');
    });

    it('shows stored decimal values without unnecessary trailing zeroes', async () => {
        const html = await render(RecipeFormFields, {
            ingredients: [{ id: 1, name: 'Mouka', kinds: ['grams'] }],
            tags: [],
            recipe: {
                id: 1,
                name: 'Chléb',
                baseServings: '4.000000',
                version: 1,
                sourceUrl: null,
                preparationMinutes: null,
                cookingMinutes: null,
                notes: null,
                archived: false,
                ingredients: [
                    {
                        id: 1,
                        ingredientId: 1,
                        ingredientName: 'Mouka',
                        quantity: '500.250000',
                        quantityKind: 'grams',
                    },
                ],
                steps: [],
                tags: [],
                matchReasons: [],
                nutrition: {
                    status: 'override',
                    perServing: null,
                    missingIngredientNames: [],
                },
                nutritionOverride: {
                    energyKcal: '350.000000',
                    fatGrams: '8.500000',
                    proteinGrams: '17.000000',
                    carbohydrateGrams: '49.000001',
                },
            },
        });

        for (const value of ['4', '500.25', '350', '8.5', '17']) {
            expect(html).toContain(`value="${value}"`);
        }

        expect(html).toContain('value="49.000001"');
        expect(html).not.toContain('value="4.000000"');
    });
});
