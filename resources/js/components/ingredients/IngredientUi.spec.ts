/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import type { Component } from 'vue';
import { renderToString } from 'vue/server-renderer';
import IngredientFormFields from './IngredientFormFields.vue';
import IngredientList from './IngredientList.vue';

const render = (component: Component, props = {}): Promise<string> =>
    renderToString(createSSRApp(component, props));

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Ingredient UI', () => {
    it('wires create and edit forms to generated actions and thin page composition', () => {
        const form = readSource('./CreateIngredientForm.vue');
        const edit = readSource('./EditIngredientDialog.vue');
        const page = readSource('../../pages/ingredients/Index.vue');

        expect(form).toContain('IngredientController.store.form()');
        expect(form).toContain('reset-on-success');
        expect(edit).toContain(
            'IngredientController.update.form(ingredient.id)',
        );
        expect(edit).toContain('@success="open = false"');
        expect(edit).toContain('shallowRef(props.openInitially)');
        expect(readSource('./IngredientList.vue')).toContain(':open-initially');
        expect(readSource('./IngredientList.vue')).toContain(
            'editIngredientId === ingredient.id',
        );
        expect(readSource('./ArchiveIngredientAlertDialog.vue')).toContain(
            'IngredientController.archive(ingredient.id)',
        );
        expect(readSource('./RestoreIngredientButton.vue')).toContain(
            'IngredientController.restore(ingredient.id)',
        );
        expect(readSource('./IngredientAlternatives.vue')).toContain(
            'IngredientAlternativeController.store.form(ingredient.id)',
        );
        expect(readSource('./IngredientAlternatives.vue')).toContain(
            'IngredientAlternativeController.destroy',
        );
        expect(page).toContain('<CreateIngredientDialog :stores="stores" />');
        expect(readSource('./CreateIngredientDialog.vue')).toContain(
            '@success="open = false"',
        );
        expect(page).toContain(':stores="stores"');
        expect(page).toContain(':alternative-options="alternativeOptions"');
        expect(readSource('./IngredientList.vue')).toContain(
            ':alternative-options="alternativeOptions"',
        );
        expect(readSource('./IngredientAlternatives.vue')).toContain(
            'const availableOptions = computed',
        );
        expect(page).toContain("filter: 'archived'");
    });

    it('renders accessible Store Placement selectors and non-colour placement output', async () => {
        const stores = [
            {
                id: 1,
                name: 'Tržiště',
                sections: [{ id: 2, name: 'Zelenina', colour: '#16A34A' }],
            },
        ];
        const html = await render(IngredientFormFields, { stores });
        const source = readSource('./IngredientFormFields.vue');

        expect(html).toContain('Umístění v obchodě');
        expect(html).toContain('Obchod');
        expect(html).toContain('Část obchodu');
        expect(source).toContain(
            '<SelectItem value="none">Bez obchodu</SelectItem>',
        );
        expect(html).toContain('name="store_id"');
        expect(html).toContain('name="store_section_id"');
        expect(html).toContain('Umístění slouží pouze');
        expect(html).toContain('Nutriční profil');
        expect(html).toContain('Základ profilu');
        expect(html).toContain('Energie (kcal)');
        expect(html).toContain('Bílkoviny (g)');
    });

    it('renders accessible explicit metric units, description, and Czech guidance', async () => {
        const html = await render(IngredientFormFields);
        const source = readSource('./IngredientFormFields.vue');

        expect(html).toContain('Název suroviny');
        expect(html).toContain('Metrické množství');
        expect(html).toContain('Jednotka');
        expect(source).toContain('<SelectItem value="mg">');
        expect(source).toContain('<SelectItem value="kg">');
        expect(source).toContain('<SelectItem value="cl">');
        expect(source).toContain('<SelectItem value="l">');
        expect(html).toContain('Počet kusů v balení');
        expect(html).toContain('Popis');
        expect(html).toContain('Vyplňte alespoň jednu hodnotu.');
        expect(html).toContain('počet kusů může být jedinou hodnotou');
        expect(html).toContain('name="metric_quantity"');
        expect(html).toContain('name="metric_unit"');
        expect(html).toContain('step="0.000001"');
        expect(html).toContain('aria-invalid="false"');
    });

    it('renders backend field and package-combination errors accessibly', async () => {
        const html = await render(IngredientFormFields, {
            errors: {
                name: 'Surovina s tímto názvem už v aktuální rodině existuje.',
                metric_quantity:
                    'Množství po převodu musí mít nejvýše šest desetinných míst.',
                quantities:
                    'Zadejte metrické množství nebo počet kusů v balení.',
            },
        });

        expect(html).toContain(
            'Surovina s tímto názvem už v aktuální rodině existuje.',
        );
        expect(html).toContain(
            'Množství po převodu musí mít nejvýše šest desetinných míst.',
        );
        expect(html).toContain(
            'Zadejte metrické množství nebo počet kusů v balení.',
        );
        expect(html).toContain('aria-invalid="true"');
    });

    it('renders an empty state and derived package quantities', async () => {
        const emptyHtml = await render(IngredientList, {
            ingredients: [],
            stores: [],
        });
        const listHtml = await render(IngredientList, {
            stores: [],
            alternativeOptions: [],
            ingredients: [
                {
                    id: 1,
                    name: 'Celozrnný chléb',
                    description: null,
                    metricQuantity: '1100.000000',
                    metricUnit: 'g',
                    pieceCount: '10.000000',
                    quantities: ['1,1 kg', '10 ks'],
                    storeId: 1,
                    storeSectionId: 2,
                    placement: 'Tržiště · Zelenina',
                    archived: true,
                    alternatives: [],
                    nutrition: {
                        basisKind: 'grams',
                        basisQuantity: '100.000000',
                        energyKcal: '360.000000',
                        fatGrams: '1.000000',
                        proteinGrams: '7.000000',
                        carbohydrateGrams: '78.000000',
                    },
                },
            ],
        });

        expect(emptyHtml).toContain('Zatím nemáte žádné suroviny');
        expect(listHtml).toContain('Celozrnný chléb');
        expect(listHtml).toContain('1,1 kg');
        expect(listHtml).toContain('10 ks');
        expect(listHtml).toContain('Tržiště · Zelenina');
        expect(listHtml).toContain('Archivovaná');
        expect(listHtml).toContain('Obnovit');
        expect(listHtml).toContain('Nutriční profil');
        expect(listHtml).toContain('Bez alternativ');
        expect(listHtml).not.toContain('Upravit');
    });

    it('adds Ingredients to primary navigation through a generated route', () => {
        const sidebar = readSource('../AppSidebar.vue');

        expect(sidebar).toContain("title: 'Suroviny'");
        expect(sidebar).toContain('href: ingredientsIndex()');
        expect(sidebar).toContain("from '@/routes/ingredients'");
        expect(sidebar).toContain('page.props.currentFamily === null');
    });
});
