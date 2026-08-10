/// <reference types="node" />

import { readFileSync, readdirSync } from 'node:fs';
import { extname, join } from 'node:path';
import { describe, expect, it } from 'vitest';
import { localizePasskeyError } from '@/lib/passkeyError';

const vueRoot = new URL('.', import.meta.url).pathname;

const collectVueFiles = (directory: string): string[] =>
    readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            return collectVueFiles(path);
        }

        return extname(path) === '.vue' ? [path] : [];
    });

const legacyEnglishPhrases = [
    'Add a member',
    'Add passkey',
    'Appearance settings',
    'Confirm password',
    'Create a Family',
    'Create a Store',
    'Current Family',
    'Delete account',
    'Delete Family',
    'Delete Store',
    'Email address',
    'Forgot password',
    'Hide password',
    'Log in',
    'Log out',
    'Navigation menu',
    'New password',
    'No passkeys yet',
    'No Stores yet',
    'Profile settings',
    'Register passkey',
    'Remove passkey',
    'Rename Store',
    'Reset password',
    'Security settings',
    'Select a Family',
    'Show password',
    'Toggle sidebar',
    'Update password',
] as const;

describe('Czech UI localization', () => {
    it('does not contain the legacy English interface copy', () => {
        const sources = collectVueFiles(vueRoot)
            .map((path) => readFileSync(path, 'utf8'))
            .join('\n');

        for (const phrase of legacyEnglishPhrases) {
            expect(sources, `Found English UI phrase: ${phrase}`).not.toContain(
                phrase,
            );
        }
    });

    it('keeps representative navigation, authentication, and action labels in Czech', () => {
        const app = readFileSync(new URL('./app.ts', import.meta.url), 'utf8');
        const sidebar = readFileSync(
            new URL('./components/AppSidebar.vue', import.meta.url),
            'utf8',
        );
        const login = readFileSync(
            new URL('./pages/auth/Login.vue', import.meta.url),
            'utf8',
        );
        const familyDialog = readFileSync(
            new URL(
                './components/families/DeleteFamilyDialog.vue',
                import.meta.url,
            ),
            'utf8',
        );

        expect(app).toContain("'Kuchařka'");
        expect(sidebar).toContain("title: 'Rodiny'");
        expect(sidebar).toContain("title: 'Obchody'");
        expect(login).toContain('Přihlásit se');
        expect(familyDialog).toContain('Smazat rodinu');
    });

    it('localizes errors emitted by the passkey client', () => {
        expect(
            localizePasskeyError('Passkeys are not supported in this browser.'),
        ).toBe('Tento prohlížeč nepodporuje přístupové klíče.');
        expect(localizePasskeyError('Request failed with status 503')).toBe(
            'Požadavek se nezdařil (stav 503).',
        );
        expect(localizePasskeyError('Unexpected browser error')).toBe(
            'Operaci s přístupovým klíčem se nepodařilo dokončit. Zkuste to znovu.',
        );
    });
});
