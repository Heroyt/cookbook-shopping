export type StoreSectionIconName =
    | 'apple'
    | 'carrot'
    | 'croissant'
    | 'milk'
    | 'beef'
    | 'fish'
    | 'snowflake'
    | 'wine'
    | 'cookie'
    | 'package'
    | 'sparkles'
    | 'cross'
    | 'banana'
    | 'egg'
    | 'nut'
    | 'wheat'
    | 'soup'
    | 'cake-slice'
    | 'cup-soda'
    | 'ham'
    | 'drumstick'
    | 'salad'
    | 'broom'
    | 'cooking-pot'
    | 'cheese';

export type StoreSummary = {
    id: number;
    name: string;
    logoUrl: string | null;
    sectionOrderVersion: number;
    sections: StoreSectionAssociationSummary[];
};

export type StoreSectionSummary = {
    id: number;
    name: string;
    colour: string;
    icon: StoreSectionIconName;
    iconUrl: string | null;
    associationCount: number;
    placementCount: number;
};

export type StoreSectionAssociationSummary = Omit<
    StoreSectionSummary,
    'associationCount' | 'iconUrl' | 'placementCount'
> & {
    position: number;
};
