/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import type { Component } from 'vue';
import { renderToString } from 'vue/server-renderer';
import StoreSectionOrderList from './StoreSectionOrderList.vue';

const render = (component: Component, props = {}): Promise<string> =>
    renderToString(createSSRApp(component, props));

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Store Section ordering UI', () => {
    it('wires association, removal, and complete reorder through generated actions', () => {
        const form = readSource('./AttachStoreSectionForm.vue');
        const list = readSource('./StoreSectionOrderList.vue');

        expect(form).toContain(
            'StoreSectionAssociationController.store.form(storeId)',
        );
        expect(form).toContain('name="store_section_id"');
        expect(list).toContain('StoreSectionAssociationController.update');
        expect(list).toContain('store_section_ids');
        expect(list).toContain('version: store.sectionOrderVersion');
        expect(list).toContain('StoreSectionAssociationController.destroy');
    });

    it('renders Czech accessible ordering controls and non-colour-only section output', async () => {
        const html = await render(StoreSectionOrderList, {
            store: {
                id: 1,
                name: 'Farmářský trh',
                sectionOrderVersion: 2,
                sections: [
                    {
                        id: 10,
                        name: 'Zelenina',
                        colour: '#2F855A',
                        position: 0,
                    },
                    {
                        id: 11,
                        name: 'Pečivo',
                        colour: '#D97706',
                        position: 1,
                    },
                ],
            },
        });

        expect(html).toContain('Zelenina');
        expect(html).toContain('Pečivo');
        expect(html).toContain('Posunout část Zelenina dolů');
        expect(html).toContain('Posunout část Pečivo nahoru');
        expect(html).toContain('Odebrat část Zelenina z obchodu');
        expect(html).toContain('aria-hidden="true"');
    });

    it('keeps the route page thin and delegates Store association management', () => {
        const page = readSource('../../pages/stores/Index.vue');
        const storeList = readSource('./StoreList.vue');
        const editDialog = readSource('./EditStoreDialog.vue');

        expect(page).toContain('<StoreList');
        expect(page).toContain(':store-sections="storeSections"');
        expect(storeList).toContain('<EditStoreDialog');
        expect(editDialog).toContain('<StoreSectionOrderManager');
    });
});
