<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
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
        <CardContent class="space-y-4 text-sm">
            <dl class="space-y-3">
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
                <p
                    v-for="choice in line.alternativeChoices"
                    :key="`${choice.originalIngredientName}:${choice.alternativeIngredientName}`"
                    class="text-muted-foreground"
                >
                    Použita alternativa
                    {{ choice.alternativeIngredientName }} místo
                    {{ choice.originalIngredientName }}.
                </p>
            </template>

            <p
                v-if="line.eligibleAlternatives.length > 0"
                class="text-muted-foreground"
            >
                Dostupné alternativy:
                {{
                    line.eligibleAlternatives
                        .map((item) => item.ingredientName)
                        .join(', ')
                }}
            </p>

            <Collapsible v-if="line.contributions.length > 0">
                <CollapsibleTrigger as-child>
                    <Button type="button" variant="ghost" size="sm">
                        <ChevronDownIcon data-icon="inline-start" />
                        Příspěvky receptů ({{ line.contributions.length }})
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <ul class="mt-2 space-y-2 border-l pl-4">
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
