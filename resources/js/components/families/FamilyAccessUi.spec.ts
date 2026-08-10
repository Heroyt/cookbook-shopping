/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Family Access UI contract', () => {
    it('wires the Family form to the typed action with its validation boundary', () => {
        const source = readSource('./CreateFamilyForm.vue');

        expect(source).toContain('FamilyController.store.form()');
        expect(source).toContain('name="name"');
        expect(source).toContain('maxlength="255"');
        expect(source).toContain(':aria-invalid="Boolean(errors.name)"');
        expect(source).toContain('<FieldError :errors="[errors.name]" />');
    });

    it('preserves the Family page heading and primary navigation entry', () => {
        const page = readSource('../../pages/families/Create.vue');
        const sidebar = readSource('../AppSidebar.vue');

        expect(page).toContain('<h1 class="sr-only">Create a Family</h1>');
        expect(sidebar).toContain("title: 'Families'");
        expect(sidebar).toContain('href: createFamily()');
    });

    it('renders the final-member limitation through the account error state', () => {
        const source = readSource('../DeleteUser.vue');

        expect(source).toContain('v-if="errors.account"');
        expect(source).toContain('{{ errors.account }}');
        expect(source).toContain('member management and Family');
        expect(source).toContain('deletion have not shipped yet');
    });
});
