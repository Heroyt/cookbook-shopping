<script setup lang="ts">
import { WheatIcon } from '@lucide/vue';
import ArchiveIngredientAlertDialog from '@/components/ingredients/ArchiveIngredientAlertDialog.vue';
import EditIngredientDialog from '@/components/ingredients/EditIngredientDialog.vue';
import IngredientAlternatives from '@/components/ingredients/IngredientAlternatives.vue';
import RestoreIngredientButton from '@/components/ingredients/RestoreIngredientButton.vue';
import EntityImageUpload from '@/components/media/EntityImageUpload.vue';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type {
    IngredientAlternativeOption,
    IngredientPlacementStore,
    IngredientSummary,
} from '@/types';

defineProps<{
    ingredients: IngredientSummary[];
    alternativeOptions: IngredientAlternativeOption[];
    stores: IngredientPlacementStore[];
    editIngredientId?: number | null;
}>();
</script>

<template>
    <Empty v-if="ingredients.length === 0" class="min-h-64 border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <WheatIcon />
            </EmptyMedia>
            <EmptyTitle>Zatím nemáte žádné suroviny</EmptyTitle>
            <EmptyDescription>
                Vytvořte první surovinu pro aktuální rodinu.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <Table v-else>
        <TableHeader>
            <TableRow>
                <TableHead>Název</TableHead>
                <TableHead>Fotografie</TableHead>
                <TableHead>Obsah balení</TableHead>
                <TableHead>Popis</TableHead>
                <TableHead>Umístění</TableHead>
                <TableHead>Výživa</TableHead>
                <TableHead>Alternativy</TableHead>
                <TableHead class="text-right">Akce</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="ingredient in ingredients" :key="ingredient.id">
                <TableCell>
                    <div class="flex items-center gap-2">
                        <span>{{ ingredient.name }}</span>
                        <Badge v-if="ingredient.archived" variant="secondary">
                            Archivovaná
                        </Badge>
                    </div>
                </TableCell>
                <TableCell class="w-56">
                    <EntityImageUpload
                        media-type="ingredient-photo"
                        :entity-id="ingredient.id"
                        :image-url="ingredient.photoUrl"
                        :image-alt="`Fotografie suroviny ${ingredient.name}`"
                        :editable="!ingredient.archived"
                    />
                </TableCell>
                <TableCell>
                    <ul class="flex flex-wrap gap-x-3 gap-y-1">
                        <li
                            v-for="quantity in ingredient.quantities"
                            :key="quantity"
                        >
                            {{ quantity }}
                        </li>
                    </ul>
                </TableCell>
                <TableCell>{{
                    ingredient.description || 'Bez popisu'
                }}</TableCell>
                <TableCell>{{
                    ingredient.placement || 'Bez obchodu'
                }}</TableCell>
                <TableCell>{{
                    ingredient.nutrition ? 'Nutriční profil' : 'Bez profilu'
                }}</TableCell>
                <TableCell class="min-w-64"
                    ><IngredientAlternatives
                        :ingredient="ingredient"
                        :alternative-options="alternativeOptions"
                /></TableCell>
                <TableCell class="text-right">
                    <div class="flex justify-end gap-2">
                        <RestoreIngredientButton
                            v-if="ingredient.archived"
                            :ingredient="ingredient"
                        />
                        <template v-else>
                            <EditIngredientDialog
                                :ingredient="ingredient"
                                :stores="stores"
                                :open-initially="
                                    editIngredientId === ingredient.id
                                "
                            />
                            <ArchiveIngredientAlertDialog
                                :ingredient="ingredient"
                            />
                        </template>
                    </div>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
