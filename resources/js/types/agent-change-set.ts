export type AgentChangeSetSummary = {
    id: string;
    title: string | null;
    credentialName: string;
    issuerName: string;
    resourceTypes: string[];
    outcome: 'applied';
    operationCount: number;
    appliedAt: string;
};

export type AgentChangeSetDetail = AgentChangeSetSummary & {
    digest: string;
    clientRequestId: string;
    sourceUrls: string[];
    note: string | null;
    canonicalRequest: Record<string, unknown>;
    preview: Record<string, unknown>;
    warningAcknowledgements: string[];
    identifierMappings: Record<string, number>;
    result: Record<string, unknown>;
};

export type AgentChangeSetOption = { id: number; name: string };

export type AgentChangeSetFilters = {
    credentialId: string | null;
    issuerUserId: string | null;
    dateFrom: string | null;
    dateTo: string | null;
    resourceType: string | null;
    outcome: string | null;
};
