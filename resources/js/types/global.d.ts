import type { AgentCredentialSecret } from '@/types/agent-credential';
import type { Auth } from '@/types/auth';
import type { FamilySummary } from '@/types/family';
import type {
    IngredientStoreOption,
    IngredientStoreSectionOption,
} from '@/types/ingredient';
import type { FlashToast } from '@/types/ui';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            toast?: FlashToast;
            agentCredentialSecret?: AgentCredentialSecret;
            createdIngredient?: { id: number; name: string };
            createdStore?: IngredientStoreOption;
            createdStoreSection?: IngredientStoreSectionOption;
        };
        sharedPageProps: {
            name: string;
            auth: Auth;
            currentFamily: FamilySummary | null;
            families: FamilySummary[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
