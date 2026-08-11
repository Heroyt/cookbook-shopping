<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Cookbook/Http/Controllers/RecipeController';
import CreateRecipeForm from '@/components/recipes/CreateRecipeForm.vue';
import RecipeList from '@/components/recipes/RecipeList.vue';
import RecipeTagManager from '@/components/recipes/RecipeTagManager.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/recipes';
import type {
    RecipeIngredientOption,
    RecipeSummary,
    RecipeTagOption,
} from '@/types';

defineProps<{
    recipes: RecipeSummary[];
    ingredients: RecipeIngredientOption[];
    tags: RecipeTagOption[];
    filter: 'active' | 'archived' | 'all';
    search: string;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Recepty', href: index() }] },
});
</script>

<template>
    <Head title="Recepty" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Recepty</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Spravujte úplné recepty aktuální rodiny včetně surovin, postupu
                a výživy.
            </p>
        </div>

        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(22rem,1fr)_2fr]"
        >
            <div class="space-y-6">
                <Card
                    ><CardHeader
                        ><CardTitle>Vytvořit recept</CardTitle
                        ><CardDescription
                            >Recept se uloží najednou jako jeden
                            celek.</CardDescription
                        ></CardHeader
                    ><CardContent
                        ><CreateRecipeForm
                            :ingredients="ingredients"
                            :tags="tags" /></CardContent
                ></Card>
                <Card
                    ><CardHeader
                        ><CardTitle>Štítky receptů</CardTitle
                        ><CardDescription
                            >Štítky jsou společné pro aktuální
                            rodinu.</CardDescription
                        ></CardHeader
                    ><CardContent><RecipeTagManager :tags="tags" /></CardContent
                ></Card>
            </div>

            <Card>
                <CardHeader
                    ><CardTitle>Recepty aktuální rodiny</CardTitle
                    ><CardDescription
                        >Každý člen rodiny má stejná práva ke čtení i
                        úpravám.</CardDescription
                    ></CardHeader
                >
                <CardContent class="space-y-4">
                    <Form
                        v-bind="RecipeController.index.form()"
                        class="flex gap-2"
                        v-slot="{ processing }"
                    >
                        <input type="hidden" name="filter" :value="filter" />
                        <label for="recipe-search" class="sr-only"
                            >Hledat v receptech, štítcích a surovinách</label
                        >
                        <Input
                            id="recipe-search"
                            name="search"
                            type="search"
                            :default-value="search"
                            placeholder="Hledat recept, štítek nebo surovinu"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="processing"
                            >Hledat</Button
                        >
                    </Form>
                    <div
                        class="flex flex-wrap gap-2"
                        aria-label="Filtr receptů"
                    >
                        <Button
                            v-for="option in [
                                { value: 'active', label: 'Aktivní' },
                                { value: 'archived', label: 'Archivované' },
                                { value: 'all', label: 'Všechny' },
                            ]"
                            :key="option.value"
                            as-child
                            size="sm"
                            :variant="
                                filter === option.value ? 'default' : 'outline'
                            "
                        >
                            <Link
                                :href="
                                    index({
                                        query: {
                                            filter: option.value,
                                            search: search || undefined,
                                        },
                                    })
                                "
                                >{{ option.label }}</Link
                            >
                        </Button>
                    </div>
                    <RecipeList
                        :recipes="recipes"
                        :ingredients="ingredients"
                        :tags="tags"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
