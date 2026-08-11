/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import EntityImageUpload from './EntityImageUpload.vue';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Entity image upload UI', () => {
    it('uses the generated Wayfinder action with JPEG/PNG, progress, and Czech field feedback', () => {
        const source = readSource('./EntityImageUpload.vue');

        expect(source).toContain(
            "import { store as storeEntityMedia } from '@/actions/App/Cookbook/Http/Controllers/EntityMediaController'",
        );
        expect(source).toContain('storeEntityMedia({');
        expect(source).toContain('FieldGroup');
        expect(source).toContain(':data-disabled="form.processing"');
        expect(source).not.toContain('space-y-');
        expect(source).toContain('accept="image/jpeg,image/png"');
        expect(source).toContain('form.progress.percentage');
        expect(source).toContain(':errors="[form.errors.image]"');
        expect(source).toContain('JPEG nebo PNG, nejvýše 5 MB');
        expect(source).toContain('Nahrát obrázek');
        expect(source).toContain('Nahradit obrázek');
    });

    it('renders a protected current image, accessible labels, and no mutation form when archived', async () => {
        const editableHtml = await renderToString(
            createSSRApp(EntityImageUpload, {
                mediaType: 'ingredient-photo',
                entityId: 7,
                imageUrl: '/entity-media/ingredient-photo/7/catalogue',
                imageAlt: 'Fotografie suroviny Mouka',
                editable: true,
            }),
        );
        const archivedHtml = await renderToString(
            createSSRApp(EntityImageUpload, {
                mediaType: 'ingredient-photo',
                entityId: 7,
                imageUrl: '/entity-media/ingredient-photo/7/catalogue',
                imageAlt: 'Fotografie suroviny Mouka',
                editable: false,
            }),
        );

        expect(editableHtml).toContain(
            'src="/entity-media/ingredient-photo/7/catalogue"',
        );
        expect(editableHtml).toContain('alt="Fotografie suroviny Mouka"');
        expect(editableHtml).toContain('Vybrat obrázek');
        expect(editableHtml).toContain('type="file"');
        expect(archivedHtml).not.toContain('type="file"');
    });

    it('is composed into all approved Store, Store Section, Ingredient, and Recipe surfaces', () => {
        expect(readSource('../stores/StoreList.vue')).toContain(
            'media-type="store-logo"',
        );
        expect(readSource('../stores/StoreSectionList.vue')).toContain(
            'media-type="store-section-icon"',
        );
        expect(readSource('../ingredients/IngredientList.vue')).toContain(
            'media-type="ingredient-photo"',
        );
        expect(readSource('../recipes/RecipeList.vue')).toContain(
            'media-type="recipe-cover"',
        );
    });
});
