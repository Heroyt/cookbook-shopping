/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Store UI contract', () => {
    it('wires the Store form to its typed action and validation boundary', () => {
        const source = readSource('./CreateStoreForm.vue');

        expect(source).toContain('StoreController.store.form()');
        expect(source).toContain('name="name"');
        expect(source).toContain('maxlength="255"');
        expect(source).toContain(':aria-invalid="Boolean(errors.name)"');
        expect(source).toContain('<FieldError :errors="[errors.name]" />');
    });

    it('keeps the route page thin and delegates Store presentation', () => {
        const page = readSource('../../pages/stores/Index.vue');

        expect(page).toContain('<CreateStoreForm />');
        expect(page).toContain('<StoreList :stores="stores" />');
    });

    it('wires each Store rename dialog to its typed update action', () => {
        const dialog = readSource('./RenameStoreDialog.vue');
        const list = readSource('./StoreList.vue');

        expect(dialog).toContain('StoreController.update.form(store.id)');
        expect(dialog).toContain('name="name"');
        expect(dialog).toContain(':default-value="store.name"');
        expect(dialog).toContain('maxlength="255"');
        expect(dialog).toContain(':aria-invalid="Boolean(errors.name)"');
        expect(dialog).toContain('<FieldError :errors="[errors.name]" />');
        expect(dialog).toContain('@success="open = false"');
        expect(list).toContain('<RenameStoreDialog :store="store" />');
    });

    it('adds Stores to primary navigation through a generated route', () => {
        const sidebar = readSource('../AppSidebar.vue');

        expect(sidebar).toContain("title: 'Stores'");
        expect(sidebar).toContain('href: storesIndex()');
        expect(sidebar).toContain("from '@/routes/stores'");
        expect(sidebar).toContain('page.props.currentFamily === null');
    });
});
