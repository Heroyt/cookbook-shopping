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
