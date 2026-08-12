/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    useClipboardCopy,
    useTargetedClipboardCopy,
} from '@/composables/useClipboardCopy';
import {
    createAgentBootstrapInstructions,
    createCredentialAgentInstructions,
} from './agentInstructions';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

afterEach(() => {
    vi.unstubAllGlobals();
});

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

    it('offers exact validity presets and a custom inclusive date picker', () => {
        const create = readSource('./CreateAgentCredentialDialog.vue');

        for (const days of [1, 7, 30, 90, 180, 365]) {
            expect(create).toContain(`value: '${days}'`);
        }

        expect(create).toContain("value: 'custom'");
        expect(create).toContain('Platný do data včetně');
        expect(create).toContain('<AppDatePicker');
        expect(create).not.toContain('type="date"');
    });

    it('states one-time secret handling and exposes an accessible copy flow', () => {
        const secret = readSource('./AgentCredentialSecretDialog.vue');
        const clipboard = readSource('../../composables/useClipboardCopy.ts');

        expect(secret).toContain('Jednorázové zobrazení');
        expect(secret).toContain('aplikace už nikdy nezobrazí');
        expect(secret).toContain('useTargetedClipboardCopy');
        expect(secret).toContain("copy('secret', credentialSecret.secret)");
        expect(secret).toContain("'instructions',");
        expect(secret).toContain(
            "copyTarget === 'secret' && copyState === 'copied'",
        );
        expect(secret).toContain(
            "copyTarget === 'instructions' && copyState === 'copied'",
        );
        expect(clipboard).toContain('navigator.clipboard.writeText');
        expect(secret).toContain('aria-label="Nové tajemství přístupu agenta"');
        expect(secret).toContain('aria-live="polite"');
        expect(secret).toContain('Mám bezpečně uloženo');
    });

    it('reports clipboard success and failure to the accessible UI state', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        vi.stubGlobal('navigator', { clipboard: { writeText } });
        const successfulCopy = useClipboardCopy();

        await successfulCopy.copy('pokyny');

        expect(writeText).toHaveBeenCalledWith('pokyny');
        expect(successfulCopy.copyState.value).toBe('copied');

        writeText.mockRejectedValueOnce(new Error('Clipboard blocked'));
        const failedCopy = useClipboardCopy();

        await failedCopy.copy('tajemství');

        expect(failedCopy.copyState.value).toBe('failed');
    });

    it('announces the result of the latest copy action in sequence', async () => {
        let finishInstructionCopy: (() => void) | undefined;
        const instructionCopy = new Promise<void>((resolve) => {
            finishInstructionCopy = resolve;
        });
        const writeText = vi
            .fn()
            .mockReturnValueOnce(instructionCopy)
            .mockResolvedValueOnce(undefined);
        vi.stubGlobal('navigator', { clipboard: { writeText } });
        const clipboard = useTargetedClipboardCopy<'secret' | 'instructions'>();

        const pendingInstructions = clipboard.copy(
            'instructions',
            'pokyny s tajemstvím',
        );
        expect(clipboard.copyTarget.value).toBe('instructions');
        expect(clipboard.copyState.value).toBe('idle');

        await clipboard.copy('secret', 'samostatné tajemství');
        expect(clipboard.copyTarget.value).toBe('secret');
        expect(clipboard.copyState.value).toBe('copied');

        finishInstructionCopy?.();
        await pendingInstructions;
        expect(clipboard.copyTarget.value).toBe('secret');
        expect(clipboard.copyState.value).toBe('copied');
    });

    it('creates portable bootstrap and credential-ready Agent instructions', () => {
        const connection = {
            applicationUrl: 'https://cookbook.example.test',
            agentAccessUrl: 'https://cookbook.example.test/agent-access',
            apiBaseUrl: 'https://cookbook.example.test/api/v1',
            openApiUrl:
                'https://cookbook.example.test/docs/agent-api/v1/openapi.json',
        };

        const bootstrap = createAgentBootstrapInstructions(connection);
        expect(bootstrap).toContain(
            `Agent Access page: ${connection.agentAccessUrl}`,
        );
        expect(bootstrap).toContain(
            `OpenAPI document: ${connection.openApiUrl}`,
        );
        expect(bootstrap).toContain('Agent Credential: not supplied yet');
        expect(bootstrap).toContain('“Aktuální rodina”');
        expect(bootstrap).toContain('“Nový přístup”');
        expect(bootstrap).toContain('“Potvrdit heslo”');
        expect(bootstrap).toContain('“Úpravy kuchařky”');
        expect(bootstrap).toContain(
            'Stores, Store Sections, Ingredients, Recipe Tags, and Recipes',
        );
        expect(bootstrap).toContain('“Úpravy plánování”');
        expect(bootstrap).toContain('“Destruktivní změny”');
        expect(bootstrap).toContain('“Kopírovat pokyny s tajemstvím”');
        expect(bootstrap).not.toContain('family-secret');

        const connected = createCredentialAgentInstructions(
            connection,
            '12|family-secret',
        );
        expect(connected).toContain('Agent Credential: 12|family-secret');
        expect(connected).toContain('Fetch and read the OpenAPI document');
        expect(connected).toContain('Always fetch active and archived Recipes');
        expect(connected).toContain('Store Sections use predefined icons');
        expect(connected).toContain('Calendar Entries require a Recipe');
        expect(connected).toContain(
            'reuse the same client_request_id only with the identical canonical request',
        );
        expect(connected).toContain(
            'reuse its exact digest and warning_acknowledgements',
        );
        expect(connected).toContain(
            'Do not apply the preview until the user explicitly confirms',
        );
        expect(connected).toContain(
            'ask whether the credential should be revoked',
        );
    });

    it('tells nontechnical users to copy, paste, and send the instructions to their AI chat', () => {
        const card = readSource('./AgentInstructionsCard.vue');
        const page = readSource('../../pages/agent-credentials/Index.vue');
        const secret = readSource('./AgentCredentialSecretDialog.vue');

        expect(card).toContain('Pokyny pro připojení agenta');
        expect(card).toContain(
            'Zkopírujte je, vložte do svého AI chatu a odešlete.',
        );
        expect(card.replace(/\s+/g, ' ')).toContain(
            'Agent vás pak provede vytvořením přístupu',
        );
        expect(card).toContain('Kopírovat pokyny bez tajemství');
        expect(card).toContain('aria-live="polite"');
        expect(page).toContain('<AgentInstructionsCard');
        expect(secret).toContain('Kopírovat tajemství');
        expect(secret).toContain('Kopírovat pokyny s tajemstvím');
        expect(secret.replace(/\s+/g, ' ')).toContain(
            'Vložte je do svého AI chatu a odešlete. Agent vás provede dalším postupem.',
        );
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
