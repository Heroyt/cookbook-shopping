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
    exact: string;
    label: string;
    value: string;
    unit: string;
    approximate: boolean;
};

export type ShoppingListLinePresentation = {
    ingredientId: number;
    ingredientName: string;
    package: {
        grams: string | null;
        millilitres: string | null;
        piece: string | null;
    };
    purchasePackages: string;
    quantities: Array<{
        kind: 'grams' | 'millilitres' | 'piece';
        required: QuantityDisplay;
        purchased: QuantityDisplay;
        surplus: QuantityDisplay;
    }>;
    contributions: Array<{
        contributionKey: string;
        recipeId: number;
        recipeName: string;
        originalIngredientId: number;
        originalIngredientName: string;
        quantityKind: 'grams' | 'millilitres' | 'piece';
        required: QuantityDisplay;
        packageFraction: string;
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

export type SavedShoppingListSummary = {
    id: number;
    generatedAt: string;
    sourceKind: 'simple_plan' | 'calendar';
    sourceLabel: string;
    schemaVersion: number;
};

export type SavedShoppingListPagination = {
    previousUrl: string | null;
    nextUrl: string | null;
};

export type SavedShoppingListDetail = SavedShoppingListSummary &
    (
        | {
              status: 'unavailable';
              unavailableMessage: string;
          }
        | {
              status: 'available';
              locale: string;
              source:
                  | {
                        kind: 'simple_plan';
                        recipes: Array<{
                            recipeId: number;
                            recipeName: string;
                            servingCount: string;
                            servingCountLabel: string;
                        }>;
                    }
                  | { kind: 'calendar'; dates: string[]; dateLabels: string[] };
              appliedAlternatives: ShoppingListLinePresentation['alternativeChoices'];
              shoppingList: ShoppingListPresentation;
          }
    );

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
