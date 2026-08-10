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
        const createPage = readSource('../../pages/families/Create.vue');
        const indexPage = readSource('../../pages/families/Index.vue');
        const sidebar = readSource('../AppSidebar.vue');

        expect(createPage).toContain(
            '<h1 class="sr-only">Create a Family</h1>',
        );
        expect(indexPage).toContain('FamilyMemberList');
        expect(indexPage).toContain('AddFamilyMemberForm');
        expect(indexPage).toContain('DeleteFamilyDialog');
        expect(sidebar).toContain("title: 'Families'");
        expect(sidebar).toContain('href: familiesIndex()');
        expect(sidebar).toContain('<FamilySwitcher />');
    });

    it('wires Current Family member and deletion forms to typed actions', () => {
        const addMember = readSource('./AddFamilyMemberForm.vue');
        const members = readSource('./FamilyMemberList.vue');
        const deleteFamily = readSource('./DeleteFamilyDialog.vue');
        const switcher = readSource('./FamilySwitcher.vue');

        expect(addMember).toContain('FamilyMemberController.store.form()');
        expect(addMember).toContain('name="email"');
        expect(members).toContain('FamilyMemberController.destroy(member.id)');
        expect(deleteFamily).toContain('FamilyController.destroy.form()');
        expect(deleteFamily).toContain('name="family_name"');
        expect(switcher).toContain('CurrentFamilyController.update(familyId)');
    });

    it('points final-member account deletion at the available resolution', () => {
        const source = readSource('../DeleteUser.vue');

        expect(source).toContain('v-if="errors.account"');
        expect(source).toContain('{{ errors.account }}');
        expect(source).toContain('Use Family management to add another');
        expect(source).toContain('member or delete the Family first');
    });
});
