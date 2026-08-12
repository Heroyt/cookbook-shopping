/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const source = readFileSync(
    new URL('./AppDatePicker.vue', import.meta.url),
    'utf8',
);

describe('shared date picker', () => {
    it('uses the shadcn calendar and popover with Czech Monday-first behavior', () => {
        expect(source).toContain("from '@/components/ui/calendar'");
        expect(source).toContain("from '@/components/ui/popover'");
        expect(source).toContain(':week-starts-on="1"');
        expect(source).toContain('locale="cs-CZ"');
        expect(source).toContain('Dnes');
        expect(source).toContain('Vymazat');
    });

    it('submits the canonical date through a hidden field', () => {
        expect(source).toContain('type="hidden"');
        expect(source).toContain(':name="name"');
        expect(source).toContain(':value="modelValue"');
    });
});
