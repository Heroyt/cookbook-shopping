/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Agent Credential UI contract', () => {
    it('keeps the Current Family route page thin and responsive', () => {
        const page = readSource('../../pages/agent-credentials/Index.vue');
        const list = readSource('./AgentCredentialList.vue');
        const userMenu = readSource('../UserMenuContent.vue');

        expect(page).toContain('<Head title="Přístupy agentů" />');
        expect(page).toContain('<CreateAgentCredentialDialog');
        expect(page).toContain('<AgentCredentialList');
        expect(page).toContain('<AgentCredentialSecretDialog');
        expect(page).toContain(':key="credentialSecret.secret"');
        expect(list).toContain('sm:grid-cols-2');
        expect(list).toContain('xl:grid-cols-2');
        expect(userMenu).toContain('Přístupy agentů');
        expect(userMenu).toContain('AgentCredentialController.index()');
    });

    it('requires confirmed password before issuer create or rotate actions', () => {
        const create = readSource('./CreateAgentCredentialDialog.vue');
        const rotate = readSource('./RotateAgentCredentialDialog.vue');

        expect(create).toContain('v-if="!passwordConfirmed"');
        expect(create).toContain('AgentCredentialController.confirmed()');
        expect(create).toContain('AgentCredentialController.store().url');
        expect(rotate).toContain('AgentCredentialController.confirmed()');
        expect(rotate).toContain(
            'AgentCredentialController.rotate(credential.id).url',
        );
    });

    it('states one-time secret handling and exposes an accessible copy flow', () => {
        const secret = readSource('./AgentCredentialSecretDialog.vue');

        expect(secret).toContain('Jednorázové zobrazení');
        expect(secret).toContain('aplikace už nikdy nezobrazí');
        expect(secret).toContain('navigator.clipboard.writeText');
        expect(secret).toContain('aria-label="Nové tajemství přístupu agenta"');
        expect(secret).toContain('aria-live="polite"');
        expect(secret).toContain('Mám bezpečně uloženo');
    });

    it('uses focus-restoring alert dialogs and states rotation and revocation consequences', () => {
        const rotate = readSource('./RotateAgentCredentialDialog.vue');
        const revoke = readSource('./RevokeAgentCredentialDialog.vue');

        for (const dialog of [rotate, revoke]) {
            expect(dialog).toContain('<AlertDialogTrigger as-child>');
            expect(dialog).toContain(
                '<AlertDialogCancel>Zrušit</AlertDialogCancel>',
            );
        }

        expect(rotate).toContain(
            'Dosavadní tajemství přestane fungovat okamžitě',
        );
        expect(rotate).toContain('nepoužité náhledy změn budou zneplatněny');
        expect(revoke).toContain(
            'Agent ztratí přístup k aktuální rodině okamžitě',
        );
        expect(revoke).toContain('auditní metadata zůstanou');
        expect(revoke).toContain(
            'AgentCredentialController.destroy(credential.id).url',
        );
    });
});
