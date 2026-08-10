export type IngredientSummary = {
    id: number;
    name: string;
    description: string | null;
    metricQuantity: string | null;
    metricUnit: 'g' | 'ml';
    pieceCount: string | null;
    quantities: string[];
    storeId: number | null;
    storeSectionId: number | null;
    placement: string | null;
    archived: boolean;
    alternatives: Array<{ id: number; name: string; archived: boolean }>;
    alternativeOptions: Array<{ id: number; name: string }>;
    nutrition: IngredientNutritionProfile | null;
};

export type IngredientNutritionProfile = {
    basisKind: 'package' | 'grams' | 'millilitres' | 'piece';
    basisQuantity: string;
    energyKcal: string;
    fatGrams: string;
    proteinGrams: string;
    carbohydrateGrams: string;
};

export type IngredientPlacementStore = {
    id: number;
    name: string;
    sections: Array<{
        id: number;
        name: string;
        colour: string;
    }>;
};
