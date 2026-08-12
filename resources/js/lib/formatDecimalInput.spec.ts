import { describe, expect, it } from 'vitest';
import { formatDecimalInput } from './formatDecimalInput';

describe('formatDecimalInput', () => {
    it.each([
        ['1100.000000', '1100'],
        ['17.500000', '17.5'],
        ['0.000001', '0.000001'],
        ['12.340500', '12.3405'],
        ['42', '42'],
        [42, '42'],
        ['', ''],
        [null, ''],
        [undefined, ''],
    ])('formats %s as %s', (value, expected) => {
        expect(formatDecimalInput(value)).toBe(expected);
    });

    it('leaves non-decimal input untouched', () => {
        expect(formatDecimalInput('not-a-number')).toBe('not-a-number');
    });
});
