/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('application branding', () => {
    it('uses the cookbook checklist mark in the application shell', () => {
        const component = readSource('./AppLogoIcon.vue');

        expect(component).toContain('viewBox="0 0 64 64"');
        expect(component).toContain('M14 23h36l-3 27H17l-3-27Z');
        expect(component).toContain('M25 36l5 5 10-11');
        expect(component).not.toContain('M17.2 5.633');
    });

    it('publishes the same SVG mark as the browser icon', () => {
        const logo = readSource('../../../public/logo.svg');
        const favicon = readSource('../../../public/favicon.svg');
        const document = readSource('../../views/app.blade.php');

        expect(logo).toContain('M14 23h36l-3 27H17l-3-27Z');
        expect(favicon).toContain('M14 23h36l-3 27H17l-3-27Z');
        expect(document).toContain('href="/logo.svg"');
        expect(document).not.toContain('href="/favicon.ico"');
        expect(document).not.toContain('href="/apple-touch-icon.png"');
    });
});
