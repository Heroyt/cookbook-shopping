/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Agent Change History UI contract', () => {
    it('provides a responsive Czech Current Family history with every approved filter', () => {
        const page = readSource('../../pages/agent-change-sets/Index.vue');
        const filters = readSource('./AgentChangeSetFilters.vue');
        const list = readSource('./AgentChangeSetList.vue');
        const userMenu = readSource('../UserMenuContent.vue');

        expect(page).toContain('<Head title="Historie změn agentů" />');
        expect(page).toContain('Neměnný přehled změn');
        expect(filters).toContain('aria-label="Filtry historie změn agentů"');

        for (const label of [
            'Přístup agenta',
            'Vydavatel',
            'Od data',
            'Do data',
            'Typ záznamu',
            'Výsledek',
        ]) {
            expect(filters).toContain(label);
        }

        expect(filters).toContain('sm:grid-cols-2');
        expect(filters).toContain('xl:grid-cols-3');
        expect(list).toContain('md:grid-cols-2');
        expect(list).toContain('xl:grid-cols-3');
        expect(list).toContain('Žádné použité změny');
        expect(userMenu).toContain('Historie změn agentů');
        expect(userMenu).toContain('AgentChangeSetHistoryController.index()');
    });

    it('shows immutable detail and a focus-restoring deletion dialog that states consequences', () => {
        const page = readSource('../../pages/agent-change-sets/Show.vue');
        const dialog = readSource('./DeleteAgentChangeSetDialog.vue');

        expect(page).toContain('Původní požadavek');
        expect(page).toContain('Úplný výsledek');
        expect(page).toContain('Mapování místních identifikátorů');
        expect(dialog).toContain('<AlertDialogTrigger as-child>');
        expect(dialog).toContain(
            '<AlertDialogCancel>Zrušit</AlertDialogCancel>',
        );
        expect(dialog).toContain('Smaže se pouze auditní záznam');
        expect(dialog).toMatch(/se nevrátí zpět\s+a zůstanou beze změny/);
        expect(dialog).toContain(
            'AgentChangeSetHistoryController.destroy(props.changeSetId).url',
        );
    });
});
