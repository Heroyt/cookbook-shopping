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
};

export type StoreSectionAssociationSummary = StoreSectionSummary & {
    position: number;
};
