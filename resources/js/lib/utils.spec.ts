import type { InertiaLinkProps } from '@inertiajs/vue3';
import { describe, expect, test } from 'vitest';
import { cn, toUrl } from '@/lib/utils';

describe('cn', () => {
    test('combines conditional class names', () => {
        expect(cn('rounded', false && 'hidden', ['px-2', 'font-medium'])).toBe(
            'rounded px-2 font-medium',
        );
    });

    test('keeps the last conflicting Tailwind utility', () => {
        expect(cn('px-2 text-sm', 'px-4 text-lg')).toBe('px-4 text-lg');
    });
});

describe('toUrl', () => {
    test('returns string links unchanged', () => {
        expect(toUrl('/dashboard')).toBe('/dashboard');
    });

    test('extracts URLs from Wayfinder-style links', () => {
        const href: NonNullable<InertiaLinkProps['href']> = {
            method: 'get',
            url: '/settings/profile',
        };

        expect(toUrl(href)).toBe('/settings/profile');
    });
});
