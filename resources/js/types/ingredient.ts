import type { StoreSectionIconName } from './store';

export type IngredientSummary = {
    id: number;
    name: string;
    photoUrl: string | null;
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
    nutrition: IngredientNutritionProfile | null;
};

export type IngredientAlternativeOption = { id: number; name: string };

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
    logoUrl?: string | null;
    sections: Array<{
        id: number;
        name: string;
        colour: string;
        icon?: StoreSectionIconName;
        iconUrl?: string | null;
    }>;
};
