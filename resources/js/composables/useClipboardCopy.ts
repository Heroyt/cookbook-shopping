import { shallowRef } from 'vue';

export type ClipboardCopyState = 'idle' | 'copied' | 'failed';

const writeClipboard = async (value: string): Promise<ClipboardCopyState> => {
    try {
        await navigator.clipboard.writeText(value);

        return 'copied';
    } catch {
        return 'failed';
    }
};

export const useClipboardCopy = () => {
    const copyState = shallowRef<ClipboardCopyState>('idle');

    const copy = async (value: string): Promise<ClipboardCopyState> => {
        copyState.value = 'idle';
        copyState.value = await writeClipboard(value);

        return copyState.value;
    };

    return { copy, copyState };
};

export const useTargetedClipboardCopy = <Target extends string>() => {
    const copyTarget = shallowRef<Target | null>(null);
    const copyState = shallowRef<ClipboardCopyState>('idle');
    let latestAttempt = 0;

    const copy = async (
        target: Target,
        value: string,
    ): Promise<ClipboardCopyState> => {
        const attempt = ++latestAttempt;
        copyTarget.value = target;
        copyState.value = 'idle';
        const result = await writeClipboard(value);

        if (attempt === latestAttempt) {
            copyState.value = result;
        }

        return result;
    };

    return { copy, copyState, copyTarget };
};
