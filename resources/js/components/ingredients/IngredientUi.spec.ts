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
    it('wires the create form to its generated action and thin page composition', () => {
        const form = readSource('./CreateIngredientForm.vue');
        const page = readSource('../../pages/ingredients/Index.vue');

        expect(form).toContain('IngredientController.store.form()');
        expect(form).toContain('reset-on-success');
        expect(page).toContain('<CreateIngredientForm />');
        expect(page).toContain('<IngredientList :ingredients="ingredients" />');
    });

    it('renders accessible canonical package quantity inputs with Czech guidance', async () => {
        const html = await render(IngredientFormFields);

        expect(html).toContain('Název suroviny');
        expect(html).toContain('Hmotnost balení (g)');
        expect(html).toContain('Objem balení (ml)');
        expect(html).toContain('Počet kusů v balení');
        expect(html).toContain('Vyplňte alespoň jednu hodnotu.');
        expect(html).toContain('počet kusů může být jedinou hodnotou');
        expect(html).toContain('name="weight_grams"');
        expect(html).toContain('step="0.000001"');
        expect(html).toContain('aria-invalid="false"');
    });

    it('renders backend field and package-combination errors accessibly', async () => {
        const html = await render(IngredientFormFields, {
            errors: {
                name: 'Surovina s tímto názvem už v aktuální rodině existuje.',
                weight_grams: 'Hmotnost a objem balení nelze zadat současně.',
                quantities: 'Zadejte hmotnost, objem nebo počet kusů v balení.',
            },
        });

        expect(html).toContain(
            'Surovina s tímto názvem už v aktuální rodině existuje.',
        );
        expect(html).toContain('Hmotnost a objem balení nelze zadat současně.');
        expect(html).toContain(
            'Zadejte hmotnost, objem nebo počet kusů v balení.',
        );
        expect(html).toContain('aria-invalid="true"');
    });

    it('renders an empty state and derived package quantities', async () => {
        const emptyHtml = await render(IngredientList, { ingredients: [] });
        const listHtml = await render(IngredientList, {
            ingredients: [
                {
                    id: 1,
                    name: 'Celozrnný chléb',
                    quantities: ['1,1 kg', '10 ks'],
                },
            ],
        });

        expect(emptyHtml).toContain('Zatím nemáte žádné suroviny');
        expect(listHtml).toContain('Celozrnný chléb');
        expect(listHtml).toContain('1,1 kg');
        expect(listHtml).toContain('10 ks');
    });

    it('adds Ingredients to primary navigation through a generated route', () => {
        const sidebar = readSource('../AppSidebar.vue');

        expect(sidebar).toContain("title: 'Suroviny'");
        expect(sidebar).toContain('href: ingredientsIndex()');
        expect(sidebar).toContain("from '@/routes/ingredients'");
        expect(sidebar).toContain('page.props.currentFamily === null');
    });
});
