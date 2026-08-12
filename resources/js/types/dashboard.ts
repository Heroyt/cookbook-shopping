import type { CalendarWeekProjection } from './calendar';
import type { SimplePlanSelection } from './simple-plan';

export type DashboardCalendarEntry = {
    id: number;
    recipeName: string;
    mealLabel: string;
    servingCount: string;
};

export type DashboardCalendarDay = {
    date: string;
    weekdayLabel: string;
    dateLabel: string;
    entries: DashboardCalendarEntry[];
};

export type DashboardLatestShoppingList = {
    id: number;
    generatedAt: string;
    sourceKind: 'simple_plan' | 'calendar';
    sourceLabel: string;
};

export type DashboardSetup = {
    recipeCount: number;
    ingredientCount: number;
    storeCount: number;
};

export type DashboardOverview = {
    familyName: string;
    today: string;
    week: CalendarWeekProjection;
    days: DashboardCalendarDay[];
    simplePlanSelections: SimplePlanSelection[];
    latestShoppingList: DashboardLatestShoppingList | null;
    setup: DashboardSetup;
};
