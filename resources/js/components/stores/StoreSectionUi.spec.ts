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
        expect(page).toContain('<CreateStoreSectionDialog />');
        expect(
            readFileSync(
                new URL('./CreateStoreSectionDialog.vue', import.meta.url),
                'utf8',
            ),
        ).toContain('@success="open = false"');
        expect(page).toContain(
            '<StoreSectionList :store-sections="storeSections" />',
        );
    });

    it('wires destructive deletion through Wayfinder and discloses affected counts', () => {
        const dialog = readFileSync(
            new URL('./DeleteStoreSectionAlertDialog.vue', import.meta.url),
            'utf8',
        );
        const list = readFileSync(
            new URL('./StoreSectionList.vue', import.meta.url),
            'utf8',
        );
        const managementDialog = readFileSync(
            new URL('./EditStoreSectionDialog.vue', import.meta.url),
            'utf8',
        );

        expect(dialog).toContain(
            'StoreSectionController.destroy(storeSection.id).url',
        );
        expect(dialog).toContain('router.delete');
        expect(dialog).toContain('Přiřazení k obchodům:');
        expect(dialog).toContain('storeSection.placementCount');
        expect(dialog).toContain('Tuto akci nelze vrátit zpět.');
        expect(managementDialog).toContain('<DeleteStoreSectionAlertDialog');
        expect(managementDialog).toContain(':store-section="storeSection"');
        expect(list).toContain('<EditStoreSectionDialog');
        expect(list).not.toContain('<EntityImageUpload');
    });

    it('offers an accessible SVG icon pack for create and edit forms', async () => {
        const html = await render(StoreSectionFormFields);
        const editDialog = readFileSync(
            new URL('./EditStoreSectionDialog.vue', import.meta.url),
            'utf8',
        );

        expect(html).toContain('Ikona části obchodu');
        expect(html).toContain('name="icon"');
        expect(html).toContain('Ovoce');
        expect(html).toContain('Zelenina');
        expect(html).toContain('Pečivo');
        expect(html).toContain('Drogerie');
        expect(editDialog).toContain(
            'StoreSectionController.updateIcon.form(storeSection.id)',
        );
        expect(editDialog).toContain('@success="open = false"');
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
                {
                    id: 1,
                    name: 'Čerstvá zelenina',
                    colour: '#2F855A',
                    icon: 'carrot',
                    iconUrl: null,
                    associationCount: 2,
                    placementCount: 1,
                },
            ],
        });

        expect(html).toContain('Čerstvá zelenina');
        expect(html).toContain('#2F855A');
        expect(html).toContain('aria-hidden="true"');
        expect(html).toContain('background-color:#2F855A');
    });
});
