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
});
