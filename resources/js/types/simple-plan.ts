export type SimplePlanRecipeOption = {
    id: number;
    name: string;
};

export type SimplePlanSelection = {
    recipeId: number;
    recipeName: string;
    servingCount: string;
    available: boolean;
};

export type QuantityDisplay = {
    label: string;
    value: string;
    unit: string;
    approximate: boolean;
};

export type ShoppingListLinePresentation = {
    ingredientId: number;
    ingredientName: string;
    purchasePackages: string;
    quantities: Array<{
        kind: 'grams' | 'millilitres' | 'piece';
        required: QuantityDisplay;
        purchased: QuantityDisplay;
        surplus: QuantityDisplay;
    }>;
    contributions: Array<{
        recipeId: number;
        recipeName: string;
        originalIngredientId: number;
        originalIngredientName: string;
        quantityKind: 'grams' | 'millilitres' | 'piece';
        required: QuantityDisplay;
    }>;
    eligibleAlternatives: Array<{
        ingredientId: number;
        ingredientName: string;
    }>;
    alternativeChoices: Array<{
        originalIngredientId: number;
        originalIngredientName: string;
        alternativeIngredientId: number;
        alternativeIngredientName: string;
    }>;
};

export type ShoppingListPresentation = {
    storeGroups: Array<{
        storeId: number;
        storeName: string;
        sections: Array<{
            sectionId: number;
            sectionName: string;
            lines: ShoppingListLinePresentation[];
        }>;
        unsectionedLines: ShoppingListLinePresentation[];
    }>;
    unplacedLines: ShoppingListLinePresentation[];
};

export type ShoppingListProblem = {
    problemKey: string;
    recipeId: number;
    recipeName: string;
    ingredientId: number;
    ingredientName: string;
    quantityLabel: string;
    message: string;
};
