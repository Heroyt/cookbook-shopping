export type AgentCredentialAbility =
    'content:read' | 'cookbook:write' | 'planning:write' | 'destructive:write';

export type AgentCredentialStatus =
    'active' | 'revoked' | 'expired' | 'invalidated';

export type AgentCredentialSummary = {
    id: number;
    name: string;
    issuerName: string;
    abilities: AgentCredentialAbility[];
    status: AgentCredentialStatus;
    isIssuer: boolean;
    createdAt: string;
    expiresAt: string;
    lastUsedAt: string | null;
    revokedAt: string | null;
    revokedByName: string | null;
    revocationReason: 'revoked' | 'rotated' | null;
    rotatedToId: number | null;
};

export type AgentCredentialSecret = {
    name: string;
    secret: string;
};
