/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import type { DashboardOverview as DashboardOverviewData } from '@/types';
import DashboardFamilyEmpty from './DashboardFamilyEmpty.vue';
import DashboardOverview from './DashboardOverview.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

const overview: DashboardOverviewData = {
    familyName: 'Rodina Novákových',
    today: '2026-08-12',
    week: {
        startsOn: '2026-08-10',
        endsOn: '2026-08-16',
        previousStartsOn: '2026-08-03',
        nextStartsOn: '2026-08-17',
    },
    days: Array.from({ length: 7 }, (_, offset) => ({
        date: `2026-08-${String(10 + offset).padStart(2, '0')}`,
        weekdayLabel: [
            'pondělí',
            'úterý',
            'středa',
            'čtvrtek',
            'pátek',
            'sobota',
            'neděle',
        ][offset]!,
        dateLabel: `${10 + offset}. 8.`,
        entries:
            offset === 2
                ? [
                      {
                          id: 1,
                          recipeName: 'Rajčatová polévka',
                          mealLabel: 'oběd',
                          servingCount: '2.5',
                      },
                  ]
                : [],
    })),
    simplePlanSelections: [
        {
            recipeId: 3,
            recipeName: 'Lívance',
            servingCount: '3.5',
            available: true,
        },
    ],
    latestShoppingList: {
        id: 4,
        generatedAt: '12. 8. 2026 08:15:30,123456',
        sourceKind: 'calendar',
        sourceLabel: 'Kalendář',
    },
    setup: {
        recipeCount: 1,
        ingredientCount: 1,
        storeCount: 0,
    },
};

describe('Dashboard UI', () => {
    it('renders current-family workflow summaries and setup guidance', async () => {
        const html = await renderToString(
            createSSRApp(DashboardOverview, { overview }),
        );

        expect(html).toContain('Rodina Novákových');
        expect(html).toContain('Dnes vaříme');
        expect(html).toContain('Rajčatová polévka');
        expect(html).toContain('2,5 porce');
        expect(html).toContain('Rozpracovaný rychlý plán');
        expect(html).toContain('Lívance');
        expect(html).toContain('Poslední nákupní seznam');
        expect(html).toContain('12. 8. 2026 08:15:30');
        expect(html).toContain('Tento týden');
        expect(html).toContain('Rychlé akce');
        expect(html).toContain('Nastavte obchod');
    });

    it('renders a focused Czech empty state before a family exists', async () => {
        const html = await renderToString(createSSRApp(DashboardFamilyEmpty));

        expect(html).toContain('Nejprve vytvořte rodinu');
        expect(html).toContain('Vytvořit rodinu');
    });

    it('uses generated routes, responsive grids, and shadcn composition', () => {
        const sources = [
            readSource('./DashboardOverview.vue'),
            readSource('./DashboardStatusCards.vue'),
            readSource('./DashboardWeekOverview.vue'),
            readSource('./DashboardQuickActions.vue'),
            readSource('./DashboardOnboarding.vue'),
            readSource('./DashboardFamilyEmpty.vue'),
        ].join('\n');

        expect(sources).toContain("from '@/routes/calendar'");
        expect(sources).toContain("from '@/routes/simple-plan'");
        expect(sources).toContain("from '@/routes/shopping-list-history'");
        expect(sources).toContain('md:grid-cols-3');
        expect(sources).toContain('xl:grid-cols-7');
        expect(sources).not.toContain("href='/");
        expect(sources).not.toMatch(/space-[xy]-/);
        expect(sources).not.toMatch(/dark:/);
        expect(sources).not.toMatch(/<Card>\s*<CardContent>/);
    });
});
