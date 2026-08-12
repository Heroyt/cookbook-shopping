<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ChevronDownIcon, ReplaceIcon, RotateCcwIcon } from '@lucide/vue';
import { computed, shallowRef } from 'vue';
import {
    destroyAlternative as destroyCalendarAlternative,
    storeAlternative as storeCalendarAlternative,
} from '@/actions/App/MealPlanning/Http/Controllers/CalendarController';
import {
    destroyAlternative as destroySimplePlanAlternative,
    storeAlternative as storeSimplePlanAlternative,
} from '@/actions/App/MealPlanning/Http/Controllers/SimplePlanController';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { FieldError } from '@/components/ui/field';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import type { ShoppingListLinePresentation } from '@/types';

const props = withDefaults(
    defineProps<{
        line: ShoppingListLinePresentation;
        generationSource?: 'simple-plan' | 'calendar';
        readOnly?: boolean;
    }>(),
    { generationSource: 'simple-plan', readOnly: false },
);
const open = shallowRef(false);
const requiredLabel = computed(() =>
    props.line.quantities
        .map(
            (quantity) =>
                `${quantity.required.approximate ? '≈' : ''}${quantity.required.label}`,
        )
        .join(' · '),
);
const storeAlternative = computed(() =>
    props.generationSource === 'calendar'
        ? storeCalendarAlternative
        : storeSimplePlanAlternative,
);
const destroyAlternative = computed(() =>
    props.generationSource === 'calendar'
        ? destroyCalendarAlternative
        : destroySimplePlanAlternative,
);
</script>

<template>
    <Collapsible
        v-model:open="open"
        class="break-inside-avoid rounded-md border bg-background select-text"
    >
        <CollapsibleTrigger as-child>
            <button
                type="button"
                class="grid w-full grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-3 px-3 py-2 text-left hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                :aria-label="`${open ? 'Skrýt' : 'Zobrazit'} detail položky ${line.ingredientName}`"
            >
                <span class="min-w-0 font-medium">{{
                    line.ingredientName
                }}</span>
                <span class="text-sm font-semibold tabular-nums"
                    >{{ line.purchasePackages }} bal.</span
                >
                <span
                    class="hidden text-sm text-muted-foreground tabular-nums sm:inline"
                    >{{ requiredLabel }}</span
                >
                <ChevronDownIcon
                    class="size-4 transition-transform"
                    :class="open ? 'rotate-180' : ''"
                    aria-hidden="true"
                />
            </button>
        </CollapsibleTrigger>
        <CollapsibleContent force-mount class="data-[state=closed]:hidden">
            <div class="flex flex-col gap-4 border-t px-3 py-3 text-sm">
                <dl class="flex flex-col gap-3">
                    <div
                        v-for="quantity in line.quantities"
                        :key="quantity.kind"
                        class="grid grid-cols-3 gap-2"
                    >
                        <div>
                            <dt class="text-muted-foreground">Potřeba</dt>
                            <dd class="font-medium tabular-nums">
                                <span
                                    v-if="quantity.required.approximate"
                                    aria-label="přibližně"
                                    >≈</span
                                >{{ quantity.required.label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Nákup</dt>
                            <dd class="font-medium tabular-nums">
                                <span
                                    v-if="quantity.purchased.approximate"
                                    aria-label="přibližně"
                                    >≈</span
                                >{{ quantity.purchased.label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Přebytek</dt>
                            <dd class="font-medium tabular-nums">
                                <span
                                    v-if="quantity.surplus.approximate"
                                    aria-label="přibližně"
                                    >≈</span
                                >{{ quantity.surplus.label }}
                            </dd>
                        </div>
                    </div>
                </dl>

                <template v-if="line.alternativeChoices.length">
                    <Separator />
                    <div
                        v-for="choice in line.alternativeChoices"
                        :key="choice.originalIngredientId"
                        class="flex flex-col items-start gap-2"
                    >
                        <p class="text-muted-foreground">
                            Použita alternativa
                            {{ choice.alternativeIngredientName }} místo
                            {{ choice.originalIngredientName }}.
                        </p>
                        <Form
                            v-if="!readOnly"
                            v-bind="
                                destroyAlternative.form(
                                    choice.originalIngredientId,
                                )
                            "
                            v-slot="{ errors, processing }"
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                :disabled="processing"
                            >
                                <Spinner
                                    v-if="processing"
                                    data-icon="inline-start"
                                /><RotateCcwIcon
                                    v-else
                                    data-icon="inline-start"
                                />Vrátit původní surovinu
                            </Button>
                            <FieldError
                                :errors="[errors.alternative_ingredient_id]"
                            />
                        </Form>
                    </div>
                </template>

                <div
                    v-if="!readOnly && line.eligibleAlternatives.length"
                    class="flex flex-col items-start gap-2"
                >
                    <p class="text-muted-foreground">Dostupné alternativy</p>
                    <Form
                        v-for="alternative in line.eligibleAlternatives"
                        :key="alternative.ingredientId"
                        v-bind="storeAlternative.form()"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="original_ingredient_id"
                            :value="line.ingredientId"
                        />
                        <input
                            type="hidden"
                            name="alternative_ingredient_id"
                            :value="alternative.ingredientId"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            size="sm"
                            :disabled="processing"
                        >
                            <Spinner
                                v-if="processing"
                                data-icon="inline-start"
                            /><ReplaceIcon
                                v-else
                                data-icon="inline-start"
                            />Použít alternativu
                            {{ alternative.ingredientName }}
                        </Button>
                        <FieldError
                            :errors="[errors.alternative_ingredient_id]"
                        />
                    </Form>
                </div>

                <div v-if="line.contributions.length">
                    <p class="font-medium">
                        Příspěvky receptů ({{ line.contributions.length }})
                    </p>
                    <ul class="mt-2 flex flex-col gap-2 border-l pl-4">
                        <li
                            v-for="contribution in line.contributions"
                            :key="contribution.contributionKey"
                        >
                            <span class="font-medium">{{
                                contribution.recipeName
                            }}</span>
                            <span class="text-muted-foreground">
                                —
                                <span
                                    v-if="contribution.required.approximate"
                                    aria-label="přibližně"
                                    >≈</span
                                >{{ contribution.required.label }}</span
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </CollapsibleContent>
    </Collapsible>
</template>
