export type CalendarNutritionProjection = {
    status: 'complete' | 'incomplete' | 'calculated' | 'override';
    source?: 'calculated' | 'override';
    totals: {
        energyKcal: string;
        fatGrams: string;
        proteinGrams: string;
        carbohydrateGrams: string;
    };
    missingIngredientNames: string[];
};

export type CalendarEntryProjection = {
    id: number;
    recipeId: number;
    recipeName: string;
    recipeArchived: boolean;
    date: string;
    mealLabel: string | null;
    servingCount: string;
    nutrition: CalendarNutritionProjection;
};

export type CalendarDayProjection = {
    date: string;
    weekdayLabel: string;
    dateLabel: string;
    groups: Array<{
        key: string;
        label: string;
        mealLabel: string | null;
        entries: CalendarEntryProjection[];
    }>;
    nutrition: CalendarNutritionProjection;
};

export type CalendarRecipeOption = { id: number; name: string };
export type CalendarMealLabelOption = { value: string; label: string };
export type CalendarWeekProjection = {
    startsOn: string;
    endsOn: string;
    previousStartsOn: string;
    nextStartsOn: string;
};
