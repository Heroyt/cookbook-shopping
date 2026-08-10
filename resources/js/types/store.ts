export type StoreSummary = {
    id: number;
    name: string;
    sectionOrderVersion: number;
    sections: StoreSectionAssociationSummary[];
};

export type StoreSectionSummary = {
    id: number;
    name: string;
    colour: string;
    associationCount: number;
    placementCount: number;
};

export type StoreSectionAssociationSummary = Omit<
    StoreSectionSummary,
    'associationCount' | 'placementCount'
> & {
    position: number;
};
