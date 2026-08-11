<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Cookbook/Http/Controllers/RecipeController';
import CreateRecipeDialog from '@/components/recipes/CreateRecipeDialog.vue';
import ManageRecipeTagsDialog from '@/components/recipes/ManageRecipeTagsDialog.vue';
import RecipeList from '@/components/recipes/RecipeList.vue';
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
    editRecipeId: number | null;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Recepty', href: index() }] },
});
</script>

<template>
    <Head title="Recepty" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Recepty</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Spravujte úplné recepty aktuální rodiny včetně surovin,
                    postupu a výživy.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <ManageRecipeTagsDialog :tags="tags" />
                <CreateRecipeDialog :ingredients="ingredients" :tags="tags" />
            </div>
        </div>

        <Card>
            <CardHeader
                ><CardTitle>Recepty aktuální rodiny</CardTitle
                ><CardDescription
                    >Každý člen rodiny má stejná práva ke čtení i
                    úpravám.</CardDescription
                ></CardHeader
            >
            <CardContent>
                <div class="flex flex-col gap-4">
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
                        :edit-recipe-id="editRecipeId"
                    />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
