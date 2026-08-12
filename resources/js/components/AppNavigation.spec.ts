/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('application navigation', () => {
    it.each(['./AppSidebar.vue', './AppHeader.vue'])(
        'omits starter-kit links from %s',
        (relativePath) => {
            const source = readSource(relativePath);

            expect(source).not.toContain('Repozitář');
            expect(source).not.toContain('Dokumentace');
            expect(source).not.toContain('github.com/laravel/vue-starter-kit');
            expect(source).not.toContain('laravel.com/docs/starter-kits#vue');
        },
    );

    it('places Current Family agent destinations in the User menu', () => {
        const sidebar = readSource('./AppSidebar.vue');
        const header = readSource('./AppHeader.vue');
        const navUser = readSource('./NavUser.vue');
        const userMenu = readSource('./UserMenuContent.vue');

        expect(sidebar).not.toContain("title: 'Přístupy agentů'");
        expect(sidebar).not.toContain("title: 'Historie změn agentů'");
        expect(userMenu).toContain('Přístupy agentů');
        expect(userMenu).toContain('Historie změn agentů');
        expect(userMenu).toContain('v-if="showFamilyLinks"');
        expect(userMenu).toContain('AgentCredentialController.index()');
        expect(userMenu).toContain('AgentChangeSetHistoryController.index()');
        expect(header).toContain(':show-family-links="showFamilyLinks"');
        expect(navUser).toContain(':show-family-links="showFamilyLinks"');
    });
});
