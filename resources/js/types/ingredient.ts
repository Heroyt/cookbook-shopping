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
