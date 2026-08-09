import { beforeEach, describe, expect, test, vi } from 'vitest';

const page = vi.hoisted(() => ({ url: '/settings/profile?tab=account' }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}));

import { useCurrentUrl } from '@/composables/useCurrentUrl';

describe('useCurrentUrl', () => {
    beforeEach(() => {
        page.url = '/settings/profile?tab=account';
    });

    test('exposes the current path without its query string', () => {
        expect(useCurrentUrl().currentUrl.value).toBe('/settings/profile');
    });

    test('matches relative, absolute, and Wayfinder-style URLs', () => {
        const { isCurrentUrl } = useCurrentUrl();

        expect(isCurrentUrl('/settings/profile')).toBe(true);
        expect(isCurrentUrl('https://example.test/settings/profile')).toBe(
            true,
        );
        expect(isCurrentUrl({ method: 'get', url: '/settings/profile' })).toBe(
            true,
        );
        expect(isCurrentUrl('/settings/security')).toBe(false);
    });

    test('matches parent navigation URLs', () => {
        const { isCurrentOrParentUrl } = useCurrentUrl();

        expect(isCurrentOrParentUrl('/settings')).toBe(true);
        expect(isCurrentOrParentUrl('/dashboard')).toBe(false);
    });

    test('uses explicit current URLs when supplied', () => {
        const { isCurrentUrl } = useCurrentUrl();

        expect(isCurrentUrl('/dashboard', '/dashboard')).toBe(true);
        expect(isCurrentUrl('/dashboard', '/settings/profile')).toBe(false);
    });

    test('selects values based on the active URL', () => {
        const { whenCurrentUrl } = useCurrentUrl();

        expect(whenCurrentUrl('/settings/profile', 'active', 'inactive')).toBe(
            'active',
        );
        expect(whenCurrentUrl('/dashboard', 'active', 'inactive')).toBe(
            'inactive',
        );
    });
});
