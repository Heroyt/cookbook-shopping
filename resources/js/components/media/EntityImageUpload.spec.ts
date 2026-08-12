/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import EntityImagePreview from './EntityImagePreview.vue';
import EntityImageUpload from './EntityImageUpload.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Entity image upload UI', () => {
    it('uses the generated Wayfinder action with static WebP, progress, and Czech field feedback', () => {
        const source = readSource('./EntityImageUpload.vue');

        expect(source).toContain(
            "import { store as storeEntityMedia } from '@/actions/App/Cookbook/Http/Controllers/EntityMediaController'",
        );
        expect(source).toContain('storeEntityMedia({');
        expect(source).toContain('FieldGroup');
        expect(source).toContain(':data-disabled="form.processing"');
        expect(source).not.toContain('space-y-');
        expect(source).toContain('image/webp');
        expect(source).toContain('@drop.prevent="dropFile"');
        expect(source).toContain('pendingPreviewUrl ?? imageUrl');
        expect(source).toContain('URL.revokeObjectURL');
        expect(source).toContain('form.progress.percentage');
        expect(source).toContain(':errors="[form.errors.image]"');
        expect(source).toContain('JPEG, PNG nebo statický WebP');
        expect(source).toContain('Klikněte nebo sem přetáhněte obrázek');
        expect(source).toContain('Nahrát obrázek');
        expect(source).toContain('Nahradit obrázek');
    });

    it('renders the current image and accessible upload controls', async () => {
        const editableHtml = await renderToString(
            createSSRApp(EntityImageUpload, {
                mediaType: 'ingredient-photo',
                entityId: 7,
                imageUrl: '/entity-media/ingredient-photo/7/catalogue',
                imageAlt: 'Fotografie suroviny Mouka',
            }),
        );

        expect(editableHtml).toContain(
            'src="/entity-media/ingredient-photo/7/catalogue"',
        );
        expect(editableHtml).toContain('alt="Fotografie suroviny Mouka"');
        expect(editableHtml).toContain('Obrázek');
        expect(editableHtml).toContain(
            'Kliknutím nebo přetažením vyberete jiný obrázek',
        );
        expect(editableHtml).toContain('type="file"');
    });

    it('renders a mutation-free preview for list views', async () => {
        const html = await renderToString(
            createSSRApp(EntityImagePreview, {
                imageUrl: '/entity-media/ingredient-photo/7/catalogue',
                imageAlt: 'Fotografie suroviny Mouka',
            }),
        );

        expect(html).toContain(
            'src="/entity-media/ingredient-photo/7/catalogue"',
        );
        expect(html).toContain('alt="Fotografie suroviny Mouka"');
        expect(html).not.toContain('type="file"');
        expect(html).not.toContain('Nahrát obrázek');
    });

    it('keeps uploads in edit dialogs and only previews in list views', () => {
        expect(readSource('../stores/EditStoreDialog.vue')).toContain(
            'media-type="store-logo"',
        );
        expect(readSource('../ingredients/EditIngredientDialog.vue')).toContain(
            'media-type="ingredient-photo"',
        );
        expect(readSource('../recipes/EditRecipeDialog.vue')).toContain(
            'media-type="recipe-cover"',
        );

        for (const list of [
            '../stores/StoreList.vue',
            '../stores/StoreSectionList.vue',
            '../ingredients/IngredientList.vue',
            '../recipes/RecipeList.vue',
        ]) {
            expect(readSource(list)).not.toContain('<EntityImageUpload');
        }

        expect(readSource('../ingredients/IngredientList.vue')).toContain(
            '<EntityImagePreview',
        );
        expect(readSource('../recipes/RecipeList.vue')).toContain(
            '<EntityImagePreview',
        );
    });
});
