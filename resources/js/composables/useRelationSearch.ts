import { HttpCancelledError } from '@inertiajs/core';
import { useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, readonly, shallowRef, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';

export type RelationSearchOption = {
    id: number;
    name: string;
};

type RelationSearchResponse<TOption extends RelationSearchOption> = {
    data: TOption[];
    nextCursor: string | null;
};

type RelationSearchPayload = {
    q: string;
    cursor: string;
    limit: number;
};

type UseRelationSearchOptions<TOption extends RelationSearchOption> = {
    endpoint: MaybeRefOrGetter<string>;
    initialOptions?: MaybeRefOrGetter<TOption[]>;
    limit?: number;
    debounceMilliseconds?: number;
};

const mergeOptions = <TOption extends RelationSearchOption>(
    preferred: TOption[],
    loaded: TOption[],
): TOption[] => {
    const optionsById = new Map<number, TOption>();

    for (const option of [...preferred, ...loaded]) {
        if (!optionsById.has(option.id)) {
            optionsById.set(option.id, option);
        }
    }

    return [...optionsById.values()];
};

export const useRelationSearch = <TOption extends RelationSearchOption>(
    options: UseRelationSearchOptions<TOption>,
) => {
    const query = shallowRef('');
    const results = shallowRef<TOption[]>([
        ...(options.initialOptions ? toValue(options.initialOptions) : []),
    ]);
    const nextCursor = shallowRef<string | null>(null);
    const hasLoaded = shallowRef(false);
    const loading = shallowRef(false);
    const failed = shallowRef(false);
    const http = useHttp<
        RelationSearchPayload,
        RelationSearchResponse<TOption>
    >({
        q: '',
        cursor: '',
        limit: options.limit ?? 20,
    });
    let debounceTimer: ReturnType<typeof setTimeout> | undefined;
    let activeRequest = 0;

    const load = async (append = false): Promise<void> => {
        if (append && nextCursor.value === null) {
            return;
        }

        activeRequest += 1;
        const request = activeRequest;
        http.cancel();
        http.q = query.value.trim();
        http.cursor = append ? (nextCursor.value ?? '') : '';
        loading.value = true;
        failed.value = false;

        try {
            const response = await http.get(toValue(options.endpoint));

            if (request !== activeRequest) {
                return;
            }

            const pinned = options.initialOptions
                ? toValue(options.initialOptions)
                : [];
            results.value = append
                ? mergeOptions(results.value, response.data)
                : mergeOptions(pinned, response.data);
            nextCursor.value = response.nextCursor;
            hasLoaded.value = true;
        } catch (error) {
            if (!(error instanceof HttpCancelledError)) {
                failed.value = true;
            }
        } finally {
            if (request === activeRequest) {
                loading.value = false;
            }
        }
    };

    const ensureLoaded = async (): Promise<void> => {
        if (!hasLoaded.value) {
            await load();
        }
    };

    const refresh = async (): Promise<void> => {
        await load();
    };

    watch(query, () => {
        if (!hasLoaded.value) {
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(
            () => void load(),
            options.debounceMilliseconds ?? 250,
        );
    });

    watch(
        () => toValue(options.endpoint),
        () => {
            nextCursor.value = null;
            hasLoaded.value = false;
            results.value = options.initialOptions
                ? [...toValue(options.initialOptions)]
                : [];
        },
    );

    if (options.initialOptions) {
        watch(
            () => toValue(options.initialOptions!),
            (initialOptions) => {
                results.value = mergeOptions(initialOptions, results.value);
            },
            { deep: true },
        );
    }

    onBeforeUnmount(() => {
        clearTimeout(debounceTimer);
        http.cancel();
    });

    return {
        query,
        results: readonly(results),
        nextCursor: readonly(nextCursor),
        hasLoaded: readonly(hasLoaded),
        loading: readonly(loading),
        failed: readonly(failed),
        ensureLoaded,
        loadMore: () => load(true),
        refresh,
    };
};
