import { describe, expect, test } from 'vitest';
import { getInitials, useInitials } from '@/composables/useInitials';

describe('getInitials', () => {
    test.each([
        ['Ada Lovelace', 'AL'],
        ['  Ada   Byron   Lovelace  ', 'AL'],
        ['prince', 'P'],
        ['Émile Zola', 'ÉZ'],
        ['', ''],
        ['   ', ''],
        [undefined, ''],
    ])('returns initials for %s', (fullName, expected) => {
        expect(getInitials(fullName)).toBe(expected);
    });

    test('exposes the same formatter through the composable', () => {
        expect(useInitials().getInitials('Grace Hopper')).toBe('GH');
    });
});
