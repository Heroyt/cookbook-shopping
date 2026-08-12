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
        expect(readSource('./IngredientList.vue')).not.toContain(
            '<EntityImageUpload',
        );
        expect(edit).toContain('media-type="ingredient-photo"');
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
        expect(page).toContain('<CreateIngredientDialog />');
        expect(readSource('./CreateIngredientDialog.vue')).toContain(
            '@success="createdIngredient = $event"',
        );
        expect(readSource('./CreateIngredientDialog.vue')).toContain(
            'media-type="ingredient-photo"',
        );
        expect(page).not.toContain(':stores="stores"');
        expect(page).toContain(':alternative-options="alternativeOptions"');
        expect(readSource('./EditIngredientDialog.vue')).toContain(
            '<IngredientAlternatives',
        );
        expect(readSource('./IngredientAlternatives.vue')).toContain(
            'const availableOptions = computed',
        );
        expect(page).toContain("filter: 'archived'");
    });

    it('uses lazy accessible Store Placement selectors and rich option visuals', async () => {
        const html = await render(IngredientFormFields);
        const source = readSource('./IngredientPlacementFields.vue');

        expect(html).toContain('Umístění v obchodě');
        expect(html).toContain('Obchod');
        expect(html).toContain('Část obchodu');
        expect(source).toContain('<RelationSearchSelect');
        expect(source).toContain("from '@/routes/relation-search'");
        expect(source).toContain(':endpoint="stores().url"');
        expect(source).toContain(':endpoint="storeSectionEndpoint"');
        expect(source).toContain("sectionSelection.value = ''");
        expect(source).toContain('clear-label="Bez obchodu"');
        expect(html).toContain('name="store_id"');
        expect(html).toContain('name="store_section_id"');
        expect(html).toContain('Umístění slouží pouze');
        expect(html).toContain('Nutriční profil');
        expect(source).toContain('option?.logoUrl');
        expect(source).toContain('backgroundColor: option.colour');
        expect(source).toContain('<StoreSectionIcon');
        expect(html).toContain('Základ profilu');
        expect(html).toContain('Energie (kcal)');
        expect(html).toContain('Bílkoviny (g)');
    });

    it('keeps Store Section colour editing out of Ingredient forms', async () => {
        const html = await render(IngredientFormFields, {
            ingredient: {
                storeId: 1,
                storeSectionId: 2,
                store: { id: 1, name: 'Tržiště', logoUrl: null },
                storeSection: {
                    id: 2,
                    name: 'Zelenina',
                    colour: '#16A34A',
                    icon: 'apple',
                    iconUrl: null,
                },
            },
        });
        const fields = readSource('./IngredientFormFields.vue');
        expect(fields).not.toContain('EditStoreSectionColourDialog');
        expect(html).not.toContain('Změnit barvu části');
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
        expect(html).toContain('Počet kusů v balení (volitelné)');
        expect(html).toContain('Popis');
        expect(html).toContain('Vyplňte alespoň jednu hodnotu.');
        expect(html).toContain('počet kusů může být jedinou hodnotou');
        expect(html).toContain('name="metric_quantity"');
        expect(html).toContain('name="metric_unit"');
        expect(html).toContain('step="0.000001"');
        expect(html).toContain('aria-invalid="false"');
    });

    it('shows stored decimal values without unnecessary trailing zeroes', async () => {
        const html = await render(IngredientFormFields, {
            ingredient: {
                metricQuantity: '1100.000000',
                pieceCount: '10.500000',
                nutrition: {
                    basisKind: 'grams',
                    basisQuantity: '100.000000',
                    energyKcal: '360.000000',
                    fatGrams: '1.250000',
                    proteinGrams: '7.000000',
                    carbohydrateGrams: '78.000001',
                },
            },
        });

        for (const value of ['1100', '10.5', '100', '360', '1.25', '7']) {
            expect(html).toContain(`value="${value}"`);
        }

        expect(html).toContain('value="78.000001"');
        expect(html).not.toContain('value="1100.000000"');
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
        });
        const listHtml = await render(IngredientList, {
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
                    store: { id: 1, name: 'Tržiště', logoUrl: null },
                    storeSection: {
                        id: 2,
                        name: 'Zelenina',
                        colour: '#16A34A',
                        icon: 'apple',
                        iconUrl: null,
                    },
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
        expect(readSource('./IngredientList.vue')).toContain('<DialogContent');
        expect(readSource('./IngredientList.vue')).toContain(
            'v-if="selectedIngredient"',
        );
        expect(readSource('./IngredientList.vue')).toContain(
            '<HoverCard v-if="ingredient.alternatives.length"',
        );
        expect(readSource('./IngredientList.vue')).not.toContain(
            '<TableHead>Výživa</TableHead>',
        );
        expect(listHtml).not.toContain('Upravit');
    });

    it('keeps modal ingredient detail in browser history', () => {
        const source = readSource('./IngredientList.vue');

        expect(source).toContain("const detailQueryParameter = 'ingredient'");
        expect(source).toContain('window.history.pushState');
        expect(source).toContain('window.history.back()');
        expect(source).toContain("window.addEventListener('popstate'");
        expect(source).toContain('syncDetailFromUrl()');
    });

    it('adds Ingredients to primary navigation through a generated route', () => {
        const sidebar = readSource('../AppSidebar.vue');

        expect(sidebar).toContain("title: 'Suroviny'");
        expect(sidebar).toContain('href: ingredientsIndex()');
        expect(sidebar).toContain("from '@/routes/ingredients'");
        expect(sidebar).toContain('page.props.currentFamily === null');
    });
});
