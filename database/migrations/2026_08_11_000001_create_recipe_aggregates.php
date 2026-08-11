<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->binary('normalized_name', length: 1020);
            $table->decimal('base_servings', total: 20, places: 6);
            $table->unsignedInteger('version')->default(1);
            $table->string('source_url', 2048)->nullable();
            $table->unsignedInteger('preparation_minutes')->nullable();
            $table->unsignedInteger('cooking_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('nutrition_energy_kcal', total: 20, places: 6)->nullable();
            $table->decimal('nutrition_fat_grams', total: 20, places: 6)->nullable();
            $table->decimal('nutrition_protein_grams', total: 20, places: 6)->nullable();
            $table->decimal('nutrition_carbohydrate_grams', total: 20, places: 6)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'normalized_name']);
            $table->unique(['family_id', 'id']);
            $table->index(['family_id', 'archived_at']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recipe_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedInteger('position');
            $table->decimal('quantity', total: 20, places: 6);
            $table->string('quantity_kind');
            $table->timestamps();

            $table->foreign(['family_id', 'recipe_id'])->references(['family_id', 'id'])->on('recipes')->cascadeOnDelete();
            $table->foreign(['family_id', 'ingredient_id'])->references(['family_id', 'id'])->on('ingredients')->restrictOnDelete();
            $table->unique(['recipe_id', 'position']);
            $table->index(['ingredient_id', 'quantity_kind']);
        });

        Schema::create('recipe_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recipe_id');
            $table->unsignedInteger('position');
            $table->text('instruction');
            $table->timestamps();

            $table->foreign(['family_id', 'recipe_id'])->references(['family_id', 'id'])->on('recipes')->cascadeOnDelete();
            $table->unique(['recipe_id', 'position']);
        });

        Schema::create('recipe_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->binary('normalized_name', length: 1020);
            $table->timestamps();

            $table->unique(['family_id', 'normalized_name']);
            $table->unique(['family_id', 'id']);
        });

        Schema::create('recipe_recipe_tag', function (Blueprint $table): void {
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recipe_id');
            $table->unsignedBigInteger('recipe_tag_id');
            $table->timestamps();

            $table->primary(['recipe_id', 'recipe_tag_id']);
            $table->foreign(['family_id', 'recipe_id'])->references(['family_id', 'id'])->on('recipes')->cascadeOnDelete();
            $table->foreign(['family_id', 'recipe_tag_id'])->references(['family_id', 'id'])->on('recipe_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_recipe_tag');
        Schema::dropIfExists('recipe_tags');
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
