/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('lazy relation search UI', () => {
    it('uses standalone Inertia HTTP requests with cancellation, debounce, and cursor continuation', () => {
        const source = readSource('../../composables/useRelationSearch.ts');

        expect(source).toContain('useHttp<');
        expect(source).toContain('http.cancel()');
        expect(source).toContain('options.debounceMilliseconds ?? 250');
        expect(source).toContain('nextCursor');
        expect(source).toContain('loadMore: () => load(true)');
        expect(source).not.toContain('fetch(');
    });

    it('composes the installed shadcn Command and Popover primitives accessibly', () => {
        const source = readSource('./RelationSearchSelect.vue');

        expect(source).toContain('<PopoverTrigger as-child>');
        expect(source).toContain('role="combobox"');
        expect(source).toContain('<CommandGroup>');
        expect(source).toContain('<CommandItem');
        expect(source).toContain('Načíst další');
        expect(source).toContain('Položky se nepodařilo načíst');
        expect(source).toContain("emit('create')");
        expect(source).toContain('clearLabel');
        expect(source).toContain("emit('update:modelValue', '')");
    });

    it('merges newly created pinned options into loaded results', () => {
        const source = readSource('../../composables/useRelationSearch.ts');

        expect(source).toContain('toValue(options.initialOptions!)');
        expect(source).toContain(
            'results.value = mergeOptions(initialOptions, results.value)',
        );
    });
});
