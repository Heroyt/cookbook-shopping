<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ChevronDownIcon, ReplaceIcon, RotateCcwIcon } from '@lucide/vue';
import {
    destroyAlternative,
    storeAlternative,
} from '@/actions/App/MealPlanning/Http/Controllers/SimplePlanController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import type { ShoppingListLinePresentation } from '@/types';

defineProps<{ line: ShoppingListLinePresentation }>();
</script>

<template>
    <Card class="break-inside-avoid select-text">
        <CardHeader class="gap-1 pb-3">
            <CardTitle class="text-base">{{ line.ingredientName }}</CardTitle>
            <p class="text-2xl font-semibold tabular-nums">
                {{ line.purchasePackages }} balení
            </p>
        </CardHeader>
        <CardContent class="flex flex-col gap-4 text-sm">
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

            <template v-if="line.alternativeChoices.length > 0">
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
                        v-bind="
                            destroyAlternative.form(choice.originalIngredientId)
                        "
                        v-slot="{ processing }"
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
                                aria-hidden="true"
                            />
                            <RotateCcwIcon v-else data-icon="inline-start" />
                            Vrátit původní surovinu
                        </Button>
                    </Form>
                </div>
            </template>

            <div
                v-if="line.eligibleAlternatives.length > 0"
                class="flex flex-col items-start gap-2"
            >
                <p class="text-muted-foreground">Dostupné alternativy</p>
                <Form
                    v-for="alternative in line.eligibleAlternatives"
                    :key="alternative.ingredientId"
                    v-bind="storeAlternative.form()"
                    v-slot="{ processing }"
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
                            aria-hidden="true"
                        />
                        <ReplaceIcon v-else data-icon="inline-start" />
                        Použít alternativu {{ alternative.ingredientName }}
                    </Button>
                </Form>
            </div>

            <Collapsible v-if="line.contributions.length > 0">
                <CollapsibleTrigger as-child>
                    <Button type="button" variant="ghost" size="sm">
                        <ChevronDownIcon data-icon="inline-start" />
                        Příspěvky receptů ({{ line.contributions.length }})
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <ul class="mt-2 flex flex-col gap-2 border-l pl-4">
                        <li
                            v-for="contribution in line.contributions"
                            :key="`${contribution.recipeId}:${contribution.originalIngredientName}`"
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
                                >{{ contribution.required.label }}
                            </span>
                        </li>
                    </ul>
                </CollapsibleContent>
            </Collapsible>
        </CardContent>
    </Card>
</template>
