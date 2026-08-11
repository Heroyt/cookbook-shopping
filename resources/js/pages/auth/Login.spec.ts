/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const source = readFileSync(new URL('./Login.vue', import.meta.url), 'utf8');

describe('login form', () => {
    it('connects validation feedback to both credential fields', () => {
        expect(source).toContain(':data-invalid="Boolean(errors.email)"');
        expect(source).toContain(':aria-invalid="Boolean(errors.email)"');
        expect(source).toContain('<FieldError :errors="[errors.email]" />');
        expect(source).toContain(':data-invalid="Boolean(errors.password)"');
        expect(source).toContain(':aria-invalid="Boolean(errors.password)"');
        expect(source).toContain('<FieldError :errors="[errors.password]" />');
    });

    it('labels and submits the remember-me checkbox', () => {
        expect(source).toContain(
            '<Checkbox id="remember" name="remember" :tabindex="3" />',
        );
        expect(source).toContain(
            '<FieldLabel for="remember">Zapamatovat si mě</FieldLabel>',
        );
    });
});
