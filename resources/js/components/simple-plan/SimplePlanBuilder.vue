<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PlusIcon, ShoppingCartIcon, Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
import SimplePlanController from '@/actions/App/MealPlanning/Http/Controllers/SimplePlanController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { SimplePlanRecipeOption, SimplePlanSelection } from '@/types';

defineProps<{
    recipes: SimplePlanRecipeOption[];
    selections: SimplePlanSelection[];
}>();

const selectedRecipeId = ref('');
</script>

<template>
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(20rem,1fr)_2fr]">
        <Card>
            <CardHeader>
                <CardTitle>Přidat recept</CardTitle>
                <CardDescription>
                    Zadejte počet porcí. Opakované přidání stejného receptu se
                    přesně přičte k jeho současnému počtu.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="SimplePlanController.store.form()"
                    reset-on-success
                    class="space-y-4"
                    @success="selectedRecipeId = ''"
                    v-slot="{ errors, processing }"
                >
                    <FieldGroup>
                        <Field :data-invalid="Boolean(errors.recipe_id)">
                            <FieldLabel for="simple-plan-recipe">
                                Recept
                            </FieldLabel>
                            <Select
                                v-model="selectedRecipeId"
                                name="recipe_id"
                                required
                            >
                                <SelectTrigger
                                    id="simple-plan-recipe"
                                    :aria-invalid="Boolean(errors.recipe_id)"
                                >
                                    <SelectValue placeholder="Vyberte recept" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="recipe in recipes"
                                        :key="recipe.id"
                                        :value="String(recipe.id)"
                                    >
                                        {{ recipe.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[errors.recipe_id]" />
                        </Field>

                        <Field :data-invalid="Boolean(errors.serving_count)">
                            <FieldLabel for="simple-plan-serving-count">
                                Počet porcí
                            </FieldLabel>
                            <Input
                                id="simple-plan-serving-count"
                                name="serving_count"
                                type="number"
                                min="0.000001"
                                step="0.000001"
                                default-value="1"
                                required
                                inputmode="decimal"
                                :aria-invalid="Boolean(errors.serving_count)"
                            />
                            <FieldDescription>
                                Lze zadat i desetinný počet, například 1,5.
                            </FieldDescription>
                            <FieldError :errors="[errors.serving_count]" />
                        </Field>
                    </FieldGroup>

                    <Button
                        type="submit"
                        :disabled="processing || recipes.length === 0"
                    >
                        <Spinner v-if="processing" aria-hidden="true" />
                        <PlusIcon v-else data-icon="inline-start" />
                        Přidat do plánu
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Vybrané recepty</CardTitle>
                <CardDescription>
                    Rychlý plán je dočasný a po úspěšném vytvoření nákupního
                    seznamu se neukládá.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <Empty v-if="selections.length === 0">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <ShoppingCartIcon />
                        </EmptyMedia>
                        <EmptyTitle>Rychlý plán je prázdný</EmptyTitle>
                        <EmptyDescription>
                            Vyberte recept a zadejte požadovaný počet porcí.
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent />
                </Empty>

                <ul
                    v-else
                    class="space-y-3"
                    aria-label="Recepty v rychlém plánu"
                >
                    <li
                        v-for="selection in selections"
                        :key="selection.recipeId"
                        class="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">
                                {{ selection.recipeName }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Celkem
                                {{ selection.servingCount.replace('.', ',') }}
                                porce
                            </p>
                            <Badge
                                v-if="!selection.available"
                                variant="destructive"
                            >
                                Recept už není dostupný
                            </Badge>
                        </div>
                        <Form
                            v-bind="
                                SimplePlanController.destroy.form(
                                    selection.recipeId,
                                )
                            "
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="ghost"
                                size="sm"
                                :disabled="processing"
                                :aria-label="`Odebrat recept ${selection.recipeName} z rychlého plánu`"
                            >
                                <Spinner v-if="processing" aria-hidden="true" />
                                <Trash2Icon v-else data-icon="inline-start" />
                                Odebrat
                            </Button>
                        </Form>
                    </li>
                </ul>

                <Form
                    v-if="selections.length > 0"
                    v-bind="SimplePlanController.generate.form()"
                    v-slot="{ errors, processing }"
                    class="space-y-3 border-t pt-4"
                >
                    <FieldError :errors="[errors.plan]" />
                    <Button type="submit" size="lg" :disabled="processing">
                        <Spinner v-if="processing" aria-hidden="true" />
                        <ShoppingCartIcon v-else data-icon="inline-start" />
                        Vytvořit nákupní seznam
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
