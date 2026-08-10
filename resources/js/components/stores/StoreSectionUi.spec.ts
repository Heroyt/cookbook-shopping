/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import type { Component } from 'vue';
import { renderToString } from 'vue/server-renderer';
import StoreSectionFormFields from './StoreSectionFormFields.vue';
import StoreSectionList from './StoreSectionList.vue';

const render = (component: Component, props = {}): Promise<string> =>
    renderToString(createSSRApp(component, props));

describe('Store Section UI', () => {
    it('wires the create form to its generated action and page composition', () => {
        const form = readFileSync(
            new URL('./CreateStoreSectionForm.vue', import.meta.url),
            'utf8',
        );
        const page = readFileSync(
            new URL('../../pages/stores/Index.vue', import.meta.url),
            'utf8',
        );

        expect(form).toContain('StoreSectionController.store.form()');
        expect(page).toContain('<CreateStoreSectionForm />');
        expect(page).toContain(
            '<StoreSectionList :store-sections="storeSections" />',
        );
    });

    it('renders an accessible required colour picker with Czech guidance', async () => {
        const html = await render(StoreSectionFormFields);

        expect(html).toContain('Barva části obchodu');
        expect(html).toContain('Vyberte barvu');
        expect(html).toContain('name="colour"');
        expect(html).toContain('type="color"');
        expect(html).toContain('required');
        expect(html).toContain('aria-invalid="false"');
    });

    it('renders the colour as text as well as a visual swatch', async () => {
        const html = await render(StoreSectionList, {
            storeSections: [
                { id: 1, name: 'Čerstvá zelenina', colour: '#2F855A' },
            ],
        });

        expect(html).toContain('Čerstvá zelenina');
        expect(html).toContain('#2F855A');
        expect(html).toContain('aria-hidden="true"');
        expect(html).toContain('background-color:#2F855A');
    });
});
