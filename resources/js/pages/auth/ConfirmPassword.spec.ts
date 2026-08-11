/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const source = readFileSync(
    new URL('./ConfirmPassword.vue', import.meta.url),
    'utf8',
);

describe('password confirmation form', () => {
    it('uses the generated Fortify action and exposes Czech accessible feedback', () => {
        expect(source).toContain(
            "import { store } from '@/routes/password/confirm';",
        );
        expect(source).toContain('v-bind="store.form()"');
        expect(source).toContain(
            '<FieldLabel for="password">Heslo</FieldLabel>',
        );
        expect(source).toContain(':aria-invalid="Boolean(errors.password)"');
        expect(source).toContain('<FieldError :errors="[errors.password]" />');
        expect(source).toContain('Potvrdit heslo');
    });
});
