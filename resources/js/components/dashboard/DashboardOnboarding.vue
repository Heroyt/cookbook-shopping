<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as ingredientsIndex } from '@/routes/ingredients';
import { index as recipesIndex } from '@/routes/recipes';
import { index as storesIndex } from '@/routes/stores';
import type { DashboardSetup } from '@/types';

defineProps<{
    setup: DashboardSetup;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Začínáme</CardTitle>
            <CardDescription>
                Dokončete základní nastavení rodinné kuchařky.
            </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-3">
            <div class="flex flex-col items-start gap-3">
                <div class="flex items-center gap-2">
                    <p class="font-medium">1. Přidejte surovinu</p>
                    <Badge
                        :variant="
                            setup.ingredientCount > 0 ? 'secondary' : 'outline'
                        "
                    >
                        {{ setup.ingredientCount > 0 ? 'Hotovo' : 'Zbývá' }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Suroviny tvoří základ receptů a nákupních seznamů.
                </p>
                <Button
                    v-if="setup.ingredientCount === 0"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="ingredientsIndex()">Přidat surovinu</Link>
                </Button>
            </div>

            <div class="flex flex-col items-start gap-3">
                <div class="flex items-center gap-2">
                    <p class="font-medium">2. Vytvořte recept</p>
                    <Badge
                        :variant="
                            setup.recipeCount > 0 ? 'secondary' : 'outline'
                        "
                    >
                        {{ setup.recipeCount > 0 ? 'Hotovo' : 'Zbývá' }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Recept můžete přidat do kalendáře nebo rychlého plánu.
                </p>
                <Button
                    v-if="setup.recipeCount === 0"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="recipesIndex()">Vytvořit recept</Link>
                </Button>
            </div>

            <div class="flex flex-col items-start gap-3">
                <div class="flex items-center gap-2">
                    <p class="font-medium">3. Nastavte obchod</p>
                    <Badge
                        :variant="
                            setup.storeCount > 0 ? 'secondary' : 'outline'
                        "
                    >
                        {{ setup.storeCount > 0 ? 'Hotovo' : 'Zbývá' }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Obchod a jeho sekce určí pořadí nákupního seznamu.
                </p>
                <Button
                    v-if="setup.storeCount === 0"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="storesIndex()">Nastavit obchod</Link>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
