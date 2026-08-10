export type IngredientSummary = {
    id: number;
    name: string;
    description: string | null;
    metricQuantity: string | null;
    metricUnit: 'g' | 'ml';
    pieceCount: string | null;
    quantities: string[];
};
