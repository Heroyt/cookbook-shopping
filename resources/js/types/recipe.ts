export type RecipeIngredientOption = {
    id: number;
    name: string;
    kinds: Array<'grams' | 'millilitres' | 'piece'>;
};

export type RecipeTagOption = { id: number; name: string };

export type RecipeSummary = {
    id: number;
    name: string;
    baseServings: string;
    version: number;
    sourceUrl: string | null;
    preparationMinutes: number | null;
    cookingMinutes: number | null;
    notes: string | null;
    archived: boolean;
    ingredients: Array<{
        id: number;
        ingredientId: number;
        ingredientName: string;
        quantity: string;
        quantityKind: 'grams' | 'millilitres' | 'piece';
    }>;
    steps: Array<{ id: number; instruction: string }>;
    tags: RecipeTagOption[];
    matchReasons: Array<{
        kind: 'name' | 'tag' | 'ingredient';
        label: string;
    }>;
    nutrition: {
        status: 'calculated' | 'override' | 'incomplete';
        perServing: {
            energyKcal: string;
            fatGrams: string;
            proteinGrams: string;
            carbohydrateGrams: string;
        } | null;
        missingIngredientNames: string[];
    };
    nutritionOverride: {
        energyKcal: string;
        fatGrams: string;
        proteinGrams: string;
        carbohydrateGrams: string;
    } | null;
};
