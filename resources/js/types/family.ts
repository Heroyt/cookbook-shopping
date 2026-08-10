export type FamilySummary = {
    id: number;
    name: string;
};

export type FamilyMember = {
    id: number;
    name: string;
    email: string;
};

export type FamilyDetail = FamilySummary & {
    members: FamilyMember[];
};
