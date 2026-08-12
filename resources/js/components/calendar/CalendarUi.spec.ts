/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import CalendarNutrition from './CalendarNutrition.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Calendar UI', () => {
    it('renders exact incomplete nutrition with its missing ingredients', async () => {
        const html = await renderToString(
            createSSRApp(CalendarNutrition, {
                nutrition: {
                    status: 'incomplete',
                    totals: {
                        energyKcal: '350.000000',
                        fatGrams: '8.000000',
                        proteinGrams: '17.500000',
                        carbohydrateGrams: '49.000000',
                    },
                    missingIngredientNames: ['Tajemství'],
                },
                label: 'Součet dne',
            }),
        );

        expect(html).toContain('Součet dne');
        expect(html).toContain('350 kcal');
        expect(html).toContain('17,5 g bílkovin');
        expect(html).toContain('Neúplné nutriční údaje');
        expect(html).toContain('Tajemství');
    });

    it('uses generated Wayfinder actions for every calendar write and generation intent', () => {
        const planner = readSource('./CalendarPlanner.vue');
        const entry = readSource('./CalendarEntryCard.vue');
        expect(planner).toContain('store.form()');
        expect(planner).toContain('generate.form()');
        expect(entry).toContain('update.form(entry.id)');
        expect(entry).toContain('destroy.form(entry.id)');
        expect(planner).not.toContain("action='/calendar");
        expect(entry).not.toContain("action='/calendar");
        expect(readSource('../../pages/calendar/Index.vue')).toContain(
            "from '@/routes/calendar'",
        );
        expect(readSource('../AppSidebar.vue')).toContain("title: 'Kalendář'");
    });

    it('keeps arbitrary dates, range convenience, archived restrictions, and errors accessible', () => {
        const planner = readSource('./CalendarPlanner.vue');
        const entry = readSource('./CalendarEntryCard.vue');
        expect(planner).toContain('name="dates[]"');
        expect(planner).toContain('Vybrat rozsah');
        expect(planner).toContain('Přidat libovolné datum');
        expect(planner).toContain('addManualDate');
        expect(planner).toContain('toggleDate(day.date');
        expect(planner).toContain('ref([...props.selectedDates])');
        expect(planner).toContain('watch(');
        expect(planner).toContain(':data-disabled="!dayHasEntries(day)"');
        expect(planner).not.toMatch(
            /selectedDates\.length === 0 \|\|\s*entryCount/,
        );
        expect(planner).toContain('<FieldSet v-if="showIndividualDates">');
        expect(planner).toContain('Vybrat jednotlivá data');
        expect(planner).toContain('<FieldError :errors="[errors.dates]" />');
        expect(entry).toContain('<DialogTitle>Upravit záznam</DialogTitle>');
        expect(entry).toContain('v-model:open="open"');
        expect(entry).toContain('@success="open = false"');
        expect(entry).toContain('entry.recipeArchived');
        expect(entry).toMatch(/Nejprve obnovte\s+recept/);
        expect(entry).toContain('<FieldError :errors="[errors.entry]" />');
    });

    it('uses responsive readable day cards and calendar-specific generated recovery', () => {
        const planner = readSource('./CalendarPlanner.vue');
        expect(planner).toContain('md:grid-cols-2 xl:grid-cols-3');
        expect(planner).not.toContain('grid-cols-7');
        expect(planner).not.toMatch(/space-[xy]-/);
        const generated = readSource('../../pages/calendar/Generated.vue');
        expect(generated).toContain('generation-source="calendar"');
        expect(generated).toContain('Výběr dat kalendáře zůstal zachovaný');
        expect(generated).toContain('Zpět do kalendáře');
        const shoppingList = readSource('../simple-plan/ShoppingListView.vue');
        expect(shoppingList).toContain("filter: 'all'");
        expect(shoppingList).toContain('Zobrazit recept');
    });

    it('formats stored serving counts for concise number inputs', () => {
        const entry = readSource('./CalendarEntryCard.vue');

        expect(entry).toContain("from '@/lib/formatDecimalInput'");
        expect(entry).toContain('formatDecimalInput(entry.servingCount)');
    });
});
